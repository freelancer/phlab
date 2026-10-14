<?php

/**
 * Runs `arc unit` through `bin/unit`, in a container with a test database and
 * the Arcanist and Phabricator versions that production runs.
 *
 * In that container, `arc unit` runs this engine too, and hands over to the
 * `.arcunit` engines. It writes its results inside the working copy for this
 * engine to read: the container mounts only the repository, so a system temp
 * path is invisible to it.
 */
final class PhlabDockerUnitTestEngine extends ArcanistUnitTestEngine {

  const REPORT_PREFIX = '.arcunit-';

  /**
   * Set in the containers that can run the tests themselves: the one
   * `bin/unit` starts, and those of the development environment.
   */
  const IN_CONTAINER = 'PHLAB_UNIT_IN_CONTAINER';

  protected function supportsRunAllTests(): bool {
    return true;
  }

  /**
   * The results show as they run, in the container or from the `.arcunit`
   * engines.
   */
  public function shouldEchoTestResults(): bool {
    return false;
  }

  public function run(): array {
    if (getenv(self::IN_CONTAINER)) {
      return $this->runHere();
    }

    $paths = array();
    if (!$this->getRunAllTests()) {
      $paths = $this->selectPaths();
      if (!$paths) {
        throw new ArcanistNoEffectException(pht('No tests to run.'));
      }
    }

    $root = $this->getWorkingCopy()->getProjectRoot();

    // Unique per run: `arc unit` and `arc diff` in two terminals would
    // otherwise share the file, and each could read the other's.
    $report = self::REPORT_PREFIX.getmypid().'-'.
      Filesystem::readRandomCharacters(8).'.json';

    // Whatever the outcome, nothing is left in the working copy.
    try {
      return $this->runInContainer($root, $paths, $report);
    } finally {
      Filesystem::remove($root.'/'.$report);
    }
  }

  private function runHere(): array {
    $engine = id(new ArcanistConfigurationDrivenUnitTestEngine())
      ->setWorkingCopy($this->getWorkingCopy())
      ->setConfigurationManager($this->getConfigurationManager())
      ->setEnableCoverage($this->getEnableCoverage())
      ->setRenderer($this->renderer);

    if ($this->getRunAllTests()) {
      $engine->setRunAllTests(true);
    } else {
      $engine->setPaths($this->getPaths());
    }

    return $engine->run();
  }

  /**
   * The paths the `.arcunit` engines would test. A diff of nothing else (docs,
   * configuration) runs nothing, so it needs no Docker. Deleted files are left
   * out: `arc unit` rejects a path that does not exist.
   */
  private function selectPaths(): array {
    $root = $this->getWorkingCopy()->getProjectRoot();

    $paths = array();
    foreach ($this->getPaths() as $path) {
      if (preg_match('(\.php\z)', $path) &&
          Filesystem::pathExists($root.'/'.$path)) {
        $paths[] = $path;
      }
    }

    return $paths;
  }

  private function runInContainer($root, array $paths, $report): array {
    $args = $paths ? $paths : array('--everything');

    // Passthru, so results show as they run, and a first run shows its setup.
    // To stderr, as `arc unit --output json` writes its results to stdout.
    $err = id(new PhutilExecPassthru('%s %Ls 1>&2', $root.'/bin/unit', $args))
      ->setCWD($root)
      ->setEnv(array('PHLAB_UNIT_REPORT' => $report))
      ->resolve();

    $path = $root.'/'.$report;
    $data = Filesystem::pathExists($path) ? Filesystem::readFile($path) : '';

    // What `arc` prints in place of results when no test covers the paths.
    $no_effect = pht('No tests to run.');
    if (!$err && $data === $no_effect) {
      throw new ArcanistNoEffectException($no_effect);
    }

    try {
      $dictionaries = phutil_json_decode($data);
    } catch (PhutilJSONParserException $ex) {
      throw new Exception(
        pht(
          '%s exited %d without reporting results; see its output above.',
          'bin/unit',
          $err));
    }

    // An empty report would otherwise read as a pass.
    if (!$dictionaries) {
      throw new Exception(
        pht('%s reported no results; no tests ran.', 'bin/unit'));
    }

    $results = array();
    foreach ($dictionaries as $dictionary) {
      $results[] = id(new ArcanistUnitTestResult())
        ->setNamespace(idx($dictionary, 'namespace'))
        ->setName(idx($dictionary, 'name'))
        ->setLink(idx($dictionary, 'link'))
        ->setResult(idx($dictionary, 'result'))
        ->setDuration(idx($dictionary, 'duration'))
        ->setExtraData(idx($dictionary, 'extra'))
        ->setUserData(idx($dictionary, 'userData'))
        ->setCoverage(idx($dictionary, 'coverage'));
    }

    return $results;
  }

}
