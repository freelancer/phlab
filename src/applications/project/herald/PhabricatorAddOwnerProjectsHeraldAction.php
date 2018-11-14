<?php

final class PhabricatorAddOwnerProjectsHeraldAction
  extends PhabricatorProjectHeraldAction {

  const ACTIONCONST = 'projects.add-owner-projects';

  public function getHeraldActionName(): string {
    return pht('Add owner projects');
  }

  public function applyEffect($object, HeraldEffect $effect): void {
    $adapter = $this->getAdapter();

    // Load the projects currently associated with the object.
    $current_projects = $adapter->loadEdgePHIDs(
      PhabricatorProjectObjectHasProjectEdgeType::EDGECONST);

    $this->applyProjects(
      $this->getOwnerProjects($current_projects),
      $is_add = true);
  }

  public function renderActionDescription($value): string {
    return pht('Add owner projects');
  }

  public function getHeraldActionStandardType(): string {
    return HeraldAction::STANDARD_NONE;
  }

  private function getOwnerProjects(array $project_phids): array {
    $unvisited = $project_phids;
    $visited   = [];

    while ($unvisited) {
      $project_phid = array_shift($unvisited);
      $visited[$project_phid] = true;

      $owner_project_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
        $project_phid,
        PhabricatorOwnedByProjectEdgeType::EDGECONST);

      foreach ($owner_project_phids as $owner_project_phid) {
        if (isset($visited[$owner_project_phid])) {
          continue;
        }

        $unvisited[] = $owner_project_phid;
      }
    }

    return array_diff(array_keys($visited), $project_phids);
  }

}
