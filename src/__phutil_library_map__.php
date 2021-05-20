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
    'DifferentialChangeStatusHeraldAction' => 'applications/herald/action/DifferentialChangeStatusHeraldAction.php',
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
    'ManiphestBrowserCustomField' => 'applications/maniphest/customfield/bugcategorization/ManiphestBrowserCustomField.php',
    'ManiphestBrowserDatasource' => 'applications/maniphest/typeahead/bugcategorization/ManiphestBrowserDatasource.php',
    'ManiphestBugReporterCustomField' => 'applications/maniphest/customfield/bugcategorization/ManiphestBugReporterCustomField.php',
    'ManiphestBugReporterDatasource' => 'applications/maniphest/typeahead/bugcategorization/ManiphestBugReporterDatasource.php',
    'ManiphestDeadlineCustomField' => 'applications/maniphest/customfield/ManiphestDeadlineCustomField.php',
    'ManiphestDeadlineReminderWorker' => 'applications/maniphest/worker/ManiphestDeadlineReminderWorker.php',
    'ManiphestEnvironmentFoundCustomField' => 'applications/maniphest/customfield/bugcategorization/ManiphestEnvironmentFoundCustomField.php',
    'ManiphestEnvironmentFoundDatasource' => 'applications/maniphest/typeahead/bugcategorization/ManiphestEnvironmentFoundDatasource.php',
    'ManiphestMultiValueCustomField' => 'applications/maniphest/customfield/infrastructure/ManiphestMultiValueCustomField.php',
    'ManiphestPerformanceReviewRevieweeCustomField' => 'applications/maniphest/customfield/ManiphestPerformanceReviewRevieweeCustomField.php',
    'ManiphestPlatformCustomField' => 'applications/maniphest/customfield/bugcategorization/ManiphestPlatformCustomField.php',
    'ManiphestPlatformDatasource' => 'applications/maniphest/typeahead/bugcategorization/ManiphestPlatformDatasource.php',
    'ManiphestRootCauseCustomField' => 'applications/maniphest/customfield/bugcategorization/ManiphestRootCauseCustomField.php',
    'ManiphestRootCauseDatasource' => 'applications/maniphest/typeahead/bugcategorization/ManiphestRootCauseDatasource.php',
    'ManiphestStaticMapDatasource' => 'applications/maniphest/typeahead/ManiphestStaticMapDatasource.php',
    'ManiphestTaskDeadlineReminderTransaction' => 'applications/maniphest/xaction/ManiphestTaskDeadlineReminderTransaction.php',
    'ManiphestTypeOfBugsCustomField' => 'applications/maniphest/customfield/bugcategorization/ManiphestTypeOfBugsCustomField.php',
    'ManiphestTypeOfBugsDatasource' => 'applications/maniphest/typeahead/bugcategorization/ManiphestTypeOfBugsDatasource.php',
    'PhabricatorAddOwnerProjectsHeraldAction' => 'applications/project/herald/PhabricatorAddOwnerProjectsHeraldAction.php',
    'PhabricatorBugCategorizationFieldValuePHIDType' => 'applications/maniphest/customfield/infrastructure/PhabricatorBugCategorizationFieldValuePHIDType.php',
    'PhabricatorBugCategorizationTokenQuery' => 'applications/maniphest/customfield/infrastructure/PhabricatorBugCategorizationTokenQuery.php',
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
    'DifferentialChangeStatusHeraldAction' => 'HeraldAction',
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
    'ManiphestBrowserCustomField' => 'ManiphestMultiValueCustomField',
    'ManiphestBrowserDatasource' => 'ManiphestStaticMapDatasource',
    'ManiphestBugReporterCustomField' => 'ManiphestMultiValueCustomField',
    'ManiphestBugReporterDatasource' => 'ManiphestStaticMapDatasource',
    'ManiphestDeadlineCustomField' => 'ManiphestCustomField',
    'ManiphestDeadlineReminderWorker' => 'PhabricatorWorker',
    'ManiphestEnvironmentFoundCustomField' => 'ManiphestMultiValueCustomField',
    'ManiphestEnvironmentFoundDatasource' => 'ManiphestStaticMapDatasource',
    'ManiphestMultiValueCustomField' => array(
      'ManiphestCustomField',
      'PhabricatorStandardCustomFieldInterface',
    ),
    'ManiphestPerformanceReviewRevieweeCustomField' => array(
      'ManiphestCustomField',
      'PhabricatorStandardCustomFieldInterface',
    ),
    'ManiphestPlatformCustomField' => 'ManiphestMultiValueCustomField',
    'ManiphestPlatformDatasource' => 'ManiphestStaticMapDatasource',
    'ManiphestRootCauseCustomField' => 'ManiphestMultiValueCustomField',
    'ManiphestRootCauseDatasource' => 'ManiphestStaticMapDatasource',
    'ManiphestStaticMapDatasource' => 'PhabricatorTypeaheadDatasource',
    'ManiphestTaskDeadlineReminderTransaction' => 'ManiphestTaskTransactionType',
    'ManiphestTypeOfBugsCustomField' => 'ManiphestMultiValueCustomField',
    'ManiphestTypeOfBugsDatasource' => 'ManiphestStaticMapDatasource',
    'PhabricatorAddOwnerProjectsHeraldAction' => 'PhabricatorProjectHeraldAction',
    'PhabricatorBugCategorizationFieldValuePHIDType' => 'PhabricatorPHIDType',
    'PhabricatorBugCategorizationTokenQuery' => 'PhabricatorCursorPagedPolicyAwareQuery',
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
