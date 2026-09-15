<?php

final class PhlabConfigOptions extends PhabricatorApplicationConfigOptions {

  public function getName(): string {
    return pht('Phlab');
  }

  public function getDescription(): string {
    return pht('Configure Phlab.');
  }

  public function getGroup(): string {
    return 'apps';
  }

  public function getOptions(): array {
    $owned_projects_field = new PhabricatorOwnedProjectsCustomField();
    $component_projects_field = new ManiphestComponentProjectsCustomField();

    return [
      $this->newOption('phlab.projects.ownable-subtypes', 'list<string>', [])
        ->setSummary(pht('Ownable project subtypes.'))
        ->setDescription(
          pht(
            'Project subtypes that can be "owned" using the `%s` field.',
            $owned_projects_field->getFieldKey())),
      $this->newOption('phlab.projects.component-subtypes', 'list<string>', ['component'])
        ->setSummary(pht('Component project subtypes.'))
        ->setDescription(
          pht(
            'Project subtypes that can be selected as components using the `%s` field.',
            $component_projects_field->getFieldKey())),
      $this->newOption('phlab.maniphest.substatus', 'wild', [])
        ->setSummary(pht('Maniphest Substatus field definition.'))
        ->setDescription(
          pht(
            'Definition for the Maniphest Substatus field: a `name` and a '.
            'map of `options`.')),
      $this->newOption('phlab.maniphest.status-notes', 'wild', [])
        ->setSummary(pht('Maniphest Status Notes field definition.'))
        ->setDescription(
          pht(
            'Definition for the Maniphest Status Notes field: a `name` and '.
            'an optional `placeholder`.')),
    ];
  }

  public function getIcon(): string {
    return 'fa-cog';
  }

  public function getKey(): string {
    return 'phlab';
  }

  protected function didValidateOption(PhabricatorConfigOption $option, $value): void {
    switch ($option->getKey()) {
      case 'phlab.projects.ownable-subtypes':
        $subtype_map = (new PhabricatorProject())->newEditEngineSubtypeMap();

        $config_option = (new PhabricatorConfigOption())
          ->setKey($option->getKey())
          ->setEnumOptions($subtype_map->getSubtypes());
        $config_type = new PhabricatorEnumConfigType();

        foreach ($value as $subtype) {
          $config_type->validateStoredValue($config_option, $subtype);
        }

        break;
      case 'phlab.projects.component-subtypes':
        $subtype_map = (new PhabricatorProject())->newEditEngineSubtypeMap();

        $config_option = (new PhabricatorConfigOption())
          ->setKey($option->getKey())
          ->setEnumOptions($subtype_map->getSubtypes());
        $config_type = new PhabricatorEnumConfigType();

        foreach ($value as $subtype) {
          $config_type->validateStoredValue($config_option, $subtype);
        }

        break;
      case 'phlab.maniphest.substatus':
        if (!$value) {
          break;
        }

        PhutilTypeSpec::checkMap(
          $value,
          [
            'name' => 'optional string',
            'options' => 'map<string, wild>',
          ]);

        break;
      case 'phlab.maniphest.status-notes':
        if (!$value) {
          break;
        }

        PhutilTypeSpec::checkMap(
          $value,
          [
            'name' => 'optional string',
            'placeholder' => 'optional string',
          ]);

        break;
    }
  }

}
