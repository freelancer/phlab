<?php

final class PhabricatorPrometheusApplication extends PhabricatorApplication {

  public function getName(): string {
    return pht('Prometheus');
  }

  public function isUnlisted(): bool {
    return true;
  }

  public function canUninstall(): bool {
    return false;
  }

  public function getBaseURI(): string {
    return '/prometheus/';
  }

  public function getIcon(): string {
    return 'fa-heartbeat';
  }

  public function getApplicationGroup(): string {
    return self::GROUP_ADMIN;
  }

  public function getTitleGlyph(): string {
    return "\xE2\x99\xA5";
  }

  public function getRoutes(): array {
    return [
      '/prometheus/' => [
        'metrics' => PhabricatorPrometheusMetricsController::class,
      ],
    ];
  }

}
