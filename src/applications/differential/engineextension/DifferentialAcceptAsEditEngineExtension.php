<?php

/**
 * Adds `accept.as` to `differential.revision.edit`: accept on behalf of only
 * the reviewers named, as the web UI's "Accept as" checkboxes do. Conduit's
 * own `accept` is a boolean, which accepts for every reviewer the caller has
 * authority over.
 *
 * It is the same accept transaction with a list for its value, so the
 * revision editor applies the web UI's rules: each reviewer named must be one
 * the caller can accept for, and only those are accepted.
 */
final class DifferentialAcceptAsEditEngineExtension
  extends PhabricatorEditEngineExtension {

  const EXTENSIONKEY = 'phlab.differential.accept-as';
  const FIELDKEY = 'accept.as';

  // Marks the transaction, so DifferentialAcceptAsEditorExtension can tell it
  // from the boolean "accept".
  const METADATA_KEY = 'phlab.accept.as';

  public function isExtensionEnabled() {
    return true;
  }

  public function getExtensionName() {
    return pht('Accept As');
  }

  public function supportsObject(
    PhabricatorEditEngine $engine,
    PhabricatorApplicationTransactionInterface $object) {
    return ($object instanceof DifferentialRevision);
  }

  public function buildCustomEditFields(
    PhabricatorEditEngine $engine,
    PhabricatorApplicationTransactionInterface $object) {

    $field = id(new PhabricatorDatasourceEditField())
      ->setKey(self::FIELDKEY)
      ->setLabel(pht('Accept As'))
      ->setDatasource(new DifferentialReviewerDatasource())
      ->setIsFormField(false)
      ->setTransactionType(
        DifferentialRevisionAcceptTransaction::TRANSACTIONTYPE)
      ->setMetadataValue(self::METADATA_KEY, true)
      ->setDescription(
        pht('Accept the revision on behalf of the reviewers named.'))
      ->setConduitDescription(
        pht(
          'Accept the revision on behalf of only these reviewers: yourself, '.
          'and any project or package reviewer you could tick under '.
          '"Accept as" in the web UI. Reviewers left out are not accepted, '.
          'so a list with only your own PHID never accepts for a group '.
          'you belong to. "accept" accepts for all of them.'))
      ->setConduitTypeDescription(pht('List of reviewer PHIDs.'))
      ->setValue(array());

    return array($field);
  }

}
