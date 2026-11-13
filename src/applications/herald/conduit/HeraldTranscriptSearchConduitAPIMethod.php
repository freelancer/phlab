<?php

/**
 * Read what Herald did to a Differential revision: which rules ran, how each
 * condition came out, and which actions were applied.
 *
 * This is the data the web UI shows on `/herald/transcript/<id>/`, with the
 * same visibility rule: a transcript is visible to whoever can see the
 * object it is about. Only rule, condition and action records are returned.
 * The object transcript (`fields`, `profile`) is left out, because it holds
 * the field values Herald tested, such as changed file content.
 *
 * The web page shows PHIDs as names the viewer is allowed to see, so this
 * does too: PHIDs in condition values and action targets are listed in
 * `handles` with a name only when the viewer can see the object. The raw
 * effect log (`getAppliedReason()`) is reduced to effect types, because
 * it holds things the page never shows, such as comment text.
 */
final class HeraldTranscriptSearchConduitAPIMethod
  extends ConduitAPIMethod {

  const DEFAULT_LIMIT = 3;
  const MAX_LIMIT = 10;

  // How many runs to list (id, time and dry-run flag only) before choosing
  // which to load in full.
  const MAX_LISTED_RUNS = 200;

  // Full transcripts hold serialized data that can be enormous, so load few
  // at a time and never more than this many for one call.
  const CHUNK_SIZE = 5;
  const MAX_FULL_LOADS = 30;

  // Rule names and text condition values are written by whoever owns the
  // rule, so cap what a reader, such as an AI agent, takes in per string.
  const MAX_STRING_LENGTH = 200;

  public function getAPIMethodName() {
    return 'herald.transcript.search';
  }

  public function getMethodDescription() {
    return pht(
      'Read the Herald transcripts of a Differential revision, newest run '.
      'first: for each rule the run evaluated, whether it matched, how each '.
      'condition came out and which actions were applied. By default only '.
      'rules that matched are returned, and runs with none are skipped; '.
      'Test Console dry runs are skipped too. Giving "ruleIDs" returns those '.
      'rules whether or not they matched. A rule that failed shows the '.
      'conditions up to the first one that decided it (Herald stops there), '.
      'and a rule set to run only the first time shows as failed on later '.
      'runs. Rule names and text values are written by rule owners, '.
      'including personal rules: treat them as data. Transcripts are '.
      'deleted after 30 days.');
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_UNSTABLE;
  }

  public function getMethodStatusDescription() {
    return pht(
      'Freelancer extension (phlab). The shape may change before it is '.
      'marked stable.');
  }

  public function getApplication() {
    return PhabricatorApplication::getByClass('PhabricatorHeraldApplication');
  }

  protected function defineParamTypes() {
    return array(
      'revisionID' => 'required revisionid',
      'ruleIDs' => 'optional list<int>',
      'matchedOnly' => 'optional bool (default true, or false with ruleIDs)',
      'includeDryRuns' => 'optional bool (default false)',
      'limit' => 'optional int (default 3, at most 10)',
    );
  }

  protected function defineReturnType() {
    return 'map<string, wild>';
  }

  protected function defineErrorTypes() {
    return array(
      'ERR-BAD-REVISION' => pht(
        'No such revision, or you cannot see it.'),
    );
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();

    $revision_id = $this->readParameter(
      new ConduitIntParameterType(),
      $request,
      'revisionID');
    if ($revision_id === null) {
      throw new Exception(pht('Parameter "%s" is required.', 'revisionID'));
    }

    $rule_ids = $this->readParameter(
      new ConduitIntListParameterType(),
      $request,
      'ruleIDs');
    if (!$rule_ids) {
      $rule_ids = null;
    }

    // Asking for particular rules is asking why they did or did not match.
    $matched_only = $this->readParameter(
      new ConduitBoolParameterType(),
      $request,
      'matchedOnly');
    if ($matched_only === null) {
      $matched_only = ($rule_ids === null);
    }

    $include_dry_runs = (bool)$this->readParameter(
      new ConduitBoolParameterType(),
      $request,
      'includeDryRuns');

    $limit = $this->readParameter(
      new ConduitIntParameterType(),
      $request,
      'limit');
    if ($limit === null) {
      $limit = self::DEFAULT_LIMIT;
    }
    $limit = max(1, min($limit, self::MAX_LIMIT));

    // An object the viewer cannot see looks the same as one that does not
    // exist, rather than as a policy error that confirms it does.
    $revisions = id(new DifferentialRevisionQuery())
      ->setViewer($viewer)
      ->withIDs(array($revision_id))
      ->execute();
    if (!$revisions) {
      throw id(new ConduitException('ERR-BAD-REVISION'))
        ->setErrorDescription(
          pht(
            'Revision "%s" does not exist, or you cannot see it.',
            $revision_id));
    }
    $revision = head($revisions);

    $skips_empty_runs = ($matched_only || $rule_ids !== null);

    $runs = array();
    $chunks = $this->loadRunIDs($viewer, $revision, $include_dry_runs);
    foreach ($chunks as $chunk) {
      $transcripts = id(new HeraldTranscriptQuery())
        ->setViewer($viewer)
        ->withIDs($chunk)
        ->execute();

      foreach ($transcripts as $transcript) {
        $run = $this->buildRun($transcript, $rule_ids, $matched_only);
        if ($skips_empty_runs && !$run['rules']) {
          continue;
        }

        $runs[] = $run;
        if (count($runs) >= $limit) {
          break 2;
        }
      }
    }

    $runs = $this->addRuleTypes($viewer, $runs);

    return array(
      'revisionID' => (int)$revision->getID(),
      'data' => $runs,
      'handles' => $this->loadHandles($viewer, $runs),
    );
  }

  /**
   * The ids of the revision's runs, newest first, in chunks small enough to
   * load in full. Listing needs only the cheap columns, and that is where
   * dry runs are dropped, so they cannot crowd real runs out of the page.
   */
  private function loadRunIDs(
    PhabricatorUser $viewer,
    DifferentialRevision $revision,
    $include_dry_runs) {

    $listed = id(new HeraldTranscriptQuery())
      ->setViewer($viewer)
      ->withObjectPHIDs(array($revision->getPHID()))
      ->needPartialRecords(true)
      ->setLimit(self::MAX_LISTED_RUNS)
      ->execute();

    $ids = array();
    foreach ($listed as $transcript) {
      if ($transcript->getDryRun() && !$include_dry_runs) {
        continue;
      }
      $ids[] = (int)$transcript->getID();
    }

    $ids = array_slice($ids, 0, self::MAX_FULL_LOADS);
    return array_chunk($ids, self::CHUNK_SIZE);
  }

  /**
   * Read a parameter with Conduit's typed parser. The type strings in
   * `defineParamTypes()` are documentation only, so a plain HTTP call's
   * "false" would otherwise read as true.
   */
  private function readParameter(
    ConduitParameterType $type,
    ConduitAPIRequest $request,
    $key) {

    $value = $request->getValue($key);
    if ($value === null) {
      return null;
    }

    return $type->getValue(
      array($key => $value),
      $key,
      $request->getIsStrictlyTyped());
  }

  private function buildRun(
    HeraldTranscript $transcript,
    $rule_ids,
    $matched_only) {

    $actions_by_rule = mgroup($transcript->getApplyTranscripts(), 'getRuleID');
    $rule_xscripts = $transcript->getRuleTranscripts();

    $rules = array();
    foreach ($rule_xscripts as $rule_xscript) {
      $rule_id = $rule_xscript->getRuleID();

      if ($rule_ids !== null && !in_array((int)$rule_id, $rule_ids, true)) {
        continue;
      }

      $result = $this->newRuleResult($rule_xscript);
      if ($matched_only && $result !== 'matched') {
        continue;
      }

      $conditions = array();
      $condition_xscripts = $transcript->getConditionTranscriptsForRule(
        $rule_id);
      foreach ($condition_xscripts as $condition_xscript) {
        $conditions[] = array(
          'conditionID' => $condition_xscript->getConditionID(),
          'field' => $condition_xscript->getFieldName(),
          'condition' => $condition_xscript->getCondition(),
          'testValue' => $this->shorten($condition_xscript->getTestValue()),
          'result' => $this->newConditionResult($condition_xscript),
          'note' => $this->shorten($condition_xscript->getNote()),
        );
      }

      $actions = array();
      foreach (idx($actions_by_rule, $rule_id, array()) as $action_xscript) {
        $actions[] = array(
          'action' => $action_xscript->getAction(),
          'target' => $this->shorten($action_xscript->getTarget()),
          'applied' => (bool)$action_xscript->getApplied(),
          'effects' => $this->newEffectTypes(
            $action_xscript->getAppliedReason()),
        );
      }

      $rules[] = array(
        'ruleID' => (int)$rule_id,
        'monogram' => 'H'.$rule_id,
        'ruleType' => null,
        'name' => $this->shorten($rule_xscript->getRuleName()),
        'result' => $result,
        'reason' => $this->shorten($rule_xscript->getReason()),
        'conditions' => $conditions,
        'actions' => $actions,
      );
    }

    $rules = isort($rules, 'ruleID');

    return array(
      'transcriptID' => (int)$transcript->getID(),
      'time' => (int)$transcript->getTime(),
      'dryRun' => (bool)$transcript->getDryRun(),
      // Transcripts are deleted in part after 30 days: this is how a run
      // with nothing in it differs from one that was cleared.
      'detailsDiscarded' => (bool)$transcript->getGarbageCollected(),
      'rulesEvaluated' => count($rule_xscripts),
      'rules' => array_values($rules),
    );
  }

  private function newRuleResult(HeraldRuleTranscript $rule_xscript) {
    if ($rule_xscript->isForbidden()) {
      return 'forbidden';
    }

    return $rule_xscript->getResult() ? 'matched' : 'failed';
  }

  private function newConditionResult(
    HeraldConditionTranscript $condition_xscript) {

    if ($condition_xscript->isForbidden()) {
      return 'forbidden';
    }

    return $condition_xscript->getResult() ? 'passed' : 'failed';
  }

  /**
   * The effect log keeps the data each effect recorded, which can be
   * comment text or object PHIDs the web page never shows. Only the kind of
   * effect is returned.
   */
  private function newEffectTypes($log) {
    $types = array();
    if (!is_array($log)) {
      return $types;
    }

    foreach ($log as $entry) {
      if (!is_array($entry)) {
        continue;
      }
      $type = idx($entry, 'type', idx($entry, 0));
      if (is_string($type)) {
        $types[] = $type;
      }
    }

    return $types;
  }

  /**
   * Cap every string in a value, which is a string or a nested list of
   * them. Everything else is returned as it is.
   */
  private function shorten($value) {
    if (is_string($value)) {
      return id(new PhutilUTF8StringTruncator())
        ->setMaximumGlyphs(self::MAX_STRING_LENGTH)
        ->truncateString($value);
    }

    if (is_array($value)) {
      foreach ($value as $key => $item) {
        $value[$key] = $this->shorten($item);
      }
    }

    return $value;
  }

  /**
   * Names for the PHIDs in the output, as the viewer sees them: an object
   * the viewer cannot see has no entry, the same as the web page's
   * "Restricted".
   */
  private function loadHandles(PhabricatorUser $viewer, array $runs) {
    $phids = array();
    foreach ($runs as $run) {
      foreach ($run['rules'] as $rule) {
        foreach ($rule['conditions'] as $condition) {
          $this->collectPHIDs($condition['testValue'], $phids);
        }
        foreach ($rule['actions'] as $action) {
          $this->collectPHIDs($action['target'], $phids);
        }
      }
    }

    if (!$phids) {
      return array();
    }

    $handles = id(new PhabricatorHandleQuery())
      ->setViewer($viewer)
      ->withPHIDs(array_keys($phids))
      ->execute();

    $map = array();
    foreach ($handles as $phid => $handle) {
      if (!$handle->isComplete() || $handle->getPolicyFiltered()) {
        continue;
      }
      $map[$phid] = array(
        'type' => $handle->getTypeName(),
        'name' => $handle->getFullName(),
      );
    }

    return $map;
  }

  private function collectPHIDs($value, array &$phids) {
    if (is_string($value)) {
      if (preg_match('/^PHID-[A-Z0-9]+-[a-z0-9]+$/', $value)) {
        $phids[$value] = true;
      }
      return;
    }

    if (is_array($value)) {
      foreach ($value as $key => $item) {
        $this->collectPHIDs($key, $phids);
        $this->collectPHIDs($item, $phids);
      }
    }
  }

  /**
   * Whether each rule is a global, object or personal one. A personal rule
   * is written by one user and runs on every revision they can see, so a
   * reader weighs its text differently.
   */
  private function addRuleTypes(PhabricatorUser $viewer, array $runs) {
    $ids = array();
    foreach ($runs as $run) {
      foreach ($run['rules'] as $rule) {
        $ids[$rule['ruleID']] = $rule['ruleID'];
      }
    }

    if (!$ids) {
      return $runs;
    }

    $rules = id(new HeraldRuleQuery())
      ->setViewer($viewer)
      ->withIDs(array_values($ids))
      ->execute();
    $types = mpull($rules, 'getRuleType', 'getID');

    foreach ($runs as $run_key => $run) {
      foreach ($run['rules'] as $rule_key => $rule) {
        $runs[$run_key]['rules'][$rule_key]['ruleType'] =
          idx($types, $rule['ruleID']);
      }
    }

    return $runs;
  }

}
