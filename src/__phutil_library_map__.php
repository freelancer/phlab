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
    'DiffusionChangesConduitAPIMethod' => 'applications/diffusion/conduit/DiffusionChangesConduitAPIMethod.php',
    'DiffusionChangesConduitAPIMethodTestCase' => 'applications/diffusion/conduit/__tests__/DiffusionChangesConduitAPIMethodTestCase.php',
    'DiffusionCommitHostedRepositoryHeraldField' => 'applications/diffusion/herald/DiffusionCommitHostedRepositoryHeraldField.php',
    'DiffusionCommitRevisionResigningReviewersHeraldField' => 'applications/diffusion/herald/DiffusionCommitRevisionResigningReviewersHeraldField.php',
    'DiffusionPreCommitRepositoryProjectsMatchesUserProjectsHeraldField' => 'applications/diffusion/herald/DiffusionPreCommitRepositoryProjectsMatchesUserProjectsHeraldField.php',
    'FreelancerGoogleAuthRegistrationListener' => 'applications/auth/event/FreelancerGoogleAuthRegistrationListener.php',
    'HarbormasterJenkinsBuildStepImplementation' => 'applications/harbormaster/step/HarbormasterJenkinsBuildStepImplementation.php',
    'HeraldRocketChatNotificationAction' => 'applications/herald/action/HeraldRocketChatNotificationAction.php',
    'PhabricatorAddOwnerProjectsHeraldAction' => 'applications/project/herald/PhabricatorAddOwnerProjectsHeraldAction.php',
    'PhabricatorCopyPHIDAction' => 'infrastructure/events/PhabricatorCopyPHIDAction.php',
    'PhabricatorCopyPHIDActionTestCase' => 'infrastructure/events/__tests__/PhabricatorCopyPHIDActionTestCase.php',
    'PhabricatorDaemonTasksPrometheusMetric' => 'applications/daemon/metric/PhabricatorDaemonTasksPrometheusMetric.php',
    'PhabricatorDaemonsPrometheusMetric' => 'applications/daemon/metric/PhabricatorDaemonsPrometheusMetric.php',
    'PhabricatorFilesManagementResetViewPolicyWorkflow' => 'applications/files/management/PhabricatorFilesManagementResetViewPolicyWorkflow.php',
    'PhabricatorGSuiteDisableUserWorker' => 'applications/auth/worker/PhabricatorGSuiteDisableUserWorker.php',
    'PhabricatorInternalUserPolicyRule' => 'applications/policy/rule/PhabricatorInternalUserPolicyRule.php',
    'PhabricatorInternalUserPolicyRuleTestCase' => 'applications/policy/rule/__tests__/PhabricatorInternalUserPolicyRuleTestCase.php',
    'PhabricatorNewLineRemarkupRule' => 'infrastructure/markup/rule/PhabricatorNewLineRemarkupRule.php',
    'PhabricatorOwnedByProjectEdgeType' => 'applications/project/edge/PhabricatorOwnedByProjectEdgeType.php',
    'PhabricatorOwnedProjectsCustomField' => 'applications/project/customfield/PhabricatorOwnedProjectsCustomField.php',
    'PhabricatorOwnsProjectEdgeType' => 'applications/project/edge/PhabricatorOwnsProjectEdgeType.php',
    'PhabricatorPeopleAddEmailWorkflow' => 'applications/people/management/PhabricatorPeopleAddEmailWorkflow.php',
    'PhabricatorPeopleCreateWorkflow' => 'applications/people/management/PhabricatorPeopleCreateWorkflow.php',
    'PhabricatorProjectCustomEdgeField' => 'applications/project/customfield/PhabricatorProjectCustomEdgeField.php',
    'PhabricatorPrometheusApplication' => 'applications/prometheus/application/PhabricatorPrometheusApplication.php',
    'PhabricatorPrometheusMetric' => 'applications/prometheus/metrics/PhabricatorPrometheusMetric.php',
    'PhabricatorPrometheusMetricsController' => 'applications/prometheus/controller/PhabricatorPrometheusMetricsController.php',
    'PhabricatorSetupIssuesPrometheusMetric' => 'applications/config/metric/PhabricatorSetupIssuesPrometheusMetric.php',
    'PhabricatorUpPrometheusMetric' => 'applications/prometheus/metrics/PhabricatorUpPrometheusMetric.php',
    'PhabricatorViewPolicyHeraldAction' => 'applications/policy/herald/PhabricatorViewPolicyHeraldAction.php',
    'PhabricatorViewPolicyHeraldActionTestCase' => 'applications/policy/herald/__tests__/PhabricatorViewPolicyHeraldActionTestCase.php',
    'PhlabAmazonSESMailImplementationAdapter' => 'applications/metamta/adapter/PhlabAmazonSESMailImplementationAdapter.php',
    'PhlabLibraryTestCase' => '__tests__/PhlabLibraryTestCase.php',
    'PhlabPatchList' => 'infrastructure/storage/patch/PhlabPatchList.php',
    'PhlabPatchListTestCase' => 'infrastructure/storage/patch/__tests__/PhlabPatchListTestCase.php',
    'PhlabRemarkupEngineTestCase' => 'infrastructure/markup/__tests__/PhlabRemarkupEngineTestCase.php',
    'PhlabS3FileStorageEngine' => 'applications/files/engine/PhlabS3FileStorageEngine.php',
    'RocketChatClient' => 'infrastructure/RocketChatClient.php',
    'RocketChatConfigOptions' => 'applications/config/option/RocketChatConfigOptions.php',
  ),
  'function' => array(),
  'xmap' => array(
    'DiffusionChangesConduitAPIMethod' => 'DiffusionQueryConduitAPIMethod',
    'DiffusionChangesConduitAPIMethodTestCase' => 'PhutilTestCase',
    'DiffusionCommitHostedRepositoryHeraldField' => 'DiffusionCommitHeraldField',
    'DiffusionCommitRevisionResigningReviewersHeraldField' => 'DiffusionCommitHeraldField',
    'DiffusionPreCommitRepositoryProjectsMatchesUserProjectsHeraldField' => 'DiffusionPreCommitRefHeraldField',
    'FreelancerGoogleAuthRegistrationListener' => 'PhabricatorAutoEventListener',
    'HarbormasterJenkinsBuildStepImplementation' => 'HarbormasterBuildStepImplementation',
    'HeraldRocketChatNotificationAction' => 'HeraldAction',
    'PhabricatorAddOwnerProjectsHeraldAction' => 'PhabricatorProjectHeraldAction',
    'PhabricatorCopyPHIDAction' => 'PhabricatorAutoEventListener',
    'PhabricatorCopyPHIDActionTestCase' => 'PhabricatorTestCase',
    'PhabricatorDaemonTasksPrometheusMetric' => 'PhabricatorPrometheusMetric',
    'PhabricatorDaemonsPrometheusMetric' => 'PhabricatorPrometheusMetric',
    'PhabricatorFilesManagementResetViewPolicyWorkflow' => 'PhabricatorFilesManagementWorkflow',
    'PhabricatorGSuiteDisableUserWorker' => 'PhabricatorWorker',
    'PhabricatorInternalUserPolicyRule' => 'PhabricatorPolicyRule',
    'PhabricatorInternalUserPolicyRuleTestCase' => 'PhabricatorTestCase',
    'PhabricatorNewLineRemarkupRule' => 'PhabricatorRemarkupCustomInlineRule',
    'PhabricatorOwnedByProjectEdgeType' => 'PhabricatorEdgeType',
    'PhabricatorOwnedProjectsCustomField' => 'PhabricatorProjectCustomEdgeField',
    'PhabricatorOwnsProjectEdgeType' => 'PhabricatorEdgeType',
    'PhabricatorPeopleAddEmailWorkflow' => 'PhabricatorPeopleManagementWorkflow',
    'PhabricatorPeopleCreateWorkflow' => 'PhabricatorPeopleManagementWorkflow',
    'PhabricatorProjectCustomEdgeField' => 'PhabricatorProjectCustomField',
    'PhabricatorPrometheusApplication' => 'PhabricatorApplication',
    'PhabricatorPrometheusMetric' => 'Phobject',
    'PhabricatorPrometheusMetricsController' => 'PhabricatorController',
    'PhabricatorSetupIssuesPrometheusMetric' => 'PhabricatorPrometheusMetric',
    'PhabricatorUpPrometheusMetric' => 'PhabricatorPrometheusMetric',
    'PhabricatorViewPolicyHeraldAction' => 'HeraldAction',
    'PhabricatorViewPolicyHeraldActionTestCase' => 'PhutilTestCase',
    'PhlabAmazonSESMailImplementationAdapter' => 'PhabricatorMailImplementationPHPMailerLiteAdapter',
    'PhlabLibraryTestCase' => 'PhutilLibraryTestCase',
    'PhlabPatchList' => 'PhabricatorSQLPatchList',
    'PhlabPatchListTestCase' => 'PhutilTestCase',
    'PhlabRemarkupEngineTestCase' => 'PhutilTestCase',
    'PhlabS3FileStorageEngine' => 'PhabricatorFileStorageEngine',
    'RocketChatClient' => 'Phobject',
    'RocketChatConfigOptions' => 'PhabricatorApplicationConfigOptions',
  ),
));
