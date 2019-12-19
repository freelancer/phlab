<?php

final class PagerDutyConfigOptions
  extends PhabricatorApplicationConfigOptions {

  public function getName(): string {
    return pht('Integration with Pagerduty');
  }

  public function getDescription(): string {
    return pht('Configure Pagerduty integration.');
  }

  public function getGroup(): string {
    return 'core';
  }

  public function getOptions(): array {
    return [
      $this->newOption('pagerduty.integration_keys', 'list<string>', null)
        ->setSummary(pht('Pagerduty service integration keys'))
        ->setDescription(
          pht('Pagerduty service integration keys used'.
            ' to authenticate event requests to Pagerduty.')),

      $this->newOption('pagerduty.server', 'string', null)
        ->addExample('freelancer.pagerduty.com', pht('Demo Server'))
        ->setSummary(pht('Pagerduty Server URL'))
        ->setDescription(pht('Base URL of Pagerduty Server.')),
    ];
  }

  public function getIcon(): string {
    return 'fa-bell';
  }

  protected function didValidateOption(
    PhabricatorConfigOption $option, $value): void {
    switch ($option->getKey()) {
      case 'pagerduty.server':
        if (!preg_match('(^https?://)', $value)) {
          throw new PhabricatorConfigValidationException(
            pht(
              "Configuration option '%s' is not a valid URI.",
              $value));
        }

        break;
    }
  }

  public function getKey(): string {
    return 'pagerduty';
  }

}
