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
    // TODO: We should probably use APC-backed storage.
    $adapter        = new InMemoryStorage();
    $this->registry = new CollectorRegistry($adapter);

    $this->registry
      ->registerGauge('phabricator', 'up', null)
      ->set(1);

    parent::willBeginExecution();
  }

  public function processRequest(): AphrontResponse {
    $renderer = new RenderTextFormat();
    $content  = $renderer->render($this->registry->getMetricFamilySamples());

    return (new AphrontPlainTextResponse())
      ->setContent($content);
  }

}
