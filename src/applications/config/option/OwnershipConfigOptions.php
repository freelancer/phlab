<?php

final class OwnershipConfigOptions
  extends PhabricatorApplicationConfigOptions {

  public function getName(): string {
    return pht('ownable');
  }

  public function getDescription(): string {
    return pht('Configure types ownable by projects');
  }

  public function getGroup(): string {
    return 'apps';
  }

  public function getOptions(): array {
    return [
      $this->newOption('ownable.ownableTypes', 'projects.subtypes', null)
        ->setSummary(pht('Ownable project types'))
        ->setDescription(pht('The types of projects that a project can own'))
    ];
  }

  public function getIcon(): string {
    return 'fa-cog';
  }

  public function getKey(): string {
    return 'ownable';
  }

}
