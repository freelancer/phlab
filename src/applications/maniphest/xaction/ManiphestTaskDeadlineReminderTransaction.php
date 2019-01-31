<?php

final class ManiphestTaskDeadlineReminderTransaction
  extends ManiphestTaskTransactionType {

  const TRANSACTIONTYPE = 'deadline:reminder';

  public function generateOldValue($object) {
    return null;
  }

  public function getTransactionHasEffect($object, $old, $new): bool {
    return true;
  }

  public function shouldHideForFeed(): bool {
    return true;
  }

  public function getIcon(): ?string {
    return 'fa-bell';
  }

  public function getTitle(): string {
    return pht('The deadline for this task is approaching.');
  }

  public function getColor(): ?string {
    return 'red';
  }

}
