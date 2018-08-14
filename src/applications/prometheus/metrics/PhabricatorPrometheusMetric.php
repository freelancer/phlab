<?php

use Prometheus\CollectorRegistry;

/**
 * @phutil-external-symbol class CollectorRegistry
 */
abstract class PhabricatorPrometheusMetric extends Phobject {
  const METRIC_NAMESPACE = 'phabricator';

  abstract public function getName(): string;
  abstract public function getValue(): float;

  public function getHelp(): ?string {
    return null;
  }

  public function getLabels(): array {
    return [];
  }

  final public function register(CollectorRegistry $registry): void {
    $gauge = $registry->registerGauge(
      self::METRIC_NAMESPACE,
      $this->getName(),
      $this->getHelp(),
      $this->getLabels());
    $gauge->set($this->getValue());
  }

  final public static function getAllMetrics(): array {
    return (new PhutilClassMapQuery())
      ->setAncestorClass(__CLASS__)
      ->setUniqueMethod('getName')
      ->execute();
  }
}
