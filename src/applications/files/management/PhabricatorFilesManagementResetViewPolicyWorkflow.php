<?php

final class PhabricatorFilesManagementResetViewPolicyWorkflow
  extends PhabricatorFilesManagementWorkflow {

  protected function didConstruct(): void {
    $this
      ->setName('reset-view-policy')
      ->setSynopsis(pht('Reset file(s) to the specified view policy.'))
      ->setArguments([
        [
          'name' => 'all',
          'help' => pht('Reset the view policy on all files.'),
        ],
        [
          'name' => 'dry-run',
          'help' => pht('Show planned writes but do not perform them.'),
        ],
        [
          'name'  => 'from',
          'param' => 'from',
          'help'  => pht('The previous view policy.'),
        ],
        [
          'name'  => 'to',
          'param' => 'to',
          'help'  => pht('The new view policy.'),
        ],
        [
          'name'     => 'names',
          'wildcard' => true,
        ],
      ]);
  }

  public function execute(PhutilArgumentParser $args): int {
    $iterator = $this->buildIterator($args);
    if (!$iterator) {
      throw new PhutilArgumentUsageException(
        pht(
          'Either specify a list of files or specify `%s` '.
          'to reset the view policy on all files.',
          '--all'));
    }

    $from_policy = $this->validatePolicy($args, 'from');
    $to_policy   = $this->validatePolicy($args, 'to');
    $is_dry_run  = $args->getArg('dry-run');

    foreach ($iterator as $file) {
      $view_policy = $file->getViewPolicy();

      if ($file->isBuiltin()) {
        $this->logInfo(
          pht('SKIP'),
          pht('%s is a built-in file.', $file->getMonogram()));
        continue;
      }

      if ($view_policy !== $from_policy) {
        $this->logInfo(
          pht('SKIP'),
          pht('%s has a custom view policy.', $file->getMonogram()));
        continue;
      }

      if ($is_dry_run) {
        $this->logOkay(
          pht('DRY RUN'),
          pht(
            'View policy for %s would be modified ("%s" -> "%s").',
            $file->getMonogram(),
            $view_policy,
            $to_policy));
        continue;
      }

      try {
        $file->setViewPolicy($to_policy);
        $file->save();

        $this->logOkay(
          pht('MODIFIED'),
          pht('View policy for %s has been reset.', $file->getMonogram()));
      } catch (Exception $ex) {
        $this->logWarn(
          pht('WARN'),
          pht(
            'Failed to update view policy for %s: %s',
            $file->getMonogram(),
            $ex->getMessage()));
      }
    }

    return 0;
  }

  /**
   * Validate a policy identifier.
   *
   * A valid policy identifier is a PHID corresponding to a `PhabricatorPolicy`
   * or one of the following constants:
   *
   *   - `PhabricatorPolicies::POLICY_PUBLIC`
   *   - `PhabricatorPolicies::POLICY_USER`
   *   - `PhabricatorPolicies::POLICY_ADMIN`
   *   - `PhabricatorPolicies::POLICY_NOONE`
   *
   * @param  PhutilArgumentParser
   * @param  string
   * @return string
   */
  private function validatePolicy(PhutilArgumentParser $args, string $arg): string {
    $policy_id = $args->getArg($arg);

    if ($policy_id === null) {
      throw new PhutilArgumentUsageException(
        pht(
          'You must specify a policy with `%s`.',
          '--'.$arg));
    }

    switch ($policy_id) {
      case PhabricatorPolicies::POLICY_PUBLIC:
      case PhabricatorPolicies::POLICY_USER:
      case PhabricatorPolicies::POLICY_ADMIN:
      case PhabricatorPolicies::POLICY_NOONE:
        return $policy_id;

      default:
        $policy = (new PhabricatorPolicyQuery())
          ->setViewer($this->getViewer())
          ->withPHIDs([$policy_id])
          ->executeOne();

        if ($policy === null) {
          throw new PhutilArgumentUsageException(
            pht(
              '%s does not refer to a valid policy.',
              $policy_id));
        }

        return $policy_id;
    }
  }

}
