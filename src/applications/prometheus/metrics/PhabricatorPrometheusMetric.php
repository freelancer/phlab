<?php

use Prometheus\CollectorRegistry;

/**
 * @phutil-external-symbol class CollectorRegistry
 */
abstract class PhabricatorPrometheusMetric extends Phobject {
  const METRIC_NAMESPACE = 'phabricator';

  abstract public function getName(): string;
  abstract public function getValues();

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

    foreach ($this->getValues() as $data) {
      if (is_array($data)) {
        list($value, $labels) = $data;
      } else {
        $value  = $data;
        $labels = [];
      }

      if (count($this->getLabels()) !== count($labels)) {
        throw new Exception(
          pht(
            'Expected value for "%s" metric to have %d labels.',
            $this->getName(),
            count($this->getLabels())));
      }

      $gauge->set($value, $labels);
    }
  }

  final public static function getAllMetrics(): array {
    return (new PhutilClassMapQuery())
      ->setAncestorClass(__CLASS__)
      ->setUniqueMethod('getName')
      ->execute();
  }
}
