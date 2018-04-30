#!/usr/bin/env php
<?php

require_once __DIR__.'/../../phabricator/scripts/__init_script__.php';

$args = new PhutilArgumentParser($argv);
$args->setTagline('Migrate tasks from JIRA to Phabricator.');

$args->parseStandardArguments();
$args->parse([
  [
    'name'  => 'actor',
    'param' => 'username',
    'help'  => pht(
      'The Phabricator user to act as when not impersonating another user.'),
  ],
  [
    'name'  => 'jira-url',
    'param' => 'url',
    'help'  => pht('JIRA base URL.'),
  ],
  [
    'name'  => 'jira-auth-cookie',
    'param' => 'jsessionid',
    'help'  => pht(
      'JIRA authentication cookie. You can obtain this by '.
      'logging in to JIRA and inspecting the `%s` cookie.',
      'JSESSIONID'),
  ],
  [
    'name'  => 'priority-map',
    'param' => 'from=to,from=to,...',
    'help'  => pht('Mapping of JIRA priorities to Phabricator priorities.'),
  ],
  [
    'name'  => 'status-map',
    'param' => 'from=to,from=to,...',
    'help'  => pht('Mapping of JIRA statuses to Phabricator statuses.'),
  ],
  [
    'name'  => 'project',
    'param' => 'slug',
    'help'  => pht('Project to tag migrated Phabricator tasks with.'),
  ],
  [
    'name'  => 'transition',
    'param' => 'name',
    'help'  => pht(
      'Transition to be performed after the issue has been migrated.'),
  ],
  [
    'name'     => 'issues',
    'wildcard' => true,
  ],
]);

$console = PhutilConsole::getConsole();

$actor_name = $args->getArg('actor');
if ($actor_name === null) {
  $args->printUsageException(
    new PhutilArgumentUsageException(
      pht(
        'You must specify the acting Phabricator user with `%s`.',
        '--actor')));
  exit(PhutilArgumentParser::PARSE_ERROR_CODE);
}

$actor = (new PhabricatorUser())->loadOneWhere('username = %s', $actor_name);
if ($actor === null) {
  $args->printUsageException(
    new PhutilArgumentUsageException(
      pht(
        'Phabricator user does not exist: %s',
        $actor_name)));
  exit(PhutilArgumentParser::PARSE_ERROR_CODE);
}

$jira_url = $args->getArg('jira-url');
if ($jira_url === null) {
  $args->printUsageException(
    new PhutilArgumentUsageException(
      pht(
        'You must specify the JIRA base URL with `%s`.',
        '--jira-url')));
  exit(PhutilArgumentParser::PARSE_ERROR_CODE);
}

$jira_auth = $args->getArg('jira-auth-cookie');
if ($jira_auth === null) {
  $args->printUsageException(
    new PhutilArgumentUsageException(
      pht(
        'You must provide a JIRA authentication cookie with `%s`.',
        '--jira-auth-cookie')));
  exit(PhutilArgumentParser::PARSE_ERROR_CODE);
}

$priority_map = [];
foreach (explode(',', $args->getArg('priority-map')) as $mapping) {
  $fields = explode('=', $mapping, 2);

  // Just ignore invalid mappings.
  if (count($fields) !== 2) {
    continue;
  }

  $from = strtolower($fields[0]);
  $to   = strtolower($fields[1]);

  $priority_map[$from] = ManiphestTaskPriority::getTaskPriorityFromKeyword($to);
}

$status_map = [];
foreach (explode(',', $args->getArg('status-map')) as $mapping) {
  $fields = explode('=', $mapping, 2);

  // Just ignore invalid mappings.
  if (count($fields) !== 2) {
    continue;
  }

  $from = strtolower($fields[0]);
  $to   = strtolower($fields[1]);

  $status_map[$from] = $to;
}

$project = null;
$slug    = $args->getArg('project');

if ($slug !== null) {
  $project = id(new PhabricatorProjectQuery())
    ->setViewer($actor)
    ->withSlugs([$slug])
    ->executeOne();

  if ($project === null) {
    $args->printUsageException(
      new PhutilArgumentUsageException(
        pht(
          'No project found with slug: #%s',
          $slug)));
    exit(PhutilArgumentParser::PARSE_ERROR_CODE);
  }
}

$jira_issues = $args->getArg('issues');
if (count($jira_issues) === 0) {
  $args->printHelpAndExit();
}

/**
 * Get the Phabricator user associated with the specified email address.
 *
 * If no Phabricator user is found then prompt for a username.
 */
function get_user(string $email_address): PhabricatorUser {
  // Cache the user mapping so that we don't continuously prompt when JIRA
  // email address don't match Phabricator users.
  static $cache = [];

  if (isset($cache[$email_address])) {
    return $cache[$email_address];
  }

  $user = null;
  $username = null;

  do {
    // Lookup the user by email address if we haven't yet prompted for a username.
    if ($username === null) {
      $user = PhabricatorUser::loadOneWithEmailAddress($email_address);
    } else {
      $user = (new PhabricatorUser())->loadOneWhere('username = %s', $username);
    }

    // If no user is found, prompt for a username.
    if ($user === null) {
      if ($username === null) {
        $prompt = pht(
          "No Phabricator user was found with email address: %s\n\n".
          "Specify the corresponding Phabricator username:",
          $email_address);
      } else {
        $prompt = pht(
          "No Phabricator user was found with username: %s\n\n".
          "Specify an existing Phabricator username:",
          $username);
      }
      $username = phutil_console_prompt($prompt);

      // If no text was entered at the prompt, bail out.
      if (!strlen(trim($username))) {
        throw new Exception(
          pht(
            'No Phabricator user found with email address: %s',
            $email_address));
      }
    }
  } while ($user === null);

  return $cache[$email_address] = $user;
}

/**
 * Apply some basic transformations to (partially) translate
 * [[https://jira.atlassian.com/secure/WikiRendererHelpAction.jspa | JIRA text
 * formating]] to [[https://secure.phabricator.com/book/phabricator/article/remarkup/ |
 * Remarkup]].
 */
function transform_text(string $text, array $attachments): string {
  assert_instances_of($attachments, 'PhabricatorFile');

  $object_mention_regex = pregsprintf(
    '\b%s/((%R|%R)(?:#([-\w\d]+))?)',
    '',
    PhabricatorEnv::getEnvConfig('phabricator.base-uri'),
    '[B-FHIK-MPQS-WZ][1-9]\d*',
    '(?:r[A-Z]+:?|R[1-9]\d*:)[0-9a-f]{5,40}');

  // According to http://urlregex.com/, this is the "perfect URL regular expression".
  $url_regex = '(?:(?:https?|ftp)://)'.
    '(?:\S+(?::\S*)?@|\d{1,3}(?:\.\d{1,3}){3}|'.
    '(?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)'.
    '(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*'.
    '(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))'.
    '(?::\d+)?(?:[^\s]*)?';

  $transformations = [
    // Translate Phabricator URLs to object mentions.
    $object_mention_regex => '$1',

    // Translate username mentions.
    pregsprintf('\B\[~(%R)\]\B', '', '[a-zA-Z0-9._-]*[a-zA-Z0-9_-]') => '@$1',

    // Single-lined code blocks. Use backticks unless the code block itself
    // contains backticks, in which case `##` is used as a delimiter instead.
    '/{{([^{`\n]+)}}/' => '`$1`',
    '/{{([^{\n]+)}}/'  => '##$1##',

    // Block quotes
    '/^bq\. */m' => '> ',

    // Code blocks
    '/^ *{code:(?:language=)?(\w+)} *$/m' => '```lang=$1',
    '/^ *{code(?::[^{}]*)?} *$/m' => '```',
    '/^ *{noformat} *$/m' => '```',

    // Headings
    '/^h1\. *(.*)$/m' => '= $1 =',
    '/^h2\. *(.*)$/m' => '== $1 ==',
    '/^h3\. *(.*)$/m' => '=== $1 ===',
    '/^h4\. *(.*)$/m' => '==== $1 ====',
    '/^h5\. *(.*)$/m' => '===== $1 =====',
    '/^h6\. *(.*)$/m' => '====== $1 ======',

    // Links
    pregsprintf('\[(%R)\]', 'iu', $url_regex) => '$1',
    pregsprintf('\[([^\|]+) *\| *(%R)\]', 'iu', $url_regex) => '[[$2 | $1]]',

    // Unsupported formatting
    '/{color(?::(?:[a-zA-Z]+|#?[0-9a-fA-F]+))?}/' => '',

    // Inline attachments
    '/\B!([^!|]*)(?:\|([^!|]*))?!\B/' => function (array $matches) use ($attachments): string {
      $name = $matches[1];

      if (isset($attachments[$name])) {
        return sprintf('{%s}', $attachments[$name]->getMonogram());
      } else {
        return $matches[0];
      }
    },
  ];

  // Convert CRLF to LF.
  $text = str_replace("\r\n", "\n", $text);

  // The `preg_replace` function cannot be used with callbacks and the
  // `preg_replace_callback` function cannot be used with string replacements.
  $callback_transformations = array_filter($transformations, 'is_callable');
  $text_transformations     = array_filter($transformations, 'is_string');

  $text = preg_replace(
    array_keys($text_transformations),
    array_values($text_transformations),
    $text);
  $text = preg_replace_callback_array(
    $callback_transformations,
    $text);

  return $text;
}

$futures = array_map(
  function (string $issue) use ($jira_url, $jira_auth): HTTPSFuture {
    $uri = (new PhutilURI($jira_url))
      ->setPath("/rest/api/2/issue/${issue}");

    return (new HTTPSFuture($uri))
      ->addHeader('Cookie', "JSESSIONID=${jira_auth}")
      ->addHeader('Content-Type', 'application/json');
  },
  array_fuse($jira_issues));

foreach (new FutureIterator($futures) as $key => $future) {
  $console->writeErr("%s\n", pht('Migrating JIRA issue %s...', $key));

  try {
    list($body) = $future->resolvex();
    $original   = phutil_json_decode($body)['fields'];

    $creator     = get_user($original['creator']['emailAddress']);
    $title       = $original['summary'];
    $description = $original['description'];
    $priority    = $original['priority'];
    $status      = $original['resolution'];
    $comments    = $original['comment']['comments'];

    $task = ManiphestTask::initializeNewTask($creator)
      ->setTitle($title);

    // Migrate attachments.
    //
    // NOTE: We need to import JIRA attachments before we can process comments
    // so that we can support the embedding of attachments in comment text.
    $attachments = array_map(
      function (array $attachment) use ($jira_auth): PhabricatorFile {
        $attachment_future = (new HTTPSFuture($attachment['content']))
          ->addHeader('Cookie', "JSESSIONID=${jira_auth}");

        $params = [
          'isExplicitUpload' => true,
          'name'             => $attachment['filename'],
          'mime-type'        => $attachment['mimeType'],
        ];

        try {
          $attachment_author = get_user($attachment['author']['emailAddress']);
          $params['authorPHID'] = $attachment_author->getPHID();
        } catch (Exception $ex) {
          // Just ignore missing users here.
        }

        list($attachment_body) = $attachment_future->resolvex();
        return PhabricatorFile::newFromFileData($attachment_body, $params);
      },
      ipull($original['attachment'], null, 'filename'));

    if ($description !== null) {
      $task->setDescription(transform_text($description, $attachments));
    }

    if ($original['assignee'] !== null) {
      $assignee = get_user($original['assignee']['emailAddress']);
      $task->setOwnerPHID($assignee->getPHID());
    }

    $task->setPriority(
      idx(
        $priority_map,
        strtolower($priority['name']),
        ManiphestTaskPriority::getDefaultPriority()));

    if ($status !== null) {
      $task->setStatus(
        idx(
          $status_map,
          strtolower($status['name']),
          ManiphestTaskStatus::getDefaultStatus()));
    }

    $content_source = PhabricatorContentSource::newForSource(PhabricatorConsoleContentSource::SOURCECONST);
    $editor = id(new ManiphestTransactionEditor())
      ->setActor($actor)
      ->setContentSource($content_source)
      ->setIsSilent(true);
    $transactions = [];

    foreach ($comments as $comment) {
      $body = $comment['body'];

      // Skip comments which are generated by Phabricator.
      $differential_comment = pregsprintf(
        '^[a-zA-Z0-9._-]*[a-zA-Z0-9_-] (?:%R) D[1-9]\d*: ',
        '',
        implode(
          '|',
          [
            'abandoned',
            'accepted',
            'added \d+ commit\(s\) for',
            'added a comment to',
            'added a dependency for',
            'added a dependent revision for',
            'added a project to',
            'added a reviewer for',
            'added a task to',
            'added inline comments to',
            'added projects to',
            'added reviewers for',
            'awarded',
            'changed the edit policy for',
            'changed the visibility for',
            'closed',
            'commandeered',
            'created',
            'edited reviewers for',
            'failed to build B[1-9]\d*: Diff [1-9]\d* for',
            'planned changes to',
            'reclaimed',
            'removed \d+ commit\(s\) for',
            'removed a dependency for',
            'removed a dependent revision for',
            'removed a project from',
            'removed a reviewer for',
            'removed a task from',
            'removed reviewers',
            'removed (?:r[A-Z]{1,32}|R[1-9]\d*) .+ as the repository for',
            'requested changes to',
            'requested review of',
            'retitled',
            'set the repository for',
            'updated',
            'updated subscribers of',
            'updated the diff for',
            'updated the summary for',
            'updated the summary of',
            'updated the test plan for',
          ]));

      if (preg_match($differential_comment, $body)) {
        continue;
      }

      // Skip comments that don't have an author.
      if (!isset($comment['author'])) {
        continue;
      }

      $author = get_user($comment['author']['emailAddress']);
      $transactions[] = (new ManiphestTransaction())
        ->setAuthorPHID($author->getPHID())
        ->setTransactionType(PhabricatorTransactions::TYPE_COMMENT)
        ->attachComment(
          (new ManiphestTransactionComment())
            ->setAuthorPHID($author->getPHID())
            ->setContent(transform_text($comment['body'], $attachments)));
    }

    // Add a comment explaining that the task has been migrated from JIRA.
    $migration_comment = sprintf(
      'This task has been migrated from JIRA: [[%s | %s]]',
      "${jira_url}/browse/${key}",
      $key);

    // Add issue links as a comment.
    if (count($issuelinks = $original['issuelinks']) > 0) {
      $migration_comment .= "\n\n= Issue Links =";

      foreach ($issuelinks as $issuelink) {
        if ($issue = idx($issuelink, 'inwardIssue')) {
          $migration_comment .= sprintf(
            "\n- %s [[%s/browse/%s | %s: %s]]",
            ucfirst($issuelink['type']['inward']),
            $jira_url,
            $issue['key'],
            $issue['key'],
            $issue['fields']['summary']);
        }

        if ($issue = idx($issuelink, 'outwardIssue')) {
          $migration_comment .= sprintf(
            "\n- %s [[%s/browse/%s | %s: %s]]",
            ucfirst($issuelink['type']['outward']),
            $jira_url,
            $issue['key'],
            $issue['key'],
            $issue['fields']['summary']);
        }
      }
    }

    // Migrate attachments.
    //
    // Maniphest doesn't support attachments, so instead we just comment on the
    // Maniphest task with a list of attachments.
    if (count($attachments) > 0) {
      $migration_comment .= "\n\n= Attachments =\n";
      $migration_comment .= implode("\n", array_map(
        function (PhabricatorFile $attachment): string {
          return sprintf('{%s, layout=link}', $attachment->getMonogram());
        },
        $attachments));
    }

    $transactions[] = (new ManiphestTransaction())
      ->setTransactionType(PhabricatorTransactions::TYPE_COMMENT)
      ->attachComment((new ManiphestTransactionComment())->setContent($migration_comment));

    // Tag the imported task with the specified project.
    if ($project !== null) {
      $transactions[] = (new ManiphestTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
        ->setMetadataValue('edge:type', PhabricatorProjectObjectHasProjectEdgeType::EDGECONST)
        ->setNewValue(['=' => array_fuse([$project->getPHID()])]);
    }

    // Add subscribers.
    $subscribers_uri = (new PhutilURI($future->getURI()))
      ->appendPath('watchers');
    $subscribers_future = (new HTTPSFuture($subscribers_uri))
      ->addHeader('Cookie', "JSESSIONID=${jira_auth}")
      ->addHeader('Content-Type', 'application/json');
    list($subscribers_body) = $subscribers_future->resolvex();

    $subscribers = array_map(
      function (array $subscriber): ?PhabricatorUser {
        return PhabricatorUser::loadOneWithEmailAddress($subscriber['emailAddress']);
      },
      phutil_json_decode($subscribers_body)['watchers']);

    // Just ignore any JIRA watchers that don't exist as Phabricator users.
    $subscribers      = array_filter($subscribers);
    $subscriber_phids = mpull($subscribers, 'getPHID');

    if (count($subscriber_phids) > 0) {
      $transactions[] = (new ManiphestTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
        ->setMetadataValue('edge:type', PhabricatorObjectHasSubscriberEdgeType::EDGECONST)
        ->setNewValue(['=' => array_fuse($subscriber_phids)]);
    }

    // Link associated Differential revisions and Diffusion commits to the
    // migrated Maniphest task.
    $doorkeeper_object = (new DoorkeeperExternalObject())->loadOneWhere(
      'applicationType = %s AND objectType = %s AND objectID = %s',
      DoorkeeperBridgeJIRA::APPTYPE_JIRA,
      DoorkeeperBridgeJIRA::OBJTYPE_ISSUE,
      $key);

    if ($doorkeeper_object !== null) {
      $revisions = (new DifferentialRevisionQuery())
        ->setViewer($actor)
        ->needCommitPHIDs(true)
        ->withEdgeLogicPHIDs(
          PhabricatorJiraIssueHasObjectEdgeType::EDGECONST,
          PhabricatorQueryConstraint::OPERATOR_OR,
          [$doorkeeper_object->getPHID()])
        ->execute();

      $revision_phids = mpull($revisions, 'getPHID');
      $transactions[] = (new ManiphestTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
        ->setMetadataValue('edge:type', ManiphestTaskHasRevisionEdgeType::EDGECONST)
        ->setNewValue(['=' => array_fuse($revision_phids)]);

      $commit_phids = array_mergev(mpull($revisions, 'getCommitPHIDs'));
      $transactions[] = (new ManiphestTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
        ->setMetadataValue('edge:type', ManiphestTaskHasCommitEdgeType::EDGECONST)
        ->setNewValue(['=' => array_fuse($commit_phids)]);
    }

    $editor->applyTransactions($task, $transactions);
    $transactions = [];

    // Unsubscribe the actor from the migrated task if they weren't watching
    // the original JIRA issue. This transaction needs to be applied separately,
    // because commenting on a task implicitly subscribes the comment author.
    if (!in_array($actor->getPHID(), $subscriber_phids)) {
      $transactions[] = (new ManiphestTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
        ->setMetadatavalue('edge:type', PhabricatorObjectHasSubscriberEdgeType::EDGECONST)
        ->setNewValue(['-' => array_fuse([$actor->getPHID()])]);
    }

    if (count($transactions) > 0) {
      $editor->applyTransactions($task, $transactions);
    }

    $console->writeOut(
      "%s\n",
      pht('Migrated %s to %s.', $key, $task->getMonogram()));

    // Comment on the JIRA issue, explaining that it has been migrated to Phabricator.
    try {
      $jira_comment_uri = (new PhutilURI($jira_url))
        ->setPath("/rest/api/2/issue/${key}/comment");

      (new HTTPSFuture($jira_comment_uri))
        ->addHeader('Cookie', "JSESSIONID=${jira_auth}")
        ->addHeader('Content-Type', 'application/json')
        ->setData(phutil_json_encode([
          'body' => sprintf(
            '{panel:title=IMPORTANT|borderColor=%s|titleBGColor=%s|bgColor=%s}%s{panel}',
            '#CCCCCC',
            '#F7D6C1',
            '#FFFFCE',
            sprintf(
              'This issue has been migrated to [%s|%s].',
              $task->getMonogram(),
              PhabricatorEnv::getProductionURI($task->getURI()))),
        ]))
        ->setMethod('POST')
        ->resolvex();
    } catch (Exception $ex) {
      $console->writeErr(
        "%s\n",
        pht('Failed to comment on JIRA issue %s: %s', $key, $ex->getMessage()));
    }

    // Add a remote link to the JIRA issue, pointing to the migrated Maniphest task.
    try {
      $jira_remote_link_uri = (new PhutilURI($jira_url))
        ->setPath("/rest/api/2/issue/${key}/remotelink");

      (new HTTPSFuture($jira_remote_link_uri))
        ->addHeader('Cookie', "JSESSIONID=${jira_auth}")
        ->addHeader('Content-Type', 'application/json')
        ->setData(phutil_json_encode([
          'relationship' => 'migrated to',
          'object' => [
            'url'     => PhabricatorEnv::getProductionURI($task->getURI()),
            'title'   => $task->getTitle(),
            'icon' => [
              'url16x16' => celerity_get_resource_uri('rsrc/favicons/favicon-16x16.png'),
              'title'    => 'Phabricator',
            ],
            'status' => [
              'resolved' => $task->isClosed(),
            ],
          ],
          'application' => [
            'name' => 'Phabricator',
            'type' => 'com.phacility.phabricator',
          ],
        ]))
        ->setMethod('POST')
        ->resolvex();
    } catch (Exception $ex) {
      $console->writeErr(
        "%s\n",
        pht('Failed to add remote link to JIRA issue %s: %s', $key, $ex->getMessage()));
    }

    // Transition the JIRA issue into the requested state.
    if (($transition_name = $args->getArg('transition')) !== null) {
      try {
        $jira_transition_uri = (new PhutilURI($jira_url))
          ->setPath("/rest/api/2/issue/${key}/transitions");

        // Find the ID of the transition matching the specified name.
        list($transitions_body) = (new HTTPSFuture($jira_transition_uri))
          ->addHeader('Cookie', "JSESSIONID=${jira_auth}")
          ->addHeader('Content-Type', 'application/json')
          ->resolvex();
        $transitions = phutil_json_decode($transitions_body)['transitions'];

        $transition_id = array_reduce(
          $transitions,
          function (?int $result, array $transition) use ($transition_name): ?int {
            if ($transition['name'] === $transition_name) {
              return $transition['id'];
            } else {
              return $result;
            }
          });

        if ($transition_id === null) {
          throw new Exception(
            pht('Could not find "%s" transition.', $transition_name));
        }

        (new HTTPSFuture($jira_transition_uri))
          ->addHeader('Cookie', "JSESSIONID=${jira_auth}")
          ->addHeader('Content-Type', 'application/json')
          ->setData(phutil_json_encode([
            'transition' => [
              'id' => $transition_id,
            ],
          ]))
          ->setMethod('POST')
          ->resolvex();
      } catch (Exception $ex) {
        $console->writeErr(
          "%s\n",
          pht('Failed to perform transition on JIRA issue %s: %s', $key, $ex->getMessage()));
      }
    }
  } catch (Exception $ex) {
    $console->writeErr(
      "%s\n",
      pht('Failed to migrate JIRA issue %s: %s', $key, $ex->getMessage()));
  }
}
