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
    'PhabricatorBot' => 'applications/chatlog/bot/PhabricatorBot.php',
    'PhabricatorBotChannel' => 'applications/chatlog/bot/target/PhabricatorBotChannel.php',
    'PhabricatorBotDebugLogHandler' => 'applications/chatlog/bot/handler/PhabricatorBotDebugLogHandler.php',
    'PhabricatorBotFeedNotificationHandler' => 'applications/chatlog/bot/handler/PhabricatorBotFeedNotificationHandler.php',
    'PhabricatorBotHandler' => 'applications/chatlog/bot/handler/PhabricatorBotHandler.php',
    'PhabricatorBotLogHandler' => 'applications/chatlog/bot/handler/PhabricatorBotLogHandler.php',
    'PhabricatorBotMacroHandler' => 'applications/chatlog/bot/handler/PhabricatorBotMacroHandler.php',
    'PhabricatorBotMessage' => 'applications/chatlog/bot/PhabricatorBotMessage.php',
    'PhabricatorBotObjectNameHandler' => 'applications/chatlog/bot/handler/PhabricatorBotObjectNameHandler.php',
    'PhabricatorBotSymbolHandler' => 'applications/chatlog/bot/handler/PhabricatorBotSymbolHandler.php',
    'PhabricatorBotTarget' => 'applications/chatlog/bot/target/PhabricatorBotTarget.php',
    'PhabricatorBotUser' => 'applications/chatlog/bot/target/PhabricatorBotUser.php',
    'PhabricatorBotWhatsNewHandler' => 'applications/chatlog/bot/handler/PhabricatorBotWhatsNewHandler.php',
    'PhabricatorHipChatProtocolAdapter' => 'applications/chatlog/bot/adapter/PhabricatorHipChatProtocolAdapter.php',
    'PhabricatorIRCProtocolAdapter' => 'applications/chatlog/bot/adapter/PhabricatorIRCProtocolAdapter.php',
    'PhabricatorProtocolAdapter' => 'applications/chatlog/bot/adapter/PhabricatorProtocolAdapter.php',
    'PhabricatorStreamingProtocolAdapter' => 'applications/chatlog/bot/adapter/PhabricatorStreamingProtocolAdapter.php',
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
