<?php

/**
 * Refuses an `accept.as` with any other review action in the same edit, as
 * the web UI allows one action per comment. The revision editor would apply
 * both, so an "accept" alongside would accept for every group after all.
 */
final class DifferentialAcceptAsEditorExtension
  extends PhabricatorEditorExtension {

  const EXTENSIONKEY = 'phlab.differential.accept-as';

  public function getExtensionName() {
    return pht('Accept As');
  }

  public function supportsObject(
    PhabricatorApplicationTransactionEditor $editor,
    PhabricatorApplicationTransactionInterface $object) {
    return ($object instanceof DifferentialRevision);
  }

  public function validateTransactions($object, array $xactions) {
    $metadata_key = DifferentialAcceptAsEditEngineExtension::METADATA_KEY;

    $action_types = array();
    foreach (DifferentialRevisionActionTransaction::loadAllActions() as $a) {
      $action_types[$a->getTransactionTypeConstant()] = true;
    }

    $accept_as = array();
    $actions = 0;
    foreach ($xactions as $xaction) {
      if (!isset($action_types[$xaction->getTransactionType()])) {
        continue;
      }

      $actions++;
      if ($xaction->getMetadataValue($metadata_key)) {
        $accept_as[] = $xaction;
      }
    }

    if (!$accept_as || $actions < 2) {
      return array();
    }

    $errors = array();
    foreach ($accept_as as $xaction) {
      $errors[] = $this->newInvalidTransactionError(
        $xaction,
        pht(
          '"accept.as" must be the only review action in an edit; this edit '.
          'has %s.',
          new PhutilNumber($actions)));
    }

    return $errors;
  }

}
