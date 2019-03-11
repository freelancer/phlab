#!/usr/bin/env php
<?php

require_once __DIR__.'/../../../phabricator/scripts/__init_script__.php';

$args = new PhutilArgumentParser($argv);
$args->setTagline(pht('Git post-receive hook'));
$args->setSynopsis(<<<EOHELP
**commit_hook.php** [__options__]
    A Git post-receive hook that triggers a Harbormaster build plan for pushes
    to non-tracked branches.
EOHELP
  );
$args->parseStandardArguments();
$args->parse(
  [
    [
      'name'  => 'build-plan',
      'param' => 'id',
      'help'  => pht('ID of the Harbormaster build plan to be executed.'),
    ],
  ]);

$console = PhutilConsole::getConsole();

$username = getenv(DiffusionCommitHookEngine::ENV_USER) ?: null;
if ($username === null) {
  throw new PhutilArgumentUsageException(
    pht(
      '%s is not set!',
      DiffusionCommitHookEngine::ENV_USER));
}

$user = (new PhabricatorPeopleQuery())
  ->setViewer(PhabricatorUser::getOmnipotentUser())
  ->withUsernames([$username])
  ->executeOne();

if ($user === null) {
  throw new PhutilArgumentUsageException(
    pht(
      'No such user "%s"!',
      $username));
}

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

// Don't do anything for non-hosted repositories.
if (!$repository->isHosted()) {
  return 0;
}

$build_plan_id = $args->getArg('build-plan');
if ($build_plan_id === null) {
  throw new PhutilArgumentUsageException(
    pht(
      'You must specify a build plan with `%s`.',
      '--build-plan'));
}

$build_plan = (new HarbormasterBuildPlanQuery())
  ->setViewer($user)
  ->withIDs([$build_plan_id])
  ->executeOne();

if ($build_plan === null) {
  throw new PhutilArgumentUsageException(
    pht(
      'No such build plan (%d) was found!',
      $build_plan_id));
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

  $commit = (new DiffusionCommitQuery())
    ->setViewer($user)
    ->withRepository($repository)
    ->withIdentifiers([$new_commit])
    ->executeOne();

  // TODO: Add retry logic.
  if ($commit === null) {
    $console->writeErr(
      "%s\n",
      pht(
        'Commit %s has not yet been processed by daemons. '.
        'Unable to trigger Harbormaster build plan %d.',
        $new_commit,
        $build_plan->getID()));
    continue;
  }

  $buildable = HarbormasterBuildable::initializeNewBuildable($user)
    ->setBuildablePHID($commit->getHarbormasterBuildablePHID())
    ->setContainerPHID($commit->getHarbormasterContainerPHID())
    ->save();

  $buildable->sendMessage(
    $user,
    HarbormasterMessageType::BUILDABLE_BUILD,
    false);

  $console->writeOut(
    "%s\n\n    %s\n\n",
    pht(
      'Applying plan %s to new buildable %s...',
      $build_plan->getID(),
      $buildable->getMonogram()),
    PhabricatorEnv::getProductionURI('/'.$buildable->getMonogram()));

  $build = $buildable->applyPlan($build_plan, [], $user->getPHID());
  $console->writeOut("%s\n", pht('Done.'));
}
