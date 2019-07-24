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

    // TODO: We should use `$this->getViewer()`, but it returns `null` for
    // global Herald rules.
    $viewer = coalesce(
      $this->getViewer(),
      PhabricatorUser::getOmnipotentUser());

    while ($unvisited) {
      $project_phid = array_shift($unvisited);
      $visited[$project_phid] = true;

      $owner_project_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
        $project_phid,
        PhabricatorOwnedByProjectEdgeType::EDGECONST);

      $owner_projects = (new PhabricatorProjectQuery())
        ->setViewer($viewer)
        ->withPHIDs($owner_project_phids)
        ->execute();

      foreach ($owner_projects as $owner_project) {
        if ($owner_project->isArchived()) {
          continue;
        }

        if (isset($visited[$owner_project->getPHID()])) {
          continue;
        }

        $unvisited[] = $owner_project->getPHID();
      }
    }

    return array_diff(array_keys($visited), $project_phids);
  }

}
