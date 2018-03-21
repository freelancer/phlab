#!/usr/bin/env php
<?php

require_once __DIR__.'/../../phabricator/scripts/__init_script__.php';

$args = new PhutilArgumentParser($argv);
$args->setTagline('Migrate tasks from JIRA to Phabricator.');

$args->parseStandardArguments();
$args->parse([
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
    'name'     => 'issues',
    'wildcard' => true,
  ],
]);

$console = PhutilConsole::getConsole();

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

$jira_issues = $args->getArg('issues');
if (count($jira_issues) === 0) {
  $args->printHelpAndExit();
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
    $creator       = PhabricatorUser::loadOneWithEmailAddress($creator_email);
    $title         = $original['summary'];
    $description   = $original['description'];

    if ($creator === null) {
      throw new Exception(
        pht(
          'No Phabricator user found with email address: %s',
          $creator_email));
    }

    $task = ManiphestTask::initializeNewTask($creator)
      ->setTitle($title);

    if ($description !== null) {
      $task->setDescription($description);
    }

    $task->save();
    $console->writeOut(
      "%s\n",
      pht('Migrated %s to %s.', $key, $task->getMonogram()));
  } catch (Exception $ex) {
    $console->writeErr(
      "%s\n",
      pht('Failed to migrate JIRA issue %s: %s', $key, $ex->getMessage()));
  }
}
