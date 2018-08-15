<?php

final class PhabricatorSetupIssuesPrometheusMetric extends PhabricatorPrometheusMetric {

  public function getName(): string {
    return 'setup_issues';
  }

  public function getHelp(): ?string {
    return pht('The number of setup issues for each setup check.');
  }

  public function getLabels(): array {
    return ['class', 'severity'];
  }

  public function getValues(): array {
    $checks = PhabricatorSetupCheck::loadAllChecks();

    return array_mergev(
      array_map(
        function (PhabricatorSetupCheck $check): array {
          $check->runSetupChecks();

          $class          = get_class($check);
          $issues         = $check->getIssues();
          $fatal_issues   = mfilter($issues, 'getIsFatal');
          $ignored_issues = mfilter($issues, 'getIsIgnored');

          return [
            [
              count($fatal_issues),
              [
                'class'    => $class,
                'severity' => 'fatal',
              ],
            ],
            [
              count($ignored_issues),
              [
                'class'    => $class,
                'severity' => 'ignored',
              ],
            ],
            [
              count($issues) - count($fatal_issues) - count($ignored_issues),
              [
                'class'    => $class,
                'severity' => 'warning',
              ],
            ],
          ];
        },
        $checks));
  }

}
