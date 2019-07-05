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

    return [
      $this->newOption('phlab.projects.ownable-subtypes', 'list<string>', null)
        ->setSummary(pht('Ownable project subtypes.'))
        ->setDescription(
          pht(
            'Project subtypes that can be "owned" using the `%s` field.',
            $owned_projects_field->getFieldKey())),
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
    }
  }

}
