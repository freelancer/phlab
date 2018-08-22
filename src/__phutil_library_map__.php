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
    'CreatePolicyConduitAPIMethod' => 'applications/policy/conduit/CreatePolicyConduitAPIMethod.php',
    'DiffusionChangesConduitAPIMethod' => 'applications/diffusion/conduit/DiffusionChangesConduitAPIMethod.php',
    'DiffusionCommitRevisionResigningReviewersHeraldField' => 'applications/diffusion/herald/DiffusionCommitRevisionResigningReviewersHeraldField.php',
    'DiffusionPreCommitRepositoryProjectsMatchesUserProjectsHeraldField' => 'applications/diffusion/herald/DiffusionPreCommitRepositoryProjectsMatchesUserProjectsHeraldField.php',
    'FreelancerGoogleAuthRegistrationListener' => 'applications/auth/event/FreelancerGoogleAuthRegistrationListener.php',
    'HarbormasterJenkinsBuildStepImplementation' => 'applications/harbormaster/step/HarbormasterJenkinsBuildStepImplementation.php',
    'HeraldRocketChatNotificationAction' => 'applications/herald/action/HeraldRocketChatNotificationAction.php',
    'PhabricatorCopyPHIDAction' => 'infrastructure/events/PhabricatorCopyPHIDAction.php',
    'PhabricatorDaemonTasksPrometheusMetric' => 'applications/daemon/metric/PhabricatorDaemonTasksPrometheusMetric.php',
    'PhabricatorDaemonsPrometheusMetric' => 'applications/daemon/metric/PhabricatorDaemonsPrometheusMetric.php',
    'PhabricatorPeopleAddEmailWorkflow' => 'applications/people/management/PhabricatorPeopleAddEmailWorkflow.php',
    'PhabricatorPrometheusApplication' => 'applications/prometheus/application/PhabricatorPrometheusApplication.php',
    'PhabricatorPrometheusMetric' => 'applications/prometheus/metrics/PhabricatorPrometheusMetric.php',
    'PhabricatorPrometheusMetricsController' => 'applications/prometheus/controller/PhabricatorPrometheusMetricsController.php',
    'PhabricatorSetupIssuesPrometheusMetric' => 'applications/config/metric/PhabricatorSetupIssuesPrometheusMetric.php',
    'PhabricatorUpPrometheusMetric' => 'applications/prometheus/metrics/PhabricatorUpPrometheusMetric.php',
    'PhlabAmazonSESMailImplementationAdapter' => 'applications/metamta/adapter/PhlabAmazonSESMailImplementationAdapter.php',
    'PhlabLibraryTestCase' => '__tests__/PhlabLibraryTestCase.php',
    'PhlabPatchList' => 'infrastructure/storage/patch/PhlabPatchList.php',
    'PhlabPatchListTestCase' => 'infrastructure/storage/patch/__tests__/PhlabPatchListTestCase.php',
    'PhlabS3FileStorageEngine' => 'applications/files/engine/PhlabS3FileStorageEngine.php',
    'RocketChatClient' => 'infrastructure/RocketChatClient.php',
    'RocketChatConfigOptions' => 'applications/config/option/RocketChatConfigOptions.php',
  ),
  'function' => array(),
  'xmap' => array(
    'CreatePolicyConduitAPIMethod' => 'ConduitAPIMethod',
    'DiffusionChangesConduitAPIMethod' => 'DiffusionQueryConduitAPIMethod',
    'DiffusionCommitRevisionResigningReviewersHeraldField' => 'DiffusionCommitHeraldField',
    'DiffusionPreCommitRepositoryProjectsMatchesUserProjectsHeraldField' => 'DiffusionPreCommitRefHeraldField',
    'FreelancerGoogleAuthRegistrationListener' => 'PhabricatorEventListener',
    'HarbormasterJenkinsBuildStepImplementation' => 'HarbormasterBuildStepImplementation',
    'HeraldRocketChatNotificationAction' => 'HeraldAction',
    'PhabricatorCopyPHIDAction' => 'PhabricatorAutoEventListener',
    'PhabricatorDaemonTasksPrometheusMetric' => 'PhabricatorPrometheusMetric',
    'PhabricatorDaemonsPrometheusMetric' => 'PhabricatorPrometheusMetric',
    'PhabricatorPeopleAddEmailWorkflow' => 'PhabricatorPeopleManagementWorkflow',
    'PhabricatorPrometheusApplication' => 'PhabricatorApplication',
    'PhabricatorPrometheusMetric' => 'Phobject',
    'PhabricatorPrometheusMetricsController' => 'PhabricatorController',
    'PhabricatorSetupIssuesPrometheusMetric' => 'PhabricatorPrometheusMetric',
    'PhabricatorUpPrometheusMetric' => 'PhabricatorPrometheusMetric',
    'PhlabAmazonSESMailImplementationAdapter' => 'PhabricatorMailImplementationPHPMailerLiteAdapter',
    'PhlabLibraryTestCase' => 'PhutilLibraryTestCase',
    'PhlabPatchList' => 'PhabricatorSQLPatchList',
    'PhlabPatchListTestCase' => 'PhutilTestCase',
    'PhlabS3FileStorageEngine' => 'PhabricatorFileStorageEngine',
    'RocketChatClient' => 'Phobject',
    'RocketChatConfigOptions' => 'PhabricatorApplicationConfigOptions',
  ),
));
