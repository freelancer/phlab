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
    'name'     => 'issues',
    'wildcard' => true,
  ],
]);

$console = PhutilConsole::getConsole();

$actor_name = $args->getArg('actor');
if ($actor_name === null) {
  throw new PhutilArgumentUsageException(
    pht('You must specify the acting Phabricator user.'));
}

$actor = (new PhabricatorUser())->loadOneWhere('username = %s', $actor_name);
if ($actor === null) {
  throw new PhutilArgumentUsageException(
    pht('Phabricator user does not exist: %s', $actor_name));
}

$jira_url = $args->getArg('jira-url');
if ($jira_url === null) {
  throw new PhutilArgumentUsageException(
    pht('You must specify the JIRA base URL.'));
}

$jira_auth = $args->getArg('jira-auth-cookie');
if ($jira_auth === null) {
  throw new PhutilArgumentUsageException(
    pht('You must provide a JIRA authentication cookie.'));
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
    throw new PhutilArgumentUsageException(
      pht('No project found with slug: #%s', $slug));
  }
}

$jira_issues = $args->getArg('issues');
if (count($jira_issues) === 0) {
  $args->printHelpAndExit();
}

/**
 * Apply some basic transformations to (partially) translate
 * [[https://jira.atlassian.com/secure/WikiRendererHelpAction.jspa | JIRA text
 * formating]] to [[https://secure.phabricator.com/book/phabricator/article/remarkup/ |
 * Remarkup]].
 */
function transform_text(string $text): string {
  $base_uri = PhabricatorEnv::getEnvConfig('phabricator.base-uri');

  $transformations = [
    // Translate Phabricator URLs to object mentions.
    pregsprintf('\b%s/([DT][1-9]\d*(?:#([-\w\d]+))?)', '', $base_uri) => '$1',

    // Translate username mentions.
    pregsprintf('\B\[~(%R)\]\B', '', '[a-zA-Z0-9._-]*[a-zA-Z0-9_-]') => '@$1',

    // Single-lined code blocks. Use backticks unless the code block itself
    // contains backticks, in which case `##` is used as a delimiter instead.
    '/{{([^{`\n]+)}}/' => '`$1`',
    '/{{([^{\n]+)}}/'  => '##$1##',

  ];

  return preg_replace(
    array_keys($transformations),
    array_values($transformations),
    $text);
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

    $creator_email = $original['creator']['emailAddress'];
    $title         = $original['summary'];
    $description   = $original['description'];
    $priority      = $original['priority'];
    $status        = $original['resolution'];
    $comments      = $original['comment']['comments'];

    $creator = PhabricatorUser::loadOneWithEmailAddress($creator_email);
    if ($creator === null) {
      throw new Exception(
        pht('No Phabricator user found with email address: %s', $creator_email));
    }

    $task = ManiphestTask::initializeNewTask($creator)
      ->setTitle($title);

    if ($description !== null) {
      $task->setDescription(transform_text($description));
    }

    if ($original['assignee'] !== null) {
      $assignee_email = $original['assignee']['emailAddress'];
      $assignee       = PhabricatorUser::loadOneWithEmailAddress($assignee_email);

      if ($assignee === null) {
        throw new Exception(
          pht(
            'No Phabricator user found with email address: %s',
            $assignee_email));
      }

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

    $content_source = new PhabricatorConsoleContentSource();
    $transactions   = [];

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
            'added a comment to',
            'added a project to',
            'added a reviewer for',
            'added a task to',
            'added inline comments to',
            'added projects to',
            'added reviewers for',
            'closed',
            'created',
            'edited reviewers for',
            'removed reviewers',
            'requested changes to',
            'requested review of',
            'retitled',
            'set the repository for',
            'updated',
            'updated subscribers of',
            'updated the diff for',
            'updated the summary for',
            'updated the test plan for',
          ]));

      if (preg_match($differential_comment, $body)) {
        continue;
      }

      $author_email = $comment['author']['emailAddress'];
      $author = PhabricatorUser::loadOneWithEmailAddress($author_email);

      if ($author === null) {
        throw new Exception(
          pht('No Phabricator user found with email address: %s', $author_email));
      }

      $transactions[] = (new ManiphestTransaction())
        ->setAuthorPHID($author->getPHID())
        ->setTransactionType(PhabricatorTransactions::TYPE_COMMENT)
        ->attachComment(
          (new ManiphestTransactionComment())
            ->setAuthorPHID($author->getPHID())
            ->setContent(transform_text($comment['body'])));
    }

    // Add a comment explaining that the task has been migrated from JIRA.
    $transactions[] = (new ManiphestTransaction())
      ->setTransactionType(PhabricatorTransactions::TYPE_COMMENT)
      ->attachComment(
        (new ManiphestTransactionComment())
          ->setContent(sprintf(
            'This task has been migrated from JIRA: [[%s | %s]]',
            "${jira_url}/browse/${key}",
            $key)));

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
    $subscribers = array_filter($subscribers);

    if (count($subscribers) > 0) {
      $transactions[] = (new ManiphestTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
        ->setMetadataValue('edge:type', PhabricatorObjectHasSubscriberEdgeType::EDGECONST)
        ->setNewValue(['=' => array_fuse(mpull($subscribers, 'getPHID'))]);
    }

    // Migrate attachments.
    if (count($original_attachments = $original['attachment']) > 0) {
      $attachments = [];

      foreach ($original_attachments as $attachment) {
        $attachment_future = (new HTTPSFuture($attachment['content']))
          ->addHeader('Cookie', "JSESSIONID=${jira_auth}");

        $params = [
          'isExplicitUpload' => true,
          'name'             => $attachment['filename'],
          'mime-type'        => $attachment['mimeType'],
        ];

        $attachment_author = PhabricatorUser::loadOneWithEmailAddress($attachment['author']['emailAddress']);
        if ($attachment_author !== null) {
          $params['authorPHID'] = $attachment_author->getPHID();
        }

        list($attachment_body) = $attachment_future->resolvex();
        $attachments[] = PhabricatorFile::newFromFileData($attachment_body, $params);
      }

      // Maniphest doesn't support attachments, so instead we just comment on
      // the Maniphest task with a list of attachments.
      $transactions[] = (new ManiphestTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_COMMENT)
        ->attachComment(
          (new ManiphestTransactionComment())
            ->setContent(
              "The following attachments were migrated from JIRA:\n\n".
              implode(
                "\n",
                array_map(
                  function (PhabricatorFile $file): string {
                    return sprintf('{%s, layout=link}', $file->getMonogram());
                  },
                  $attachments))));
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

    $editor = id(new ManiphestTransactionEditor())
      ->setActor($actor)
      ->setContentSource($content_source)
      ->applyTransactions($task, $transactions);

    $console->writeOut(
      "%s\n",
      pht('Migrated %s to %s.', $key, $task->getMonogram()));
  } catch (Exception $ex) {
    $console->writeErr(
      "%s\n",
      pht('Failed to migrate JIRA issue %s: %s', $key, $ex->getMessage()));
  }
}
