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

    $task_statuses = [
      'active'   => new PhabricatorWorkerActiveTask(),
      'archived' => new PhabricatorWorkerArchiveTask(),
    ];

    foreach ($task_statuses as $status => $task) {
      foreach ($this->getTaskCounts($task) as $class => $count) {
        $values[] = [$count, [$class, $status]];
      }
    }

    return $values;
  }

  private function getTaskCounts(PhabricatorWorkerDAO $table): array {
    $tasks = queryfx_all(
      $table->establishConnection('r'),
      'SELECT taskClass, COUNT(*) AS count FROM %T GROUP BY taskClass',
      $table->getTableName());
    return ipull($tasks, 'count', 'taskClass');
  }

}
