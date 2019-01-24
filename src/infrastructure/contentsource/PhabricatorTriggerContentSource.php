<?php

final class PhabricatorTriggerContentSource extends PhabricatorContentSource {

  const SOURCECONST = 'trigger';

  public function getSourceName(): string {
    return pht('Trigger');
  }

  public function getSourceDescription(): string {
    return pht('Content created by a time-based trigger.');
  }

}
