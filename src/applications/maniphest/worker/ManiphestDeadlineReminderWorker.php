<?php

final class ManiphestDeadlineReminderWorker extends PhabricatorWorker {

  protected function doWork(): void {
    $actor = PhabricatorUser::getOmnipotentUser();

    $task = (new ManiphestTaskQuery())
      ->setViewer($actor)
      ->withPHIDs([$this->getTaskDataValue('objectPHID')])
      ->executeOne();

    if ($task->isClosed()) {
      throw new PhabricatorWorkerPermanentFailureException(
        pht('Task is already closed.'));
    }

    $xaction = $task->getApplicationTransactionTemplate()
      ->setTransactionType(PhabricatorTransactions::TYPE_COMMENT);
    $comment = $xaction->getApplicationTransactionCommentObject()
      ->setContent(pht('The deadline for this task is approaching!'));
    $xaction->attachComment($comment);

    $application    = new PhabricatorDaemonsApplication();
    $content_source = $this->newContentSource();
    $editor = $task->getApplicationTransactionEditor();

    $editor
      ->setActor($actor)
      ->setActingAsPHID($application->getPHID())
      ->setContentSource($content_source)
      ->applyTransactions($task, [$xaction]);
  }

}
