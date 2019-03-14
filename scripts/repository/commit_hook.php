#!/usr/bin/env php
<?php

require_once __DIR__.'/../../../phabricator/scripts/__init_script__.php';

$args = new PhutilArgumentParser($argv);
$args->setTagline(pht('Git post-receive hook'));
$args->setSynopsis(<<<EOHELP
**commit_hook.php** [__options__]
    A Git post-receive hook that makes a HTTP GET request for pushes to
    non-tracked branches.
EOHELP
  );
$args->parseStandardArguments();
$args->parse(
  [
    [
      'name'  => 'url',
      'param' => 'template',
      'help'  => pht('The URL to make a HTTP request to.'),
    ],
  ]);

$console = PhutilConsole::getConsole();

// TODO: We //should// use `PhabricatorRepositoryQuery` here, but
// `PhabricatorRepositoryQuery` doesn't expose a `withLocalPaths` method.
$cwd = getcwd();
$repository = (new PhabricatorRepository())->loadOneWhere(
  'localPath IN (%Ls)',
  [
    $cwd,
    $cwd.'/',
  ]);

if ($repository === null) {
  throw new PhutilArgumentUsageException(
    pht(
      'Unable to determine the repository from the current '.
      'working directory ("%s")!',
      $cwd));
}

if (PhabricatorEnv::getEnvConfig('phabricator.silent')) {
  return 0;
}

// Don't do anything for non-hosted repositories.
if (!$repository->isHosted()) {
  return 0;
}

$stdin = @file_get_contents('php://stdin');
if ($stdin === false) {
  throw new PhutilArgumentUsageException(pht('Failed to read stdin!'));
}

foreach (phutil_split_lines($stdin, false) as $line) {
  list($old_commit, $new_commit, $ref) = explode(' ', $line, 3);

  if (!preg_match('(^refs/heads/)', $ref)) {
    continue;
  }

  // This commit hook is only needed for untracked branches. Tracked branches
  // should use a Herald rule in order to trigger a Harbormaster build.
  $branch = preg_replace('(^refs/heads/)', '', $ref);
  if ($repository->shouldTrackBranch($branch)) {
    continue;
  }

  // See @{method:PhabricatorRepositoryCommit::getBuildVariables}.
  $variables = [
    'branch'         => $branch,
    'commit'         => $new_commit,
    'repository.uri' => $repository->getPublicCloneURI(),
  ];

  $uri = varsprintf('vurisprintf', $args->getArg('url'), $variables);
  $uri = new PhutilURI($uri);

  $future = (new HTTPSFuture($uri))
    ->setMethod('GET')
    ->setTimeout(10);

  list($status, $body, $headers) = $future->resolve();

  if (strlen($body) > 0) {
    $console->writeOut("%s\n", $body);
  }
}
