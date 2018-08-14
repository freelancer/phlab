<?php

final class PhabricatorUpPrometheusMetric extends PhabricatorPrometheusMetric {
  public function getName(): string {
    return 'up';
  }

  public function getValue(): float {
    return 1;
  }
}
