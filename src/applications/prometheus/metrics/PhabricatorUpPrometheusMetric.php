<?php

final class PhabricatorUpPrometheusMetric extends PhabricatorPrometheusMetric {
  public function getName(): string {
    return 'up';
  }

  public function getValues(): array {
    return [1];
  }
}
