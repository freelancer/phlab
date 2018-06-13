<?php

final class HarbormasterJenkinsBuildStepImplementation
  extends HarbormasterBuildStepImplementation {

  public function getName() {
    return pht('Build with Jenkins');
  }

  public function getGenericDescription() {
    return pht('Trigger a build in Jenkins.');
  }

  public function getBuildStepGroupKey() {
    return HarbormasterExternalBuildStepGroup::GROUPKEY;
  }

  public function getDescription() {
    return pht('Run a build in Jenkins.');
  }

  public function getEditInstructions() {
    return pht(<<<EOTEXT
To build **commits** with Jenkins:

  - You must configure a Jenkins pipeline for that repository; and
  - The pipeline must use the canonical Diffusion URL.
EOTEXT
    );
  }

  public function execute(
    HarbormasterBuild $build,
    HarbormasterBuildTarget $build_target) {

    $viewer = PhabricatorUser::getOmnipotentUser();

    if (PhabricatorEnv::getEnvConfig('phabricator.silent')) {
      $this->logSilencedCall($build, $build_target, pht('Jenkins'));
      throw new HarbormasterBuildFailureException();
    }

    $buildable = $build->getBuildable();
    $object = $buildable->getBuildableObject();

    if (!($object instanceof HarbormasterBuildkiteBuildableInterface)) {
      throw new Exception(
        pht('This object does not support builds with Jenkins.'));
    }

    $uri = new PhutilURI($this->getSetting('uri'));
    $build_variables = $object->getBuildVariables();

    $query = array(
      'url'    => $build_variables['repository.uri'],
      'commit' => $object->getBuildkiteCommit(),
      'branch' => $object->getBuildkiteBranch(),
    );

    $uri->setQueryParams($query);
    $uri->setPath('/git/notifyCommit');

    $future = id(new HTTPSFuture($uri))
      ->setMethod('POST')
      ->setTimeout(60);

    $this->resolveFutures(
      $build,
      $build_target,
      array($future));

    $this->logHTTPResponse($build, $build_target, $future, pht('Jenkins'));

    list($status, $body) = $future->resolve();
    if ($status->isError()) {
      throw new HarbormasterBuildFailureException();
    }
  }

  public function getFieldSpecifications() {
    return array(
      'uri' => array(
        'name'     => pht('Jenkins URL'),
        'type'     => 'text',
        'required' => true,
      ),
    );
  }
}
