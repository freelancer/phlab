<?php

/**
 * A chatbot target that represents an individual user.
 */
final class PhabricatorChatbotUser extends PhabricatorChatbotTarget {

  public function isPublic() {
    return false;
  }

}
