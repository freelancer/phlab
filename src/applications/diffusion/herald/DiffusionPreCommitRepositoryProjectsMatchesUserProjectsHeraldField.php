<?php

final class DiffusionPreCommitRepositoryProjectsMatchesUserProjectsHeraldField
  extends DiffusionPreCommitRefHeraldField {

  const FIELDCONST = 'diffusion.commit.repository-user.projects';

  public function getHeraldFieldName() {
    return pht('Repository tags matches pusher projects');
  }

  public function getHeraldFieldValue($object) {
    $adapter = $this->getAdapter();
    $viewer = $adapter->getHookEngine()->getViewer();
    $repository = $object->getRepository();

    $repository_projects = $this->getRepositoryProjects($object);
    $user_projects = $this->getUserProjects();

    return !empty(array_intersect($repository_projects, $user_projects));
  }

  protected function getHeraldFieldStandardType() {
    return HeraldField::STANDARD_BOOL;
  }

  private function getUserProjects() {
    return $this->getAdapter()
      ->getHookEngine()
      ->loadViewerProjectPHIDsForHerald();
  }

  private function getRepositoryProjects($object) {
    return PhabricatorEdgeQuery::loadDestinationPHIDs(
      $object->getRepository()->getPHID(),
      PhabricatorProjectObjectHasProjectEdgeType::EDGECONST);
  }
}
