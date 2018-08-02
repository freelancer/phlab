<?php

/**
 * TODO: Ideally we would just use `diffusion.commit.search`, but it doesn't
 * currently support searching for a range of commits. See
 * https://discourse.phabricator-community.org/t/querying-for-commits-within-a-specified-range/1308.
 */
final class DiffusionChangesConduitAPIMethod
  extends DiffusionQueryConduitAPIMethod {

  public function getMethodDescription() {
    return pht(
      'Retrieve information about the commits within a specified range. '.
      'This method is intended to be used exclusively by [[%s | Changelog]].',
      'https://changelog.analytics.flnltd.com');
  }

  protected function defineCustomParamTypes() {
    return array(
      'startCommit' => 'string',
      'endCommit'   => 'string',
    );
  }

  protected function defineReturnType() {
    return 'list<map<string, wild>>';
  }

  protected function getGitResult(ConduitAPIRequest $request) {
    $repository = $this->getRepository($request);

    // TODO: We should add `--skip` and `--max-count` so that we can paginate
    // the results.
    list($stdout) = $repository->execxLocalCommand(
      'log --pretty=format:%s %s..%s',
      '%H',
      $request->getValue('startCommit'),
      $request->getValue('endCommit'));

    $commit_hashes = phutil_split_lines($stdout, false);
    $commits = DiffusionQuery::loadCommitsByIdentifiers(
      $commit_hashes,
      $this->getDiffusionRequest());

    return array_map(
      function (PhabricatorRepositoryCommit $commit) {
        return [
          'id'         => $commit->getID(),
          'phid'       => $commit->getPHID(),
          'identifier' => $commit->getCommitIdentifier(),
          'summary'    => $commit->getCommitData()->getSummary(),
        ];
      },
      $commits);
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_UNSTABLE;
  }

  public function getAPIMethodName() {
    return 'diffusion.changes';
  }

}
