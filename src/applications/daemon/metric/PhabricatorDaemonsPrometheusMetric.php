<?php

final class PhabricatorDaemonsPrometheusMetric extends PhabricatorPrometheusMetric {

  public function getName(): string {
    return 'daemons';
  }

  public function getLabels(): array {
    return ['host', 'class', 'status'];
  }

  public function getValues(): array {
    $daemon_log = new PhabricatorDaemonLog();
    $daemons = queryfx_all(
      $daemon_log->establishConnection('r'),
      'SELECT host, daemon AS class, status, COUNT(*) AS count FROM %T GROUP BY host, daemon, status',
      $daemon_log->getTableName());

    return array_map(
      function (array $daemon): array {
        return [
          $daemon['count'],
          [
            $daemon['host'],
            $daemon['class'],
            $daemon['status'],
          ],
        ];
      },
      $daemons);
  }

}
