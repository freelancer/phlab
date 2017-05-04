<?php

/**
 * Logs messages to stdout.
 */
final class PhabricatorBotDebugLogHandler extends PhabricatorBotHandler {
  public function receiveMessage(PhabricatorChatbotMessage $message) {
    switch ($message->getCommand()) {
    case 'LOG':
      echo addcslashes(
        $message->getBody(),
        "\0..\37\177..\377");
      echo "\n";
      break;
    }
  }
}
