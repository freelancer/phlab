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
      'commit'  => 'required string',
      'against' => 'optional string',
      'offset'  => 'optional int',
      'limit'   => 'optional int',

      // TODO: Remove these parameters after T65473.
      'startCommit' => 'deprecated string',
      'endCommit'   => 'deprecated string',
    ];
  }

  protected function defineReturnType(): string {
    return 'list<map<string, wild>>';
  }

  protected function defineCustomErrorTypes(): array {
    return [
      'ERR-INVALID-PARAMETER' => pht('Missing or malformed parameter.'),
    ];
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
    $commit  = $request->getValue('commit');
    $against = $request->getValue('against');

    if ($commit !== null && !self::isValidCommitIdentifier($commit)) {
      throw (new ConduitException('ERR-INVALID-PARAMETER'))
        ->setErrorDescription(
          pht(
            'Parameter "%s" should be a commit hash.',
            'commit'));
    }

    if ($against !== null && !self::isValidCommitIdentifier($against)) {
      throw (new ConduitException('ERR-INVALID-PARAMETER'))
        ->setErrorDescription(
          pht(
            'Parameter "%s" should be a commit hash.',
            'against'));
    }

    // TODO: Remove this conditional after T65473.
    if ($commit === null) {
      $against = $request->getValue('startCommit');
      $commit  = $request->getValue('endCommit');

      if (!self::isValidCommitIdentifier($against, true)) {
        throw (new ConduitException('ERR-INVALID-PARAMETER'))
          ->setErrorDescription(
            pht(
              'Parameter "%s" should be a commit hash.',
              'startCommit'));
      }

      if (!self::isValidCommitIdentifier($commit, true)) {
        throw (new ConduitException('ERR-INVALID-PARAMETER'))
          ->setErrorDescription(
            pht(
              'Parameter "%s" should be a commit hash.',
              'endCommit'));
      }
    }

    $repository = $this->getRepository($request);
    $viewer     = $request->getUser();

    if ($against !== null) {
      $commit_range = "${against}..${commit}";

      $limit  = $request->getValue('limit', 100);
      $offset = $request->getValue('offset', 0);
    } else {
      $commit_range = $commit;

      // `--max-count` and `--skip` don't really make sense when querying
      // for a single commit.
      $limit  = 1;
      $offset = 0;
    }

    list($stdout) = $repository->execxLocalCommand(
      'log --max-count=%d --skip=%d --format=format:%s %s',
      $limit,
      $offset,
      '%H',
      $commit_range);
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

  /**
   * Validate commit identifiers.
   *
   * Commit idenitifers (i.e. the `startCommit` and `endCommit` parameters)
   * must be a 40-character SHA-1 hash, optionally succeeded by a tilde (`~`).
   *
   * @todo Remove the `$allow_tilde` parameter after T65473.
   */
  public static function isValidCommitIdentifier(?string $commit, bool $allow_tilde = false): bool {
    if ($allow_tilde) {
      return preg_match('/^[0-9a-f]{40}~?$/', $commit);
    }

    return preg_match('/^[0-9a-f]{40}$/', $commit);
  }

}
