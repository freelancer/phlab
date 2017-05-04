<?php

/**
 * A chatbot target that represents a group channel or room.
 */
final class PhabricatorChatbotChannel extends PhabricatorChatbotTarget {

  public function isPublic() {
    return true;
  }

}
