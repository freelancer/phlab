<?php

final class ManiphestDeadlineReminderWorker extends PhabricatorWorker {

  protected function doWork(): void {
    phlog('A task deadline is approaching.');
  }

}
