<?php

/**
 * Disable users that have been suspended in GSuite.
 *
 * We use GSuite as the source of truth for user accounts. As such, it is
 * desirable that Phabricator user accounts are disabled as soon as the
 * corresponding account has been suspended in GSuite.
 */
final class PhabricatorGSuiteDisableUserWorker extends PhabricatorWorker {

  protected function doWork(): void {
    $data   = $this->getTaskData();
    $email  = idx($data, 'email');
    $viewer = PhabricatorUser::getOmnipotentUser();

    $user = (new PhabricatorPeopleQuery())
      ->setViewer($viewer)
      ->withEmails([$email])
      ->executeOne();

    // If no matching user is found or the user is already disabled,
    // there is nothing to be done.
    if ($user === null || $user->getIsDisabled()) {
      return;
    }

    $adapter = new PhutilGoogleAuthAdapter();
    $account = (new PhabricatorExternalAccountQuery())
      ->setViewer($viewer)
      ->withAccountTypes([$adapter->getAdapterType()])
      ->withAccountDomains([$adapter->getAdapterDomain()])
      ->withUserPHIDs([$user->getPHID()])
      ->executeOne();

    if ($account === null) {
      throw new PhabricatorWorkerPermanentFailureException(
        pht(
          'User ("%s") has no matching external account.',
          $user->getPHID()));
    }

    list($status) = (new HTTPSFuture($account->getAccountURI()))->resolve();

    switch ($status->getStatusCode()) {
      case 200:
        throw new PhabricatorWorkerPermanentFailureException(
          pht(
            'User ("%s") is still active.',
            $user->getPHID()));

      case 404:
        break;

      default:
        throw $status;
    }

    $editor = (new PhabricatorUserEditor())
      ->setActor($viewer)
      ->disableUser($user, true);
  }

}
