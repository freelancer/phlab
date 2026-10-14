<?php

final class DifferentialInlineSearchConduitAPIMethod
  extends DifferentialInlineConduitAPIMethod {

  public function getAPIMethodName() {
    return 'differential.inline.search';
  }

  public function getMethodDescription() {
    return pht(
      'List inline comments on a Differential revision with their side, '.
      'line range, character range, suggested edit and Done state. Returns '.
      'every published comment plus your own drafts, newest first, with '.
      'the same cursor paging as other "*.search" methods.');
  }

  protected function defineParamTypes() {
    return array(
      'revisionID' => 'required revisionid',
    ) + $this->getPagerParamTypes();
  }

  protected function defineReturnType() {
    return 'map<string, wild>';
  }

  protected function defineErrorTypes() {
    return array(
      'ERR-BAD-REVISION' => pht(
        'No such revision, or you cannot see it.'),
      'ERR-BAD-CURSOR' => pht(
        '"before" or "after" is not an inline comment you can see on this '.
        'revision.'),
    );
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();

    $revision = $this->loadRevision(
      $viewer,
      self::getRequiredTypedParameter(
        new ConduitIntParameterType(),
        $request,
        'revisionID'));

    $query = self::newVisibleInlineQuery($viewer)
      ->withRevisionPHIDs(array($revision->getPHID()));

    // The pager loads its cursor row by ID alone, and inline storage is
    // world-readable, so an ID the viewer cannot see would page fine where
    // a missing one errors. Check the cursor is a visible row first.
    foreach (array('before', 'after') as $key) {
      $cursor = $request->getValue($key);
      if ($cursor === null) {
        continue;
      }

      $visible = self::loadVisibleObject(
        id(clone $query)->withIDs(array((int)$cursor)));
      if (!$visible) {
        throw id(new ConduitException('ERR-BAD-CURSOR'))
          ->setErrorDescription(
            pht(
              'Cursor "%s" is not an inline comment you can see on '.
              'revision %d.',
              $cursor,
              $revision->getID()));
      }
    }

    $pager = $this->newPager($request);
    $comments = $query->executeWithCursorPager($pager);

    $changesets = array();
    if ($comments) {
      $changesets = id(new DifferentialChangesetQuery())
        ->setViewer($viewer)
        ->withIDs(array_unique(mpull($comments, 'getChangesetID')))
        ->execute();
      $changesets = mpull($changesets, null, 'getID');
    }

    $data = array();
    foreach ($comments as $comment) {
      $changeset = idx($changesets, $comment->getChangesetID());
      if (!$changeset) {
        continue;
      }

      $data[] = $this->buildInlineDictionary(
        $revision,
        $comment->newInlineCommentObject(),
        $changeset);
    }

    return $this->addPagerResults(
      array(
        'data' => $data,
      ),
      $pager);
  }

}
