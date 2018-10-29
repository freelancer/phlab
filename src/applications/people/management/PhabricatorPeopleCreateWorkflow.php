<?php

final class PhabricatorPeopleCreateWorkflow
  extends PhabricatorPeopleManagementWorkflow {

  protected function didConstruct(): void {
    $this
      ->setName('create')
      ->setExamples('**create** --user __username__ --real-name __name__ --email __address__ --as __admin__')
      ->setSynopsis(pht("Associate an email address with a user's account."))
      ->setArguments(
        [
          [
            'name'  => 'user',
            'param' => 'username',
            'help'  => pht('Username of the user to be created.'),
          ],
          [
            'name'  => 'real-name',
            'param' => 'name',
            'help'  => pht('Real name of the user to be created.'),
          ],
          [
            'name'  => 'email',
            'param' => 'email',
            'help'  => pht('Email address of the user to be created.'),
          ],
          [
            'name'  => 'as',
            'param' => 'admin',
            'help'  => pht(
              'Administrative user to act on behalf of. '.
              'The welcome email will be sent on behalf of this user.'),
          ],
          [
            'name' => 'force',
            'help' => pht(
              'Forcefully create the user, bypassing `%s`.',
              'auth.email-domains'),
          ],
        ]);
  }

  public function execute(PhutilArgumentParser $args): void {
    $admin = (new PhabricatorUser())
      ->loadOneWhere('username = %s', $args->getArg('as'));

    if ($admin === null) {
      throw new PhutilArgumentUsageException(
        pht(
          'Admin user must be the username of a valid Phabricator account, '.
          'used to send the new user a welcome email.'));
    }

    $editor = (new PhabricatorUserEditor())
      ->setActor($admin);

    $user = (new PhabricatorUser())
      ->setUsername($args->getArg('user'))
      ->setRealName($args->getArg('real-name'));

    $email = (new PhabricatorUserEmail())
      ->setAddress($args->getArg('email'))
      ->setIsVerified(1);

    // Unconditionally approve new accounts created from the CLI.
    $user->setIsApproved(1);

    // TODO: This is extremely hacky, but we want to be able to create accounts
    // for external users, so we need to bypass `auth.email-domains`.
    if ($args->getArg('force')) {
      // Set `auth.email-domains` to an empty array to allow all domains.
      PhabricatorEnv::overrideConfig('auth.email-domains', []);
    }

    $editor->createNewUser($user, $email);
    $user->sendWelcomeEmail($admin);
  }

}
