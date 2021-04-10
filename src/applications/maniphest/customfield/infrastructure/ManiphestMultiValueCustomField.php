<?php

abstract class ManiphestMultiValueCustomField
extends ManiphestCustomField
implements PhabricatorStandardCustomFieldInterface {

  private $fieldValue;
  private $dataSource;

  abstract public function getDatasourceClassName();
  abstract public function getClassFieldKey();
  abstract public function getClassFieldName();
  abstract public function getClassFieldDescription();

  public function __construct() {

    /**
     * Since 'custom' fields are not currently allowed to be configured on
     * a per-ticket-subtype basis; we need to make this object appear to
     * be one that adheres to PhabricatorStandardCustomField. To this end,
     * a PhabricatorStandardCustomFieldDatasource is created and set up as
     * proxy for this object.
     *
     * Ref: https://stackoverflow.com/q/55025688
     */
    $proxy = (new PhabricatorStandardCustomFieldDatasource())
      ->setApplicationField($this)
      ->setFieldName($this->getClassFieldName())
      ->setFieldKey($this->getClassFieldKey())
      ->setRawStandardFieldKey($this->getClassFieldKey())
      ->setFieldDescription($this->getClassFieldDescription())
      ->setFieldConfig(['datasource.class' => $this->getDatasourceClassName()]);
    $this->setProxy($proxy);

    $this->dataSource = $proxy->getDataSource();
  }

  public function getStandardCustomFieldNamespace() {
    return 'maniphest';
  }

  public function getRawStandardFieldKey() {
    return $this->getFieldKey();
  }

  public function newStorageObject() {
    return new ManiphestCustomFieldStorage();
  }

  public function getFieldValue() {
    return $this->getProxy()->getFieldValue();
  }

  public function setFieldValue($value) {
    $this->getProxy()->setFieldValue($value);
    return $this;
  }

  public function renderPropertyViewLabel() {
    return $this->getFieldName();
  }

  public function renderPropertyViewValue($handles) {
    return $this->getPrettierView($this->getFieldValue());
  }

  public function shouldAppearInApplicationSearch() {
    return false;
  }

  public function shouldAppearInPropertyView() {
    return true;
  }

  public function shouldAppearInEditView() {
    return true;
  }

  public function shouldAppearInApplicationTransactions() {
    return true;
  }

  public function shouldUseStorage() {
    return true;
  }

  public function shouldAppearInConduitDictionary() {
    return true;
  }

  public function getConduitDictionaryValue() {
    return $this->getFieldValue();
  }

  public function getValueForStorage() {
    $value = $this->getFieldValue();
    if (!$value) {
      return null;
    }
    return json_encode(array_values($value));
  }

  public function setValueFromStorage($value) {
    $result = array();
    if (!is_array($value)) {
      $value = json_decode($value, true);
      if (is_array($value)) {
        $result = array_values($value);
      } else {
        $result = [];
      }
    }
    $this->setFieldValue($result);
  }

  public function buildFieldIndexes() {
    $indexes = [];
    if ($this->getFieldValue() !== null) {
      $indexes[] = $this->newNumericIndex($this->getFieldValue());
    }
    return $indexes;
  }

  public function readValueFromRequest($request) {
    $value = $request->getArr($this->getFieldKey());
    $this->setFieldValue($value);
  }

  public function renderEditControl($handles) {
    $control = id(new AphrontFormTokenizerControl())
      ->setUser($this->getViewer())
      ->setLabel($this->getFieldName())
      ->setName($this->getFieldKey())
      ->setDatasource($this->dataSource)
      ->setValue(nonempty($this->getFieldValue(), array()));
    return $control;
  }

  public function getApplicationTransactionTitle($xaction) {
    $author_phid = $xaction->getAuthorPHID();
    if ($xaction->getOldValue() !== null) {
      $formatted_old_value =
      $this->getPrettierView(
        phutil_json_decode($xaction->getOldValue()));
    } else {
      $formatted_old_value = null;
    }

    if ($xaction->getNewValue() !== null) {
      $formatted_new_value =
      $this->getPrettierView(
        phutil_json_decode($xaction->getNewValue()));
    } else {
      $formatted_new_value = null;
    }

    if ($formatted_old_value === null) {
      return pht(
        '%s set %s to %s.',
        $xaction->renderHandleLink($author_phid),
        strtolower($this->getFieldName()),
        $formatted_new_value);
    } else if ($formatted_new_value === null) {
      return pht(
        '%s removed %s.',
        $xaction->renderHandleLink($author_phid),
        strtolower($this->getFieldName()));
    } else {
      return pht(
        '%s changed %s from %s to %s.',
        $xaction->renderHandleLink($author_phid),
        strtolower($this->getFieldName()),
        $formatted_old_value,
        $formatted_new_value);
    }
  }

  public function getApplicationTransactionTitleForFeed($xaction) {
    $author_phid = $xaction->getAuthorPHID();
    $object_phid = $xaction->getObjectPHID();

    if ($xaction->getOldValue() !== null) {
      $formatted_old_value =
      $this->getPrettierView(
        phutil_json_decode($xaction->getOldValue()));
    } else {
      $formatted_old_value = null;
    }

    if ($xaction->getNewValue() !== null) {
      $formatted_new_value =
      $this->getPrettierView(
        phutil_json_decode($xaction->getNewValue()));
    } else {
      $formatted_new_value = null;
    }

    if ($formatted_old_value === null) {
      return pht(
        '%s set %s for %s to %s.',
        $xaction->renderHandleLink($author_phid),
        strtolower($this->getFieldName()),
        $xaction->renderHandleLink($object_phid),
        $formatted_new_value);
    } else if ($formatted_new_value === null) {
      return pht(
        '%s removed %s for %s.',
        $xaction->renderHandleLink($author_phid),
        strtolower($this->getFieldName()),
        $xaction->renderHandleLink($object_phid));
    } else {
      return pht(
        '%s changed %s for %s from %s to %s.',
        $xaction->renderHandleLink($author_phid),
        strtolower($this->getFieldName()),
        $xaction->renderHandleLink($object_phid),
        $formatted_old_value,
        $formatted_new_value);
    }
  }

  public function getPrettierView($values) {
    if ($values === null) {
      return null;
    }
    $prettier_view = '';
    $data_mapping = $this->dataSource->getDataMapping();

    for ($x = 0; $x < count($values); $x++) {
      $key = $values[$x];

      // cut off 'PHID-BUGC-' from the beginning
      if (strncmp($key, 'PHID-BUGC-', 10) === 0) {
        $key = substr($key, 10);
      }

      // display if found, otherwise sortKey, otherwise $values[$x] ($key)
      if (!array_key_exists($key, $data_mapping)) {
        return $key;
      }

      $mapping = $data_mapping[$key];
      $display = $key;
      if (array_key_exists('sortKey', $mapping)) {
        $display = $mapping['sortKey'];
      }
      if (array_key_exists('display', $mapping)) {
        $display = $mapping['display'];
      }

      if ($x == 0) {
        $prettier_view = $display;
      } else {
        $prettier_view = $prettier_view.', '.$display;
      }
    }
    return $prettier_view;
  }
}
