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
    'AphrontFormDateRangeControl' => 'view/form/control/AphrontFormDateRangeControl.php',
    'DifferentialMakeDraftHeraldAction' => 'applications/herald/action/DifferentialMakeDraftHeraldAction.php',
    'DifferentialMakeDraftHeraldActionTestCase' => 'applications/herald/action/__tests__/DifferentialMakeDraftHeraldActionTestCase.php',
    'DifferentialMakeDraftOnCreationHeraldAction' => 'applications/herald/action/DifferentialMakeDraftOnCreationHeraldAction.php',
    'DifferentialMakeDraftOnCreationHeraldActionTestCase' => 'applications/herald/action/__tests__/DifferentialMakeDraftOnCreationHeraldActionTestCase.php',
    'DiffusionChangesConduitAPIMethod' => 'applications/diffusion/conduit/DiffusionChangesConduitAPIMethod.php',
    'DiffusionChangesConduitAPIMethodTestCase' => 'applications/diffusion/conduit/__tests__/DiffusionChangesConduitAPIMethodTestCase.php',
    'DiffusionCommitHostedRepositoryHeraldField' => 'applications/diffusion/herald/DiffusionCommitHostedRepositoryHeraldField.php',
    'DiffusionCommitHostedRepositoryHeraldFieldTestCase' => 'applications/diffusion/herald/__tests__/DiffusionCommitHostedRepositoryHeraldFieldTestCase.php',
    'DiffusionCommitRevisionResigningReviewersHeraldField' => 'applications/diffusion/herald/DiffusionCommitRevisionResigningReviewersHeraldField.php',
    'DiffusionCommitRevisionResigningReviewersHeraldFieldTestCase' => 'applications/diffusion/herald/__tests__/DiffusionCommitRevisionResigningReviewersHeraldFieldTestCase.php',
    'DiffusionPreCommitRepositoryProjectsMatchesUserProjectsHeraldField' => 'applications/diffusion/herald/DiffusionPreCommitRepositoryProjectsMatchesUserProjectsHeraldField.php',
    'FreelancerGoogleAuthRegistrationListener' => 'applications/auth/event/FreelancerGoogleAuthRegistrationListener.php',
    'FreelancerGoogleAuthRegistrationListenerTestCase' => 'applications/auth/event/__tests__/FreelancerGoogleAuthRegistrationListenerTestCase.php',
    'HarbormasterJenkinsBuildStepImplementation' => 'applications/harbormaster/step/HarbormasterJenkinsBuildStepImplementation.php',
    'HeraldRocketChatNotificationAction' => 'applications/herald/action/HeraldRocketChatNotificationAction.php',
    'ManiphestDeadlineCustomField' => 'applications/maniphest/customfield/ManiphestDeadlineCustomField.php',
    'ManiphestDeadlineReminderWorker' => 'applications/maniphest/worker/ManiphestDeadlineReminderWorker.php',
    'ManiphestPerformanceReviewRevieweeCustomField' => 'applications/maniphest/customfield/ManiphestPerformanceReviewRevieweeCustomField.php',
    'ManiphestTaskDeadlineReminderTransaction' => 'applications/maniphest/xaction/ManiphestTaskDeadlineReminderTransaction.php',
    'PhabricatorAddOwnerProjectsHeraldAction' => 'applications/project/herald/PhabricatorAddOwnerProjectsHeraldAction.php',
    'PhabricatorCopyPHIDAction' => 'infrastructure/events/PhabricatorCopyPHIDAction.php',
    'PhabricatorCopyPHIDActionTestCase' => 'infrastructure/events/__tests__/PhabricatorCopyPHIDActionTestCase.php',
    'PhabricatorDaemonsPrometheusMetric' => 'applications/daemon/metric/PhabricatorDaemonsPrometheusMetric.php',
    'PhabricatorFilesManagementResetViewPolicyWorkflow' => 'applications/files/management/PhabricatorFilesManagementResetViewPolicyWorkflow.php',
    'PhabricatorInternalUserPolicyRule' => 'applications/policy/rule/PhabricatorInternalUserPolicyRule.php',
    'PhabricatorInternalUserPolicyRuleTestCase' => 'applications/policy/rule/__tests__/PhabricatorInternalUserPolicyRuleTestCase.php',
    'PhabricatorNewLineRemarkupRule' => 'infrastructure/markup/rule/PhabricatorNewLineRemarkupRule.php',
    'PhabricatorOwnedByProjectEdgeType' => 'applications/project/edge/PhabricatorOwnedByProjectEdgeType.php',
    'PhabricatorOwnedProjectsCustomField' => 'applications/project/customfield/PhabricatorOwnedProjectsCustomField.php',
    'PhabricatorOwnerProjectsCustomField' => 'applications/project/customfield/PhabricatorOwnerProjectsCustomField.php',
    'PhabricatorOwnsProjectEdgeType' => 'applications/project/edge/PhabricatorOwnsProjectEdgeType.php',
    'PhabricatorPeopleAddEmailWorkflow' => 'applications/people/management/PhabricatorPeopleAddEmailWorkflow.php',
    'PhabricatorPeopleCreateWorkflow' => 'applications/people/management/PhabricatorPeopleCreateWorkflow.php',
    'PhabricatorProjectColumnUpdatedOrder' => 'applications/project/order/PhabricatorProjectColumnUpdatedOrder.php',
    'PhabricatorProjectCustomEdgeField' => 'applications/project/customfield/PhabricatorProjectCustomEdgeField.php',
    'PhabricatorProjectSprintCycleCustomField' => 'applications/project/customfield/PhabricatorProjectSprintCycleCustomField.php',
    'PhabricatorPrometheusApplication' => 'applications/prometheus/application/PhabricatorPrometheusApplication.php',
    'PhabricatorPrometheusMetric' => 'applications/prometheus/metrics/PhabricatorPrometheusMetric.php',
    'PhabricatorPrometheusMetricsController' => 'applications/prometheus/controller/PhabricatorPrometheusMetricsController.php',
    'PhabricatorSetupIssuesPrometheusMetric' => 'applications/config/metric/PhabricatorSetupIssuesPrometheusMetric.php',
    'PhabricatorUpPrometheusMetric' => 'applications/prometheus/metrics/PhabricatorUpPrometheusMetric.php',
    'PhabricatorViewPolicyHeraldAction' => 'applications/policy/herald/PhabricatorViewPolicyHeraldAction.php',
    'PhabricatorViewPolicyHeraldActionTestCase' => 'applications/policy/herald/__tests__/PhabricatorViewPolicyHeraldActionTestCase.php',
    'PhlabCelerityResources' => 'applications/celerity/resources/PhlabCelerityResources.php',
    'PhlabConfigOptions' => 'applications/config/option/PhlabConfigOptions.php',
    'PhlabLibraryTestCase' => '__tests__/PhlabLibraryTestCase.php',
    'PhlabPatchList' => 'infrastructure/storage/patch/PhlabPatchList.php',
    'PhlabPatchListTestCase' => 'infrastructure/storage/patch/__tests__/PhlabPatchListTestCase.php',
    'PhlabProjectDatasource' => 'applications/project/typeahead/PhlabProjectDatasource.php',
    'PhlabProjectDatasourceTestCase' => 'applications/project/typeahead/__tests__/PhlabProjectDatasourceTestCase.php',
    'PhlabRemarkupEngineTestCase' => 'infrastructure/markup/__tests__/PhlabRemarkupEngineTestCase.php',
    'PhlabS3FileStorageEngine' => 'applications/files/engine/PhlabS3FileStorageEngine.php',
    'PhlabSESMailAdapter' => 'applications/metamta/adapter/PhlabSESMailAdapter.php',
    'PhlabSESMailAdapterTestCase' => 'applications/metamta/adapter/__tests__/PhlabSESMailAdapterTestCase.php',
    'PhlabUSEnglishTranslation' => 'infrastructure/internationalization/translation/PhlabUSEnglishTranslation.php',
    'PhlabUtilsTestCase' => 'utils/__tests__/PhlabUtilsTestCase.php',
    'RocketChatClient' => 'infrastructure/RocketChatClient.php',
    'RocketChatClientTestCase' => 'infrastructure/__tests__/RocketChatClientTestCase.php',
    'RocketChatConfigOptions' => 'applications/config/option/RocketChatConfigOptions.php',
  ),
  'function' => array(
    'varsprintf' => 'utils/utils.php',
  ),
  'xmap' => array(
    'AphrontFormDateRangeControl' => 'AphrontFormControl',
    'DifferentialMakeDraftHeraldAction' => 'HeraldAction',
    'DifferentialMakeDraftHeraldActionTestCase' => 'PhabricatorTestCase',
    'DifferentialMakeDraftOnCreationHeraldAction' => 'HeraldAction',
    'DifferentialMakeDraftOnCreationHeraldActionTestCase' => 'PhabricatorTestCase',
    'DiffusionChangesConduitAPIMethod' => 'DiffusionQueryConduitAPIMethod',
    'DiffusionChangesConduitAPIMethodTestCase' => 'PhutilTestCase',
    'DiffusionCommitHostedRepositoryHeraldField' => 'DiffusionCommitHeraldField',
    'DiffusionCommitHostedRepositoryHeraldFieldTestCase' => 'PhutilTestCase',
    'DiffusionCommitRevisionResigningReviewersHeraldField' => 'DiffusionCommitHeraldField',
    'DiffusionCommitRevisionResigningReviewersHeraldFieldTestCase' => 'PhabricatorTestCase',
    'DiffusionPreCommitRepositoryProjectsMatchesUserProjectsHeraldField' => 'DiffusionPreCommitRefHeraldField',
    'FreelancerGoogleAuthRegistrationListener' => 'PhabricatorEventListener',
    'FreelancerGoogleAuthRegistrationListenerTestCase' => 'PhabricatorTestCase',
    'HarbormasterJenkinsBuildStepImplementation' => 'HarbormasterBuildStepImplementation',
    'HeraldRocketChatNotificationAction' => 'HeraldAction',
    'ManiphestDeadlineCustomField' => 'ManiphestCustomField',
    'ManiphestDeadlineReminderWorker' => 'PhabricatorWorker',
    'ManiphestPerformanceReviewRevieweeCustomField' => array(
      'ManiphestCustomField',
      'PhabricatorStandardCustomFieldInterface',
    ),
    'ManiphestTaskDeadlineReminderTransaction' => 'ManiphestTaskTransactionType',
    'PhabricatorAddOwnerProjectsHeraldAction' => 'PhabricatorProjectHeraldAction',
    'PhabricatorCopyPHIDAction' => 'PhabricatorAutoEventListener',
    'PhabricatorCopyPHIDActionTestCase' => 'PhabricatorTestCase',
    'PhabricatorDaemonsPrometheusMetric' => 'PhabricatorPrometheusMetric',
    'PhabricatorFilesManagementResetViewPolicyWorkflow' => 'PhabricatorFilesManagementWorkflow',
    'PhabricatorInternalUserPolicyRule' => 'PhabricatorPolicyRule',
    'PhabricatorInternalUserPolicyRuleTestCase' => 'PhabricatorTestCase',
    'PhabricatorNewLineRemarkupRule' => 'PhabricatorRemarkupCustomInlineRule',
    'PhabricatorOwnedByProjectEdgeType' => 'PhabricatorEdgeType',
    'PhabricatorOwnedProjectsCustomField' => 'PhabricatorProjectCustomEdgeField',
    'PhabricatorOwnerProjectsCustomField' => 'PhabricatorProjectCustomEdgeField',
    'PhabricatorOwnsProjectEdgeType' => 'PhabricatorEdgeType',
    'PhabricatorPeopleAddEmailWorkflow' => 'PhabricatorPeopleManagementWorkflow',
    'PhabricatorPeopleCreateWorkflow' => 'PhabricatorPeopleManagementWorkflow',
    'PhabricatorProjectColumnUpdatedOrder' => 'PhabricatorProjectColumnOrder',
    'PhabricatorProjectCustomEdgeField' => 'PhabricatorProjectCustomField',
    'PhabricatorProjectSprintCycleCustomField' => 'PhabricatorProjectCustomField',
    'PhabricatorPrometheusApplication' => 'PhabricatorApplication',
    'PhabricatorPrometheusMetric' => 'Phobject',
    'PhabricatorPrometheusMetricsController' => 'PhabricatorController',
    'PhabricatorSetupIssuesPrometheusMetric' => 'PhabricatorPrometheusMetric',
    'PhabricatorUpPrometheusMetric' => 'PhabricatorPrometheusMetric',
    'PhabricatorViewPolicyHeraldAction' => 'HeraldAction',
    'PhabricatorViewPolicyHeraldActionTestCase' => 'PhabricatorTestCase',
    'PhlabCelerityResources' => 'CelerityResourcesOnDisk',
    'PhlabConfigOptions' => 'PhabricatorApplicationConfigOptions',
    'PhlabLibraryTestCase' => 'PhutilLibraryTestCase',
    'PhlabPatchList' => 'PhabricatorSQLPatchList',
    'PhlabPatchListTestCase' => 'PhutilTestCase',
    'PhlabProjectDatasource' => 'PhabricatorTypeaheadDatasource',
    'PhlabProjectDatasourceTestCase' => 'PhabricatorTestCase',
    'PhlabRemarkupEngineTestCase' => 'PhutilTestCase',
    'PhlabS3FileStorageEngine' => 'PhabricatorFileStorageEngine',
    'PhlabSESMailAdapter' => 'PhabricatorMailAdapter',
    'PhlabSESMailAdapterTestCase' => 'PhabricatorTestCase',
    'PhlabUSEnglishTranslation' => 'PhutilTranslation',
    'PhlabUtilsTestCase' => 'PhutilTestCase',
    'RocketChatClient' => 'Phobject',
    'RocketChatClientTestCase' => 'PhutilTestCase',
    'RocketChatConfigOptions' => 'PhabricatorApplicationConfigOptions',
  ),
));
