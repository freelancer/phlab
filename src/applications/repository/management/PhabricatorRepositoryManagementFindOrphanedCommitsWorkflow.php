<?php

final class PhabricatorRepositoryManagementFindOrphanedCommitsWorkflow
  extends PhabricatorRepositoryManagementWorkflow {

  protected function didConstruct(): void {
    $this
      ->setName('find-orphaned-commits')
      ->setExamples('**find-orphaned-commits** [__options__] __repository__ ...')
      ->setSynopsis(pht('List commits which are not found on any branch.'))
      ->setArguments([
        [
          'name'     => 'repositories',
          'wildcard' => true,
        ],
      ]);
  }

  public function execute(PhutilArgumentParser $args): int {
    $console      = PhutilConsole::getConsole();
    $repositories = $this->loadRepositories($args, 'repositories');

    if (!$repositories) {
      throw new PhutilArgumentUsageException(
        pht('Specify one or more repositories.'));
    }

    foreach ($repositories as $repository) {
      $orphaned_commits = $this->getOrphanedCommits($repository);

      foreach ($orphaned_commits as $commit) {
        $console->writeOut("%s\n", $commit);
      }
    }

    return 0;
  }

  private function getOrphanedCommits(PhabricatorRepository $repository): iterable {
    $viewer = $this->getViewer();

    if (!$repository->isGit()) {
      throw new PhutilArgumentUsageException(
        pht(
          'Only Git repositories are supported, this repository ("%s") '.
          'is not a Git repository.',
          $repository->getDisplayName()));
    }

    $commits = (new DiffusionCommitQuery())
      ->setViewer($viewer)
      ->withRepository($repository)
      ->execute();

    $client  = $repository->newConduitClient($this->getViewer());
    $futures = [];

    foreach ($commits as $commit) {
      $futures[$commit->getCommitIdentifier()] = $client->callMethod(
        'diffusion.branchquery',
        [
          'contains'   => $commit->getCommitIdentifier(),
          'repository' => $repository->getPHID(),
        ]);
    }

    $futures = (new FutureIterator($futures))
      ->limit(8);

    foreach ($futures as $key => $future) {
      $branches = $future->resolve();

      if (!$branches) {
        yield $key;
      }
    }
  }

}
