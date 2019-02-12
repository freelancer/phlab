<?php

final class ManiphestPerformanceReviewRevieweeCustomField
  extends ManiphestCustomField
  implements PhabricatorStandardCustomFieldInterface {

  public function __construct() {
    $proxy = (new PhabricatorStandardCustomFieldUsers())
      ->setApplicationField($this)
      ->setFieldName($this->getFieldName())
      ->setFieldDescription($this->getFieldDescription())
      ->setFieldConfig([
        'limit'  => 1,
        'search' => true,
      ])
      ->setFieldKey($this->getFieldKey());

    $this->setProxy($proxy);
  }

  public function getStandardCustomFieldNamespace(): string {
    return 'maniphest';
  }

  public function getFieldKey(): string {
    return 'maniphest:performance-review:reviewee';
  }

  public function getFieldName(): string {
    return pht('Reviewee');
  }

  public function getFieldDescription(): string {
    return pht('The employee under review.');
  }

  public function getApplicationTransactionTitle(PhabricatorApplicationTransaction $xaction) {
    $author_phid = $xaction->getAuthorPHID();

    $old = $this->decodeValue($xaction->getOldValue());
    $new = $this->decodeValue($xaction->getNewValue());

    $old_phid = head($old);
    $new_phid = head($new);

    if (count($old) == 0) {
      return pht(
        '%s set %s as the employee being reviewed.',
        $xaction->renderHandleLink($author_phid),
        $xaction->renderHandleLink($new_phid));
    } else if (count($new) == 0) {
      return pht(
        '%s removed %s as the employee being reviewed.',
        $xaction->renderHandleLink($author_phid),
        $xaction->renderHandleLink($old_phid));
    } else {
      return pht(
        '%s changed the employee being reviewed from %s to %s.',
        $xaction->renderHandleLink($author_phid),
        $xaction->renderHandleLink($old_phid),
        $xaction->renderHandleLink($new_phid));
    }
  }

  public function getApplicationTransactionTitleForFeed(PhabricatorApplicationTransaction $xaction) {
    $author_phid = $xaction->getAuthorPHID();
    $object_phid = $xaction->getObjectPHID();

    $old = $this->decodeValue($xaction->getOldValue());
    $new = $this->decodeValue($xaction->getNewValue());

    $old_phid = head($old);
    $new_phid = head($new);

    if (count($old) == 0) {
      return pht(
        '%s set the employee being reviewed in %s to %s.',
        $xaction->renderHandleLink($author_phid),
        $xaction->renderHandleLink($object_phid),
        $xaction->renderHandleLink($new_phid));
    } else if (count($new) == 0) {
      return pht(
        '%s removed %s from being reviewed in %s.',
        $xaction->renderHandleLink($author_phid),
        $xaction->renderHandleLink($old_phid),
        $xaction->renderHandleLink($object_phid));
    } else {
      return pht(
        '%s changed the employee being reviewed from %s to %s in %s.',
        $xaction->renderHandleLink($author_phid),
        $xaction->renderHandleLink($old_phid),
        $xaction->renderHandleLink($new_phid),
        $xaction->renderHandleLink($object_phid));
    }
  }

  /**
   * TODO: Store value as a single PHID instead of a list.
   */
  private function decodeValue($value) {
    $value = json_decode($value);
    if (!is_array($value)) {
      $value = array();
    }

    return $value;
  }

}
