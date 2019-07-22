<?php

final class PhlabProjectDatasourceTestCase extends PhabricatorTestCase {

  protected function getPhabricatorTestCaseConfiguration(): array {
    return [
      self::PHABRICATOR_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    ];
  }

  public function testWithoutSubtypesFilter(): void {
    $user     = $this->generateNewTestUser();
    $projects = $this->createProjects($user, 5);

    $results = (new PhlabProjectDatasource())
      ->setViewer($user)
      ->loadResults();

    $this->assertEqualCanonicalizing(
      mpull($projects, 'getPHID'),
      mpull($results, 'getPHID'));
  }

  public function testWithSubtypesFilter(): void {
    $env = PhabricatorEnv::beginScopedEnv();
    $env->overrideEnvConfig('projects.subtypes', [
      [
        'key'  => 'default',
        'name' => pht('Default'),
      ],
      [
        'key'  => 'a',
        'name' => pht('Subtype A'),
      ],
      [
        'key'  => 'b',
        'name' => pht('Subtype B'),
      ],
    ]);

    $user     = $this->generateNewTestUser();
    $projects = [
      'default' => $this->createProjects($user, 5),
      'a'       => $this->createProjects($user, 5, 'a'),
      'b'       => $this->createProjects($user, 5, 'b'),
    ];

    $results = function (array $subtypes) use ($user): array {
      return (new PhlabProjectDatasource())
        ->setViewer($user)
        ->setParameters(['subtypes' => $subtypes])
        ->loadResults();
    };

    $this->assertEqualCanonicalizing(
      mpull($projects['a'], 'getPHID'),
      mpull($results(['a']), 'getPHID'));

    $this->assertEqualCanonicalizing(
      mpull($projects['b'], 'getPHID'),
      mpull($results(['b']), 'getPHID'));

    $this->assertEqualCanonicalizing(
      array_merge(
        mpull($projects['a'], 'getPHID'),
        mpull($projects['b'], 'getPHID')),
      mpull($results(['a', 'b']), 'getPHID'));
  }

  /**
   * @task assert
   */
  protected function assertEqualCanonicalizing(array $expect, array $actual, ?string $message = null): void {
    sort($expect);
    sort($actual);
    $this->assertEqual($expect, $actual, $message);
  }

  private function applyTransactions(PhabricatorApplicationTransactionInterface $object, PhabricatorUser $user, array $xactions): void {
    $editor = $object->getApplicationTransactionEditor();

    $editor
      ->setActor($user)
      ->setContentSource($this->newContentSource())
      ->setContinueOnNoEffect(true)
      ->applyTransactions($object, $xactions);
  }

  private function createProject(PhabricatorUser $user, ?string $subtype = null): PhabricatorProject {
    $project  = PhabricatorProject::initializeNewProject($user);
    $xactions = [];

    $xactions[] = (new PhabricatorProjectTransaction())
      ->setTransactionType(PhabricatorProjectNameTransaction::TRANSACTIONTYPE)
      ->setNewValue(pht('Test Project %d', mt_rand()));

    if ($subtype !== null) {
      $xactions[] = (new PhabricatorProjectTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_SUBTYPE)
        ->setNewValue($subtype);
    }

    $this->applyTransactions($project, $user, $xactions);

    return $project;
  }

  private function createProjects(PhabricatorUser $user, int $count, ?string $subtype = null): array {
    $projects = [];

    for ($ii = 0; $ii < $count; $ii++) {
      $projects[] = $this->createProject($user, $subtype);
    }

    return $projects;
  }

}
