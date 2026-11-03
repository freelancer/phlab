<?php

/**
 * Edit a published comment through Conduit, as the timeline's "Edit Comment"
 * does (`PhabricatorApplicationTransactionCommentEditController`).
 *
 * It works on any object with a transaction timeline (tasks, revisions and
 * the rest), and applies the edit through the same
 * `PhabricatorApplicationTransactionCommentEditor`, so the result is the
 * same new comment version the web UI saves.
 */
final class TransactionCommentEditConduitAPIMethod
  extends ConduitAPIMethod {

  public function getAPIMethodName() {
    return 'transaction.comment.edit';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_UNSTABLE;
  }

  public function getMethodStatusDescription() {
    return pht(
      'Freelancer extension (phlab). The shape may change before it is '.
      'marked stable.');
  }

  public function getMethodDescription() {
    return pht(
      'Replace the text of one of your published comments, as "Edit '.
      'Comment" does in the web UI. This saves a new version of the '.
      'comment; earlier versions stay in its edit history.'.
      "\n\n".
      'Name the object ("T123", "D456" or a PHID) and the comment\'s '.
      'transaction PHID, as "transaction.search" returns it. Only the '.
      'comment\'s author can edit it, and only a regular comment: not an '.
      'inline comment (use "differential.inline.edit" on drafts), not a '.
      'removed comment, not a comment signed with MFA, and not on a locked '.
      'conversation.');
  }

  protected function defineParamTypes() {
    return array(
      'objectIdentifier' => 'required phid|string',
      'transactionPHID' => 'required phid',
      'content' => 'required string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function defineErrorTypes() {
    return array(
      'ERR-BAD-OBJECT' => pht(
        'No such object, you cannot see it, or it has no transactions.'),
      'ERR-BAD-TRANSACTION' => pht(
        'No such transaction on that object, or you cannot see it.'),
      'ERR-NOT-EDITABLE' => pht(
        'The transaction is not a regular comment, its comment was removed, '.
        'or you are not its author.'),
      'ERR-LOCKED' => pht(
        'The conversation is locked, so its comments cannot be edited.'),
      'ERR-MFA' => pht(
        'The comment was signed with MFA, so an edit must be signed with '.
        'MFA too, which only the web UI can do.'),
      'ERR-EMPTY-COMMENT' => pht(
        'Provide non-empty "content". To delete a comment, use the web UI.'),
    );
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();

    $object_name = self::getRequiredString($request, 'objectIdentifier');
    $xaction_phid = self::getRequiredString($request, 'transactionPHID');
    $content = self::getRequiredString($request, 'content');

    // The web UI saves an emptied comment as deleted. That is a different
    // operation from an edit, so it is not done by accident here.
    if (!strlen(trim($content))) {
      throw new ConduitException('ERR-EMPTY-COMMENT');
    }

    $object = self::loadObject($viewer, $object_name);
    $xaction = self::loadTransaction($viewer, $object, $xaction_phid);
    self::assertCanEdit($viewer, $object, $xaction);

    $comment = $xaction->getApplicationTransactionCommentObject()
      ->setContent($content);

    // The editor asks the web request for MFA when editing an existing
    // comment, and fails without one. It only ever needs MFA to remove a
    // comment or to edit one signed with MFA, and `assertCanEdit()` has
    // refused both, so its check is skipped as it is for a comment saved
    // with a new transaction.
    id(new PhabricatorApplicationTransactionCommentEditor())
      ->setActor($viewer)
      ->setContentSource($request->newContentSource())
      ->setIsNewComment(true)
      ->applyEdit($xaction, $comment);

    return self::buildResult($object, $xaction, $comment);
  }

  private static function getRequiredString(
    ConduitAPIRequest $request,
    $key) {

    $value = $request->getValue($key);
    if ($value === null) {
      throw new Exception(pht('Parameter "%s" is required.', $key));
    }

    return id(new ConduitStringParameterType())->getValue(
      array($key => $value),
      $key,
      $request->getIsStrictlyTyped());
  }

  private static function loadObject(PhabricatorUser $viewer, $object_name) {
    // An object the viewer cannot see fails like one that does not exist,
    // instead of as a policy exception that confirms it exists.
    $object = head(
      id(new PhabricatorObjectQuery())
        ->setViewer($viewer)
        ->withNames(array($object_name))
        ->execute());

    if (!($object instanceof PhabricatorApplicationTransactionInterface)) {
      throw id(new ConduitException('ERR-BAD-OBJECT'))
        ->setErrorDescription(
          pht(
            'Object "%s" does not exist, you cannot see it, or it has no '.
            'transactions.',
            $object_name));
    }

    return $object;
  }

  private static function loadTransaction(
    PhabricatorUser $viewer,
    PhabricatorApplicationTransactionInterface $object,
    $xaction_phid) {

    $query = PhabricatorApplicationTransactionQuery::newQueryForObject(
      $object);

    // Constrained to the object, so a transaction PHID from another object
    // is refused rather than edited.
    $xaction = null;
    if ($query) {
      $xaction = head(
        $query
          ->setViewer($viewer)
          ->needHandles(false)
          ->withObjectPHIDs(array($object->getPHID()))
          ->withPHIDs(array($xaction_phid))
          ->execute());
    }

    if (!$xaction) {
      throw id(new ConduitException('ERR-BAD-TRANSACTION'))
        ->setErrorDescription(
          pht(
            'Transaction "%s" does not exist on "%s", or you cannot see it.',
            $xaction_phid,
            $object->getPHID()));
    }

    return $xaction;
  }

  /**
   * The web UI's rules: the timeline offers "Edit Comment" to the comment's
   * author (`PhabricatorApplicationTransactionView`), and the edit
   * controller and editor refuse a removed comment, a locked conversation
   * and, without MFA, a comment signed with MFA.
   */
  private static function assertCanEdit(
    PhabricatorUser $viewer,
    PhabricatorApplicationTransactionInterface $object,
    PhabricatorApplicationTransaction $xaction) {

    $xaction_phid = $xaction->getPHID();
    $old_comment = $xaction->getComment();

    // Inline comments have their own transaction types, so this also
    // refuses them.
    $is_comment =
      $xaction->getTransactionType() === PhabricatorTransactions::TYPE_COMMENT;
    if (!$is_comment || !$old_comment) {
      throw id(new ConduitException('ERR-NOT-EDITABLE'))
        ->setErrorDescription(
          pht(
            'Transaction "%s" is not a regular comment. Inline comments are '.
            'edited with "differential.inline.edit", and only as drafts.',
            $xaction_phid));
    }

    if ($old_comment->getIsRemoved()) {
      throw id(new ConduitException('ERR-NOT-EDITABLE'))
        ->setErrorDescription(
          pht(
            'The comment on transaction "%s" was removed, so it cannot be '.
            'edited.',
            $xaction_phid));
    }

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $xaction,
      PhabricatorPolicyCapability::CAN_EDIT);
    if (!$can_edit) {
      throw id(new ConduitException('ERR-NOT-EDITABLE'))
        ->setErrorDescription(
          pht(
            'Only the author of the comment on transaction "%s" can edit it.',
            $xaction_phid));
    }

    if (!PhabricatorPolicyFilter::canInteract($viewer, $object)) {
      throw id(new ConduitException('ERR-LOCKED'))
        ->setErrorDescription(
          pht(
            'You can not edit this comment because the conversation is '.
            'locked.'));
    }

    if ($xaction->getIsMFATransaction()) {
      throw new ConduitException('ERR-MFA');
    }
  }

  /**
   * The edited comment in the shape of one entry of "transaction.search"'s
   * "comments", so a caller can read it as it reads that method's result.
   */
  private static function buildResult(
    PhabricatorApplicationTransactionInterface $object,
    PhabricatorApplicationTransaction $xaction,
    PhabricatorApplicationTransactionComment $comment) {

    return array(
      'objectPHID' => $object->getPHID(),
      'transactionPHID' => $xaction->getPHID(),
      'comment' => array(
        'id' => (int)$comment->getID(),
        'phid' => (string)$comment->getPHID(),
        'version' => (int)$comment->getCommentVersion(),
        'authorPHID' => (string)$comment->getAuthorPHID(),
        'dateCreated' => (int)$comment->getDateCreated(),
        'dateModified' => (int)$comment->getDateModified(),
        'removed' => (bool)$comment->getIsDeleted(),
        'content' => array(
          'raw' => (string)$comment->getContent(),
        ),
      ),
    );
  }

}
