<?php

require_once('autoload.php');

use PKP\plugins\GenericPlugin;
use Illuminate\Database\Migrations\Migration;
use APP\plugins\generic\deiaSurvey\classes\DataEncryption;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\classes\migrations\SchemaMigration;
use APP\plugins\generic\deiaSurvey\classes\DefaultQuestionsCreator;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO;
use APP\plugins\generic\deiaSurvey\classes\OrcidClient;
use APP\plugins\generic\deiaSurvey\DeiaSurveySettingsForm;
use APP\plugins\generic\deiaSurvey\report\DeiaSurveyReportPlugin;

class DeiaSurveyPlugin extends \GenericPlugin
{
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path);
        $encrypter = new DataEncryption();

        if ($success && $this->getEnabled() && $encrypter->secretConfigExists()) {
            HookRegistry::register('Request::redirect', [$this, 'redirectUserAfterLogin']);
            HookRegistry::register('LoadComponentHandler', [$this, 'setupHandlers']);
            HookRegistry::register('LoadHandler', [$this, 'addPageHandler']);
            HookRegistry::register('Schema::get::author', [$this, 'editAuthorSchema']);
            HookRegistry::register('userdetailsform::execute', [$this, 'checkMigrateResponsesOrcid']);
            $this->registerHooksForCustomSchemas();

            $context = Application::get()->getRequest()->getContext();
            if (!is_null($context)) {
                $this->loadDispatcherClasses();
                $this->registerReportPlugin();
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

    public function registerReportPlugin()
    {
        if (Validation::isSiteAdmin()) {
            $reportPlugin = new DeiaSurveyReportPlugin();
            PluginRegistry::register('reports', $reportPlugin, $this->getPluginPath());
        }
    }

    private function registerHooksForCustomSchemas()
    {
        HookRegistry::register('Schema::get::deiaQuestionBlock', [$this, 'addCustomSchema']);
        HookRegistry::register('Schema::get::deiaQuestion', [$this, 'addCustomSchema']);
        HookRegistry::register('Schema::get::deiaResponse', [$this, 'addCustomSchema']);
        HookRegistry::register('Schema::get::deiaResponseOption', [$this, 'addCustomSchema']);
    }

    public function getInstallEmailTemplatesFile()
    {
        return $this->getPluginPath() . '/emailTemplates.xml';
    }

    public function setEnabled($enabled)
    {
        $contextId = $this->getCurrentContextId();

        if ($enabled && $contextId != CONTEXT_SITE) {
            $defaultQuestionsCreator = new DefaultQuestionsCreator();

            $this->registerHooksForCustomSchemas();
            $defaultQuestionsCreator->createDefaultQuestions($contextId);

            $encrypter = new DataEncryption();
            if (!$encrypter->secretConfigExists()) {
                $currentUser = Application::get()->getRequest()->getUser();
                $notificationMgr = new NotificationManager();
                $notificationMessage = 'plugins.generic.deiaSurvey.settings.encryptionSecretNotDefined';
                $notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_WARNING, ['contents' => __($notificationMessage)]);
            }
        }

        parent::setEnabled($enabled);
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

        $schema->properties->{'deiaToken'} = (object) [
            'type' => 'string',
            'apiSummary' => true,
            'validation' => ['nullable'],
        ];
        $schema->properties->{'deiaOrcid'} = (object) [
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

    public function setupHandlers($hookName, $params)
    {
        $component = &$params[0];
        $handledComponents = [
            'plugins.generic.deiaSurvey.classes.controllers.TabHandler',
            'plugins.generic.deiaSurvey.classes.controllers.grid.deiaQuestionBlock.DeiaQuestionBlockGridHandler',
            'plugins.generic.deiaSurvey.classes.controllers.grid.deiaQuestion.DeiaQuestionGridHandler',
            'plugins.generic.deiaSurvey.classes.controllers.listbuilder.deiaQuestion.'
                . 'DeiaQuestionResponseOptionListbuilderHandler',
        ];
        return in_array($component, $handledComponents, true);
    }

    public function addPageHandler($hookName, $params)
    {
        $page = $params[0];
        $op = $params[1];

        if ($page == 'deiaQuestionnaire') {
            define('HANDLER_CLASS', 'APP\plugins\generic\deiaSurvey\pages\deia\QuestionnaireHandler');
            return true;
        }

        if ($page === 'user' && $op === 'register') {
            define('HANDLER_CLASS', 'APP\plugins\generic\deiaSurvey\pages\user\CustomRegistrationHandler');
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

        $deiaDataDao = new DeiaDataDAO();
        $userHasConsent = $deiaDataDao->userHasDeiaConsent($user->getId());

        return !$userHasConsent && $this->userHasMandatoryFilling($user, $context);
    }

    private function userHasMandatoryFilling($user, $context)
    {
        $userRoles = $user->getRoles(CONTEXT_SITE);
        $userRoles = array_map(function ($role) {
            return $role->getRoleId();
        }, $userRoles);

        return !in_array(ROLE_ID_SITE_ADMIN, $userRoles);
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
                            [
                                'verb' => 'settings',
                                'method' => 'display',
                                'plugin' => $this->getName(),
                                'category' => 'generic'
                            ]
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
        $templateMgr = \TemplateManager::getManager($request);

        if ($request->getUserVar('verb') == 'settings') {
            switch ($request->getUserVar('method')) {
                case 'display':
                    $templateMgr->assign('pluginName', $this->getName());
                    return new \JSONMessage(true, $templateMgr->fetch($this->getTemplateResource('settings/index.tpl')));
                case 'form':
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
                            return new \JSONMessage(true);
                        }
                    } else {
                        $form->initData();
                    }
                    return new \JSONMessage(true, $form->fetch($request));
            }
        }

        return parent::manage($args, $request);
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
            $deiaDataService = new DeiaDataService();
            $deiaDataService->migrateResponsesByUserIdentifier($context, $user, 'orcid');
        }
    }
}
