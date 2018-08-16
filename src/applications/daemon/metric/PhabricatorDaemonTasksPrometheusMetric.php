<?php

final class PhabricatorDaemonTasksPrometheusMetric extends PhabricatorPrometheusMetric {

  public function getName(): string {
    return 'daemon_tasks';
  }

  public function getLabels(): array {
    return ['class', 'status'];
  }

  public function getValues(): array {
    $values = [];

    // TODO: We should also expose counts for triggers, bulk jobs and failed tasks.
    $task_statuses = [
      'completed' => [
        new PhabricatorWorkerArchiveTask(),
        [],
      ],
      'expired'   => [
        new PhabricatorWorkerActiveTask(),
        ['leaseExpires < UNIX_TIMESTAMP()'],
      ],
      'leased'    => [
        new PhabricatorWorkerActiveTask(),
        ['leaseOwner IS NOT NULL', 'leaseExpires >= UNIX_TIMESTAMP()'],
      ],
      'queued'    => [
        new PhabricatorWorkerActiveTask(),
        ['leaseOwner IS NULL'],
      ],
    ];

    foreach ($task_statuses as $status => $params) {
      list($table, $where) = $params;

      foreach ($this->getTaskCounts($table, $where) as $class => $count) {
        $values[] = [
          $count,
          [
            'class'  => $class,
            'status' => $status,
          ],
        ];
      }
    }

    return $values;
  }

  private function getTaskCounts(PhabricatorWorkerDAO $table, array $conditions = []): array {
    $conn = $table->establishConnection('r');

    // If `$conditions` is empty, add a dummy condition.
    if (count($conditions) === 0) {
      $conditions[] = '1 = 1';
    }

    $where_clause = implode(
      ' AND ',
      array_map(
        function (string $condition) use ($conn): string {
          return qsprintf($conn, '(%s)', $condition);
        },
        $conditions));

    $tasks = queryfx_all(
      $conn,
      'SELECT taskClass, COUNT(*) AS count FROM %T WHERE %Q GROUP BY taskClass',
      $table->getTableName(),
      $where_clause);
    return ipull($tasks, 'count', 'taskClass');
  }

}
