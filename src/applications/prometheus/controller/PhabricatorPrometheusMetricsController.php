<?php

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory as InMemoryStorage;

/**
 * @phutil-external-symbol class CollectorRegistry
 * @phutil-external-symbol class InMemoryStorage
 * @phutil-external-symbol class RenderTextFormat
 */
final class PhabricatorPrometheusMetricsController extends PhabricatorController {

  private $registry;

  public function shouldRequireLogin(): bool {
    return false;
  }

  public function willBeginExecution(): void {
    $this->loadMetrics();
    parent::willBeginExecution();
  }

  public function processRequest(): AphrontResponse {
    $renderer = new RenderTextFormat();
    $content  = $renderer->render($this->registry->getMetricFamilySamples());

    return (new AphrontPlainTextResponse())
      ->setContent($content);
  }

  private function loadMetrics(): void {
    // TODO: We should probably use APC-backed storage.
    $adapter  = new InMemoryStorage();
    $registry = new CollectorRegistry($adapter);
    $metrics  = PhabricatorPrometheusMetric::getAllMetrics();

    foreach ($metrics as $metric) {
      $metric->register($registry);
    }

    $this->registry = $registry;
  }

}
