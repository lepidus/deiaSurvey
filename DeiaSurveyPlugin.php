<?php

namespace APP\plugins\generic\deiaSurvey;

use PKP\plugins\GenericPlugin;
use APP\core\Application;
use PKP\plugins\Hook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Event;
use APP\template\TemplateManager;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\core\JSONMessage;
use PKP\security\Role;
use APP\plugins\generic\deiaSurvey\classes\migrations\SchemaMigration;
use APP\plugins\generic\deiaSurvey\classes\DefaultQuestionsCreator;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataDAO;
use APP\plugins\generic\deiaSurvey\classes\observers\listeners\MigrateResponsesOnRegistration;
use APP\plugins\generic\deiaSurvey\classes\OrcidClient;
use APP\plugins\generic\deiaSurvey\classes\DataCollectionEmailSender;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataService;
use APP\plugins\generic\deiaSurvey\DeiaSurveySettingsForm;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;

class DeiaSurveyPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
            Hook::add('Request::redirect', [$this, 'redirectUserAfterLogin']);
            Hook::add('LoadComponentHandler', [$this, 'setupTabHandler']);
            Hook::add('LoadHandler', [$this, 'addPageHandler']);
            Hook::add('Schema::get::author', [$this, 'editAuthorSchema']);
            Hook::add('User::edit', [$this, 'checkMigrateResponsesOrcid']);
            $this->registerHooksForCustomSchemas();

            Event::subscribe(new MigrateResponsesOnRegistration());

            $context = Application::get()->getRequest()->getContext();
            if (!is_null($context)) {
                $this->loadDispatcherClasses();
            }
        }
        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.deiaSurvey.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.deiaSurvey.description');
    }

    private function registerHooksForCustomSchemas()
    {
        Hook::add('Schema::get::demographicQuestion', [$this, 'addCustomSchema']);
        Hook::add('Schema::get::demographicResponse', [$this, 'addCustomSchema']);
        Hook::add('Schema::get::demographicResponseOption', [$this, 'addCustomSchema']);
    }

    public function getInstallEmailTemplatesFile()
    {
        return $this->getPluginPath() . '/emailTemplates.xml';
    }

    public function setEnabled($enabled)
    {
        $contextId = $this->getCurrentContextId();

        if ($enabled && $contextId != Application::CONTEXT_SITE) {
            $defaultQuestionsCreator = new DefaultQuestionsCreator();

            $this->registerHooksForCustomSchemas();
            $defaultQuestionsCreator->createDefaultQuestions($contextId);
        }

        parent::setEnabled($enabled);
    }

    public function getCanEnable()
    {
        $request = Application::get()->getRequest();
        return $request->getContext() !== null;
    }

    public function getCanDisable()
    {
        $request = Application::get()->getRequest();
        return $request->getContext() !== null;
    }

    public function loadDispatcherClasses()
    {
        $dispatcherClasses = [
            'DataCollectionDispatcher',
            'TemplateFilterDispatcher'
        ];

        foreach ($dispatcherClasses as $dispatcherClass) {
            $dispatcherClass = 'APP\plugins\generic\deiaSurvey\classes\dispatchers\\' . $dispatcherClass;
            $dispatcher = new $dispatcherClass($this);
        }
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
            '%s/plugins/generic/deiaSurvey/schemas/%s.json',
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
        if ($component == 'plugins.generic.deiaSurvey.classes.controllers.TabHandler') {
            return true;
        }
        return false;
    }

    public function addPageHandler($hookName, $params)
    {
        $page = $params[0];
        if ($page == 'demographicQuestionnaire') {
            define('HANDLER_CLASS', 'APP\plugins\generic\deiaSurvey\pages\demographic\QuestionnaireHandler');
            return true;
        }
        return false;
    }

    public function redirectUserAfterLogin(string $hookName, array $params)
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

    public function userShouldBeRedirected($request)
    {
        $context = $request->getContext();
        $user = $request->getUser();

        if (is_null($user)) {
            return false;
        }

        $demographicDataDao = new DemographicDataDAO();
        $userHasConsent = $demographicDataDao->userHasDemographicConsent($user->getId());

        return !$userHasConsent && $this->userHasMandatoryFilling($user, $context);
    }

    private function userHasMandatoryFilling($user, $context)
    {
        $userRoles = $user->getRoles(Application::CONTEXT_SITE);
        $userRoles = array_map(function ($role) {
            return $role->getRoleId();
        }, $userRoles);

        return !in_array(Role::ROLE_ID_SITE_ADMIN, $userRoles);
    }

    public function getInstallMigration(): Migration
    {
        return new SchemaMigration();
    }

    public function getActions($request, $actionArgs)
    {
        $router = $request->getRouter();
        return array_merge(
            array(
                new LinkAction(
                    'settings',
                    new AjaxModal($router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')), $this->getDisplayName()),
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
                $templateMgr = TemplateManager::getManager();
                $templateMgr->registerPlugin('function', 'plugin_url', array($this, 'smartyPluginUrl'));
                $apiOptions = [
                    OrcidClient::ORCID_API_URL_PUBLIC => 'plugins.generic.deiaSurvey.settings.orcidAPIPath.public',
                    OrcidClient::ORCID_API_URL_PUBLIC_SANDBOX => 'plugins.generic.deiaSurvey.settings.orcidAPIPath.publicSandbox',
                    OrcidClient::ORCID_API_URL_MEMBER => 'plugins.generic.deiaSurvey.settings.orcidAPIPath.member',
                    OrcidClient::ORCID_API_URL_MEMBER_SANDBOX => 'plugins.generic.deiaSurvey.settings.orcidAPIPath.memberSandbox'
                ];
                $templateMgr->assign('orcidApiUrls', $apiOptions);

                $form = new DeiaSurveySettingsForm($this, $contextId);
                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }
                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    public function checkMigrateResponsesOrcid(string $hookName, array $params)
    {
        $user = $params[0];
        $userOrcid = $user->getOrcid();

        if ($userOrcid) {
            $context = Application::get()->getRequest()->getContext();
            $demographicDataService = new DemographicDataService();
            $demographicDataService->migrateResponsesByUserIdentifier($context, $user, 'orcid');
        }
    }
}
