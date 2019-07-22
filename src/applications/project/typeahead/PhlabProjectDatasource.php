<?php

/**
 * This class is intended to be a drop-in replacement for
 * @{class:PhabricatorProjectDatasource} which also allows filtering by
 * project subtypes.
 *
 * TODO: We possibly should also proxy a bunch of other methods, such as
 * @{method:getDatasourceFunctions}.
 */
final class PhlabProjectDatasource extends PhabricatorTypeaheadDatasource {

  private $proxied;

  public function __construct() {
    $this->proxied = new PhabricatorProjectDatasource();
  }

  public function __clone() {
    $this->proxied = clone $this->proxied;
  }

  public function setLimit($limit) {
    $this->proxied->setLimit($limit);
    return $this;
  }

  public function getLimit(): ?int {
    return $this->proxied->getLimit();
  }

  public function setOffset($offset) {
    $this->proxied->setOffset($offset);
    return $this;
  }

  public function getOffset(): ?int {
    return $this->proxied->getOffset();
  }

  public function setViewer(PhabricatorUser $viewer) {
    $this->proxied->setViewer($viewer);
    return $this;
  }

  public function getViewer(): ?PhabricatorUser {
    return $this->proxied->getViewer();
  }

  public function setRawQuery($raw_query) {
    $this->proxied->setRawQuery($raw_query);
    return $this;
  }

  public function getRawQuery(): ?string {
    return $this->proxied->getRawQuery();
  }

  public function setQuery($query) {
    $this->proxied->setQuery($query);
    return $this;
  }

  public function getQuery(): ?string {
    return $this->proxied->getQuery();
  }

  /**
   * @todo This method should be proxied to @{class:PhabricatorProjectDatasource}.
   */
  public function setParameters(array $params) {
    return parent::setParameters($params);
  }

  /**
   * @todo This method should be proxied to @{class:PhabricatorProjectDatasource}.
   */
  public function getParameters(): array {
    return parent::getParameters();
  }

  /**
   * @todo This method should be proxied to @{class:PhabricatorProjectDatasource}.
   */
  public function getParameter($name, $default = null) {
    return parent::getParameter($name, $default);
  }

  public function setIsBrowse($is_browse) {
    $this->proxied->setIsBrowse($is_browse);
    return $this;
  }

  public function getIsBrowse(): ?bool {
    return $this->proxied->getIsBrowse();
  }

  public function setPhase($phase) {
    $this->proxied->setPhase($phase);
    return $this;
  }

  public function getPhase(): string {
    return $this->proxied->getPhase();
  }

  public function getPlaceholderText(): string {
    return $this->proxied->getPlaceholderText();
  }

  public function getBrowseTitle(): string {
    return $this->proxied->getBrowseTitle();
  }

  public function getDatasourceApplicationClass(): string {
    return $this->proxied->getDatasourceApplicationClass();
  }

  public function loadResults(): array {
    $results  = $this->proxied->loadResults();
    $subtypes = $this->getParameter('subtypes');

    // If the `subtypes` parameter isn't set then we don't need to perform any
    // additional filtering.
    if ($subtypes === null) {
      return $results;
    }

    $projects = (new PhabricatorProjectQuery())
      ->setViewer($this->getViewer())
      ->withPHIDs(mpull($results, 'getPHID'))
      ->execute();
    $projects = mpull($projects, null, 'getPHID');

    return array_filter(
      $results,
      function (PhabricatorTypeaheadResult $result) use ($projects, $subtypes): bool {
        $project = $projects[$result->getPHID()];
        $subtype = $project->getSubtype();

        return in_array($subtype, $subtypes);
      });
  }

  public function isBrowsable(): bool {
    return $this->proxied->isBrowsable();
  }

}
