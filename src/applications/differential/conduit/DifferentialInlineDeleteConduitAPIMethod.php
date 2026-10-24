<?php

final class DifferentialInlineDeleteConduitAPIMethod
  extends DifferentialInlineConduitAPIMethod {

  public function getAPIMethodName() {
    return 'differential.inline.delete';
  }

  public function getMethodDescription() {
    return pht(
      'Delete one of your draft inline comments, as "Delete" on it in the '.
      'web UI does. Only the comment author can, and only while it is a '.
      'draft; published comments cannot be deleted.');
  }

  protected function defineParamTypes() {
    return array(
      'inlineID' => 'required int',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function defineErrorTypes() {
    return array(
      'ERR-BAD-INLINE' => pht(
        'No such inline comment, or you cannot see it. A deleted draft is '.
        'no longer visible, so deleting it again fails this way.'),
      'ERR-NOT-EDITABLE' => pht(
        'Only the comment author can delete it, and only while it is a '.
        'draft.'),
    );
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();

    $inline_id = self::getRequiredTypedParameter(
      new ConduitIntParameterType(),
      $request,
      'inlineID');

    list($revision, , $inline) = self::loadVisibleInline(
      $viewer,
      $inline_id);
    self::assertCanChangeDraft($viewer, $inline);

    $inline->setIsDeleted(1);
    self::saveDraftChange($viewer, $revision, $inline);

    return array(
      'id' => (int)$inline->getID(),
      'revisionID' => (int)$revision->getID(),
      'isDeleted' => true,
    );
  }

}
