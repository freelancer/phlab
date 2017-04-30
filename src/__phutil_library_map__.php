<?php

/**
 * This file is automatically generated. Use 'arc liberate' to rebuild it.
 *
 * @generated
 * @phutil-library-version 2
 */
phutil_register_library_map(array(
  '__library_version__' => 2,
  'class' => array(
    'FreelancerGoogleAuthRegistrationListener' => 'applications/auth/event/FreelancerGoogleAuthRegistrationListener.php',
    'HeraldHipChatNotificationAction' => 'applications/herald/extension/HeraldHipChatNotificationAction.php',
    'HipChatClient' => 'infrastructure/HipChatClient.php',
    'HipChatConfigOptions' => 'applications/config/option/HipChatConfigOptions.php',
    'PhabricatorBot' => 'infrastructure/daemon/bot/PhabricatorBot.php',
    'PhabricatorBotChannel' => 'infrastructure/daemon/bot/target/PhabricatorBotChannel.php',
    'PhabricatorBotDebugLogHandler' => 'infrastructure/daemon/bot/handler/PhabricatorBotDebugLogHandler.php',
    'PhabricatorBotFeedNotificationHandler' => 'infrastructure/daemon/bot/handler/PhabricatorBotFeedNotificationHandler.php',
    'PhabricatorBotHandler' => 'infrastructure/daemon/bot/handler/PhabricatorBotHandler.php',
    'PhabricatorBotLogHandler' => 'infrastructure/daemon/bot/handler/PhabricatorBotLogHandler.php',
    'PhabricatorBotMacroHandler' => 'infrastructure/daemon/bot/handler/PhabricatorBotMacroHandler.php',
    'PhabricatorBotMessage' => 'infrastructure/daemon/bot/PhabricatorBotMessage.php',
    'PhabricatorBotObjectNameHandler' => 'infrastructure/daemon/bot/handler/PhabricatorBotObjectNameHandler.php',
    'PhabricatorBotSymbolHandler' => 'infrastructure/daemon/bot/handler/PhabricatorBotSymbolHandler.php',
    'PhabricatorBotTarget' => 'infrastructure/daemon/bot/target/PhabricatorBotTarget.php',
    'PhabricatorBotUser' => 'infrastructure/daemon/bot/target/PhabricatorBotUser.php',
    'PhabricatorBotWhatsNewHandler' => 'infrastructure/daemon/bot/handler/PhabricatorBotWhatsNewHandler.php',
    'PhabricatorHipChatProtocolAdapter' => 'infrastructure/daemon/bot/adapter/PhabricatorHipChatProtocolAdapter.php',
    'PhabricatorIRCProtocolAdapter' => 'infrastructure/daemon/bot/adapter/PhabricatorIRCProtocolAdapter.php',
    'PhabricatorProtocolAdapter' => 'infrastructure/daemon/bot/adapter/PhabricatorProtocolAdapter.php',
    'PhabricatorStreamingProtocolAdapter' => 'infrastructure/daemon/bot/adapter/PhabricatorStreamingProtocolAdapter.php',
    'PhlabLibraryTestCase' => '__tests__/PhlabLibraryTestCase.php',
    'PhlabS3FileStorageEngine' => 'applications/files/engine/PhlabS3FileStorageEngine.php',
  ),
  'function' => array(),
  'xmap' => array(
    'FreelancerGoogleAuthRegistrationListener' => 'PhabricatorEventListener',
    'HeraldHipChatNotificationAction' => 'HeraldAction',
    'HipChatClient' => 'Phobject',
    'HipChatConfigOptions' => 'PhabricatorApplicationConfigOptions',
    'PhabricatorBot' => 'PhabricatorDaemon',
    'PhabricatorBotChannel' => 'PhabricatorBotTarget',
    'PhabricatorBotDebugLogHandler' => 'PhabricatorBotHandler',
    'PhabricatorBotFeedNotificationHandler' => 'PhabricatorBotHandler',
    'PhabricatorBotHandler' => 'Phobject',
    'PhabricatorBotLogHandler' => 'PhabricatorBotHandler',
    'PhabricatorBotMacroHandler' => 'PhabricatorBotHandler',
    'PhabricatorBotMessage' => 'Phobject',
    'PhabricatorBotObjectNameHandler' => 'PhabricatorBotHandler',
    'PhabricatorBotSymbolHandler' => 'PhabricatorBotHandler',
    'PhabricatorBotTarget' => 'Phobject',
    'PhabricatorBotUser' => 'PhabricatorBotTarget',
    'PhabricatorBotWhatsNewHandler' => 'PhabricatorBotHandler',
    'PhabricatorHipChatProtocolAdapter' => 'PhabricatorProtocolAdapter',
    'PhabricatorIRCProtocolAdapter' => 'PhabricatorProtocolAdapter',
    'PhabricatorProtocolAdapter' => 'Phobject',
    'PhabricatorStreamingProtocolAdapter' => 'PhabricatorProtocolAdapter',
    'PhlabLibraryTestCase' => 'PhutilLibraryTestCase',
    'PhlabS3FileStorageEngine' => 'PhabricatorFileStorageEngine',
  ),
));
