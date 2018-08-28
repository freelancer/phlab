<?php

/**
 * TODO: Ideally we would just use `diffusion.commit.search`, but it doesn't
 * currently support searching for a range of commits. See
 * https://discourse.phabricator-community.org/t/querying-for-commits-within-a-specified-range/1308.
 */
final class DiffusionChangesConduitAPIMethod
  extends DiffusionQueryConduitAPIMethod {

  public function getMethodSummary(): string {
    return pht(
      'Retrieve information about the commits within a specified range.');
  }

  public function getMethodDescription(): string {
    return pht(
      'Retrieve information about the commits within a specified range. '.
      'This method is intended to be used exclusively by [[%s | Changelog]].',
      'https://changelog.analytics.flnltd.com');
  }

  protected function defineCustomParamTypes(): array {
    return [
      'startCommit' => 'required string',
      'endCommit'   => 'required string',
      'offset'      => 'optional int',
      'limit'       => 'optional int',
    ];
  }

  protected function defineReturnType(): string {
    return 'list<map<string, wild>>';
  }

  protected function defineCustomErrorTypes(): array {
    return [];
  }

  public function getMethodStatus(): string {
    return self::METHOD_STATUS_UNSTABLE;
  }

  public function getMethodStatusDescription(): ?string {
    return null;
  }

  public function getRequiredScope(): string {
    return self::SCOPE_ALWAYS;
  }

  public function getAPIMethodName(): string {
    return 'diffusion.changes';
  }

  protected function getGitResult(ConduitAPIRequest $request): array {
    $repository = $this->getRepository($request);
    $viewer     = $request->getUser();

    list($stdout) = $repository->execxLocalCommand(
      'log --max-count=%d --skip=%d --format=format:%s %s..%s',
      $request->getValue('limit', 100),
      $request->getValue('offset', 0),
      '%H',
      $request->getValue('startCommit'),
      $request->getValue('endCommit'));
    $commit_hashes = phutil_split_lines($stdout, false);

    $commits = (new DiffusionCommitQuery())
      ->setViewer($viewer)
      ->withRepositoryIDs([$repository->getID()])
      ->withIdentifiers($commit_hashes)
      ->needCommitData(true)
      ->needIdentities(true)
      ->execute();

    return array_map(
      function (PhabricatorRepositoryCommit $commit): array {
        return [
          'id'         => $commit->getID(),
          'phid'       => $commit->getPHID(),
          'identifier' => $commit->getCommitIdentifier(),
          'summary'    => $commit->getCommitData()->getSummary(),

          'author'    => $commit->getAuthorIdentity()->getIdentityName(),
          'committer' => $commit->getCommitterIdentity()->getIdentityName(),
        ];
      },
      $commits);
  }

}
