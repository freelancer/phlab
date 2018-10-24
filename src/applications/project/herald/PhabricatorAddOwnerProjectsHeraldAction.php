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

    $owner_projects = array_mergev(
      array_map(
        function (string $owned_project): array {
          return PhabricatorEdgeQuery::loadDestinationPHIDs(
            $owned_project,
            PhabricatorOwnedByProjectEdgeType::EDGECONST);
        },
        $current_projects));
    $owner_projects = array_unique($owner_projects);

    $this->applyProjects($owner_projects, $is_add = true);
  }

  public function renderActionDescription($value): string {
    return pht('Add owner projects');
  }

  public function getHeraldActionStandardType(): string {
    return HeraldAction::STANDARD_NONE;
  }

}
