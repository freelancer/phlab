<?php

abstract class ManiphestStaticMapDatasource
extends PhabricatorTypeaheadDatasource {

  private $dataMapping;
  private $browseTitle;
  private $placeholderText;
  private $icon;
  private $color;

  public function getGenericResultDescription(): string {
    return 'Generic description';
  }

  /**
   * List the strings which when found to be a substring of dataMapping keys,
   * should be demoted to the bottom of the display list of typeahead results
   */
  public function getNonUniqueResultKeys(): array {
    return ['_others'];
  }

  public function getDataMapping(): array {
    return $this->dataMapping;
  }

  public function setDataMapping(array $data_mapping): void {
    $this->dataMapping = $data_mapping;
  }

  public function getBrowseTitle(): string {
    return $this->browseTitle;
  }

  public function setBrowseTitle(string $browse_title): void {
    $this->browseTitle = $browse_title;
  }

  public function getPlaceholderText() {
    return $this->placeholderText;
  }

  public function setPlaceholderText(string $placeholder_text): void {
    $this->placeholderText = $placeholder_text;
  }

  public function getIcon() {
    return $this->icon;
  }

  public function setIcon(string $icon): void {
    $this->icon = $icon;
  }

  public function getColor() {
    return $this->color;
  }

  public function setColor(string $color): void {
    $this->color = $color;
  }

  public function getDatasourceApplicationClass(): string {
    return 'PhabricatorManiphestApplication';
  }

  public function loadResults(): array {
    $results = $this->buildResults();
    return $this->filterResultsAgainstTokens($results);
  }

  public function renderTokens(array $values): array {
    return $this->renderTokensFromResults($this->buildResults(), $values);
  }

  public function buildResults(): array {
    $results = array();
    $type = PhabricatorBugCategorizationFieldValuePHIDType::TYPECONST;

    foreach ($this->dataMapping as $key => $value) {

      $display_name = $key;
      if (array_key_exists('display', $value)) {
        $display_name = $value['display'];
      }

      $subtitle = $this->getGenericResultDescription();
      if (array_key_exists('description', $value)) {
        $subtitle = $value['description'];
      }

      $phid = 'PHID-'.$type.'-'.$key;

      $result = id(new PhabricatorTypeaheadResult())
        ->setName($display_name)
        ->setDisplayName($display_name)
        ->setPHID($phid)
        ->setColor($this->color)
        ->setIcon($this->icon)
        ->setUnique(true)
        ->addAttribute($subtitle);

      foreach ($this->getNonUniqueResultKeys() as $non_unique_key_substr) {
        if (strpos($key, $non_unique_key_substr) !== false) {
          $result->setUnique(false);
        }
      }
      $results[$phid] = $result;
    }
    return $results;
  }
}
