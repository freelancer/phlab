<?php

final class PhabricatorPeopleAddEmailWorkflow
  extends PhabricatorPeopleManagementWorkflow {

  protected function didConstruct(): void {
    $this
      ->setName('add-email')
      ->setExamples('**add-email** --user __username__ --email __address__')
      ->setSynopsis(pht("Associate an email address with a user's account."))
      ->setArguments(
        [
          [
            'name'  => 'user',
            'param' => 'user',
            'help'  => pht('The username of the user to be updated.'),
          ],
          [
            'name'  => 'email',
            'param' => 'email',
            'help'  => pht(
              "The email address to be associated with the user's account."),
          ],
          [
            'name' => 'verify',
            'help' => pht('Mark the email address as verified.'),
          ],
        ]);
  }

  public function execute(PhutilArgumentParser $args): void {
    $username      = $args->getArg('user');
    $email_address = $args->getArg('email');
    $verify        = $args->getArg('verify');

    if (!strlen($username)) {
      throw new PhutilArgumentUsageException(
        pht(
          'You must specify a username with `%s`.',
          '--user'));
    }

    if (!strlen($email_address)) {
      throw new PhutilArgumentUsageException(
        pht(
          'You must specify an email address with `%s`.',
          '--email_address'));
    }

    $existing_user = PhabricatorUser::loadOneWithEmailAddress($email_address);
    if ($existing_user) {
      throw new PhutilArgumentUsageException(
        pht(
          'The specified email address is already in use by user @%s.',
          $existing_user->getUserName()));
    }

    $user = (new PhabricatorUser())->loadOneWhere('userName = %s', $username);
    if (!$user) {
      throw new PhutilArgumentUsageException(
        pht(
          'User @%s not found.',
          $username));
    }

    $actor = PhabricatorUser::getOmnipotentUser();
    $email = (new PhabricatorUserEmail())
      ->setUserPHID($user->getPHID())
      ->setAddress($email_address)
      ->setIsVerified($verify ? 1 : 0)
      ->setIsPrimary(0);

    $editor = (new PhabricatorUserEditor())
      ->setActor($actor)
      ->updateUser($user, $email);
  }
}
