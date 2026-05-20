<?php

namespace APP\plugins\generic\deiaSurvey;

use APP\core\Application;
use APP\notification\Notification;
use APP\notification\NotificationManager;
use APP\plugins\generic\deiaSurvey\classes\DataEncryption;
use APP\plugins\generic\deiaSurvey\classes\DefaultQuestionsCreator;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\classes\migrations\SchemaMigration;
use APP\plugins\generic\deiaSurvey\classes\observers\listeners\MigrateResponsesOnRegistration;
use APP\plugins\generic\deiaSurvey\classes\OrcidClient;
use APP\plugins\generic\deiaSurvey\report\DeiaSurveyReportPlugin;
use APP\template\TemplateManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Event;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use PKP\security\Validation;

class DeiaSurveyPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path);
        $encrypter = new DataEncryption();

        if ($success && $this->getEnabled() && $encrypter->secretConfigExists()) {
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
        Hook::add('Schema::get::deiaQuestionBlock', [$this, 'addCustomSchema']);
        Hook::add('Schema::get::deiaQuestion', [$this, 'addCustomSchema']);
        Hook::add('Schema::get::deiaResponse', [$this, 'addCustomSchema']);
        Hook::add('Schema::get::deiaResponseOption', [$this, 'addCustomSchema']);
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

            $encrypter = new DataEncryption();
            if (!$encrypter->secretConfigExists()) {
                $currentUser = Application::get()->getRequest()->getUser();
                $notificationMgr = new NotificationManager();
                $notificationMessage = 'plugins.generic.deiaSurvey.settings.encryptionSecretNotDefined';
                $notificationMgr->createTrivialNotification($currentUser->getId(), Notification::NOTIFICATION_TYPE_WARNING, ['contents' => __($notificationMessage)]);
            }
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

    public function setupTabHandler($hookName, $params)
    {
        $component = &$params[0];
        $allowedComponents = [
            'plugins.generic.deiaSurvey.classes.controllers.TabHandler',
            'plugins.generic.deiaSurvey.classes.controllers.grid.deiaQuestionBlock.DeiaQuestionBlockGridHandler',
            'plugins.generic.deiaSurvey.classes.controllers.grid.deiaQuestion.DeiaQuestionGridHandler',
            'plugins.generic.deiaSurvey.classes.controllers.listbuilder.deiaQuestion.'
                . 'DeiaQuestionResponseOptionListbuilderHandler',
        ];

        if (in_array($component, $allowedComponents)) {
            return true;
        }
        return false;
    }

    public function addPageHandler($hookName, $params)
    {
        $page = $params[0];
        if ($page == 'deiaQuestionnaire') {
            define('HANDLER_CLASS', 'APP\plugins\generic\deiaSurvey\pages\deia\QuestionnaireHandler');
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
            $url = $request->getDispatcher()->url($request, Application::ROUTE_PAGE, null, 'user', 'profile');
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

        return !$userHasConsent && !Validation::isSiteAdmin();
    }

    public function getInstallMigration(): Migration
    {
        return new SchemaMigration();
    }

    public function getActions($request, $actionArgs)
    {
        $router = $request->getRouter();
        return array_merge(
            [
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $router->url(
                            $request,
                            null,
                            null,
                            'manage',
                            null,
                            [
                                'verb' => 'settings',
                                'plugin' => $this->getName(),
                                'category' => 'generic',
                                'method' => 'display'
                            ]
                        ),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                ),
            ],
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
                $templateMgr->registerPlugin('function', 'plugin_url', [$this, 'smartyPluginUrl']);
                $method = $request->getUserVar('method') ?? 'display';

                if ($method === 'display') {
                    $templateMgr->assign('pluginName', $this->getName());
                    return new JSONMessage(
                        true,
                        $templateMgr->fetch($this->getTemplateResource('settings/index.tpl'))
                    );
                }

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
            $deiaDataService = new DeiaDataService();
            $deiaDataService->migrateResponsesByUserIdentifier($context, $user, 'orcid');
        }
    }
}
