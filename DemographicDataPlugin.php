<?php

require_once('autoload.php');

use APP\plugins\generic\demographicData\classes\DataCollectionEmailSender;
use APP\plugins\generic\demographicData\classes\DefaultQuestionsCreator;
use APP\plugins\generic\demographicData\classes\DemographicDataService;
use APP\plugins\generic\demographicData\classes\dispatchers\TemplateFilterDispatcher;
use APP\plugins\generic\demographicData\classes\form\CustomRegistrationForm;
use APP\plugins\generic\demographicData\classes\migrations\SchemaMigration;
use APP\plugins\generic\demographicData\classes\DemographicDataDAO;
use APP\plugins\generic\demographicData\classes\observers\listeners\MigrateResponsesOnRegistration;
use APP\plugins\generic\demographicData\classes\OrcidClient;
use APP\plugins\generic\demographicData\DemographicDataSettingsForm;
use Illuminate\Database\Migrations\Migration;
use PKP\plugins\GenericPlugin;

class DemographicDataPlugin extends \GenericPlugin
{
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path);
        if ($success && $this->getEnabled()) {
            HookRegistry::register('Request::redirect', [$this, 'redirectAuthorAfterLogin']);
            HookRegistry::register('TemplateManager::display', [$this, 'addChangesOnTemplateDisplaying']);
            HookRegistry::register('LoadComponentHandler', [$this, 'setupTabHandler']);
            HookRegistry::register('LoadHandler', [$this, 'addPageHandler']);
            HookRegistry::register('Schema::get::author', [$this, 'editAuthorSchema']);
            HookRegistry::register('Schema::get::demographicQuestion', [$this, 'addCustomSchema']);
            HookRegistry::register('Schema::get::demographicResponse', [$this, 'addCustomSchema']);
            HookRegistry::register('Schema::get::demographicResponseOption', [$this, 'addCustomSchema']);
            HookRegistry::register('EditorAction::recordDecision', [$this, 'requestDataExternalContributors']);
            HookRegistry::register('userdetailsform::execute', [$this, 'checkMigrateResponsesOrcid']);

            $defaultQuestionsCreator = new DefaultQuestionsCreator();
            $defaultQuestionsCreator->createDefaultQuestions();
        }
        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.demographicData.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.demographicData.description');
    }

    public function getInstallEmailTemplatesFile()
    {
        return $this->getPluginPath() . '/emailTemplates.xml';
    }

    public function getCanEnable()
    {
        $request = \Application::get()->getRequest();
        return $request->getContext() !== null;
    }

    public function getCanDisable()
    {
        $request = \Application::get()->getRequest();
        return $request->getContext() !== null;
    }

    public function editAuthorSchema(string $hookName, array $params): bool
    {
        $schema = &$params[0];

        $schema->properties->{'demographicToken'} = (object) [
            'type' => 'string',
            'apiSummary' => true,
            'validation' => ['nullable'],
        ];
        $schema->properties->{'demographicOrcid'} = (object) [
            'type' => 'string',
            'apiSummary' => true,
            'validation' => ['nullable'],
        ];

        return false;
    }

    public function addCustomSchema(string $hookName, array $params): bool
    {
        $schemaName = explode('::', $hookName)[2];
        $schema = &$params[0];
        $schema = $this->getJsonSchema($schemaName);

        return true;
    }

    private function getJsonSchema(string $schemaName): ?\stdClass
    {
        $schemaFile = sprintf(
            '%s/plugins/generic/demographicData/schemas/%s.json',
            BASE_SYS_DIR,
            $schemaName
        );
        if (file_exists($schemaFile)) {
            $schema = json_decode(file_get_contents($schemaFile));
            if (!$schema) {
                throw new \Exception(
                    'Schema failed to decode. This usually means it is invalid JSON. Requested: '
                    . $schemaFile
                    . '. Last JSON error: '
                    . json_last_error()
                );
            }
        }
        return $schema;
    }

    public function setupTabHandler($hookName, $params)
    {
        $component = & $params[0];
        if ($component == 'plugins.generic.demographicData.classes.controllers.TabHandler') {
            return true;
        }
        return false;
    }

    public function addPageHandler($hookName, $params)
    {
        $page = $params[0];
        $op = $params[1];

        if ($page == 'demographicQuestionnaire') {
            define('HANDLER_CLASS', 'APP\plugins\generic\demographicData\pages\demographic\QuestionnaireHandler');
            return true;
        }

        if ($page === 'user' && $op === 'register') {
            define('HANDLER_CLASS', 'APP\plugins\generic\demographicData\pages\user\CustomRegistrationHandler');
            return true;
        }
        return false;
    }

    public function redirectAuthorAfterLogin(string $hookName, array $params)
    {
        $url = &$params[0];
        if (strpos($url, '/submissions') === false) {
            return;
        }

        $request = Application::get()->getRequest();
        if ($this->userShouldBeRedirected($request)) {
            $url = $request->getDispatcher()->url($request, ROUTE_PAGE, null, 'user', 'profile');
        }
    }

    public function addChangesOnTemplateDisplaying(string $hookName, array $params)
    {
        $templateMgr = $params[0];
        $template = $params[1];
        
        if ($template === 'user/profile.tpl') {
            $templateFilterDispatcher = new TemplateFilterDispatcher($this);
            $templateFilterDispatcher->dispatch($templateMgr);
        }

        if ($template === 'dashboard/index.tpl') {
            $request = Application::get()->getRequest();
            if ($this->userShouldBeRedirected($request)) {
                $request->redirect(null, 'user', 'profile');
            }
        }
    }

    private function userShouldBeRedirected($request)
    {
        $context = $request->getContext();
        $user = $request->getUser();

        $demographicDataDao = new DemographicDataDAO();
        $userConsent = $demographicDataDao->getDemographicConsent($context->getId(), $user->getId());

        return is_null($userConsent) && $this->userIsAuthor($user, $context);
    }

    private function userIsAuthor($user, $context)
    {
        $userRoles = $user->getRoles($context->getId());
        $userRoles = array_map(function ($role) {
            return $role->getRoleId();
        }, $userRoles);
        $authorRoles = [ROLE_ID_AUTHOR, ROLE_ID_READER];

        return !empty(array_intersect($userRoles, $authorRoles)) && empty(array_diff($userRoles, $authorRoles));
    }

    public function getInstallMigration(): Migration
    {
        return new SchemaMigration();
    }

    public function getActions($request, $actionArgs)
    {
        $router = $request->getRouter();

        import('lib.pkp.classes.linkAction.request.AjaxModal');
        return array_merge(
            array(
                new \LinkAction(
                    'settings',
                    new \AjaxModal(
                        $router->url(
                            $request,
                            null,
                            null,
                            'manage',
                            null,
                            array(
                                'verb' => 'settings',
                                'plugin' => $this->getName(),
                                'category' => 'generic'
                            )
                        ),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                ),
            ),
            parent::getActions($request, $actionArgs)
        );
    }

    public function manage($args, $request)
    {
        $context = $request->getContext();
        $contextId = ($context == null) ? 0 : $context->getId();

        switch ($request->getUserVar('verb')) {
            case 'settings':
                $templateMgr = \TemplateManager::getManager();
                $templateMgr->registerPlugin('function', 'plugin_url', array($this, 'smartyPluginUrl'));
                $apiOptions = [
                    OrcidClient::ORCID_API_URL_PUBLIC => 'plugins.generic.demographicData.settings.orcidAPIPath.public',
                    OrcidClient::ORCID_API_URL_PUBLIC_SANDBOX => 'plugins.generic.demographicData.settings.orcidAPIPath.publicSandbox',
                    OrcidClient::ORCID_API_URL_MEMBER => 'plugins.generic.demographicData.settings.orcidAPIPath.member',
                    OrcidClient::ORCID_API_URL_MEMBER_SANDBOX => 'plugins.generic.demographicData.settings.orcidAPIPath.memberSandbox'
                ];
                $templateMgr->assign('orcidApiUrls', $apiOptions);

                $form = new DemographicDataSettingsForm($this, $contextId);
                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new \JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }
                return new \JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    public function requestDataExternalContributors(string $hookName, array $params)
    {
        $submission = $params[0];
        $decision = $params[1];

        if ($decision['decision'] != SUBMISSION_EDITOR_DECISION_ACCEPT) {
            return;
        }

        $dataCollectionEmailSender = new DataCollectionEmailSender();
        $dataCollectionEmailSender->sendRequestDataCollectionEmails($submission->getId());
    }

    public function checkMigrateResponsesOrcid(string $hookName, array $params)
    {
        $form = $params[0];
        $user = $form->user;

        if (!$user) {
            return;
        }

        $userOrcid = $user->getOrcid();

        if ($userOrcid) {
            $context = \Application::get()->getRequest()->getContext();
            $demographicDataService = new DemographicDataService();
            $demographicDataService->migrateResponsesByUserIdentifier($context, $user, 'orcid');
        }
    }
}
