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
      ->setTransactionType(ManiphestTaskDeadlineReminderTransaction::TRANSACTIONTYPE);

    $application    = new PhabricatorDaemonsApplication();
    $content_source = $this->newContentSource();
    $editor         = $task->getApplicationTransactionEditor();

    $editor
      ->setActor($actor)
      ->setActingAsPHID($application->getPHID())
      ->setContentSource($content_source)
      ->applyTransactions($task, [$xaction]);
  }

}
