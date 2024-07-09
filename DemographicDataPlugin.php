<?php

namespace APP\plugins\generic\demographicData;

use PKP\plugins\GenericPlugin;
use APP\core\Application;
use PKP\plugins\Hook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use APP\template\TemplateManager;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\core\JSONMessage;
use APP\decision\Decision;
use APP\plugins\generic\demographicData\classes\dispatchers\TemplateFilterDispatcher;
use APP\plugins\generic\demographicData\classes\migrations\SchemaMigration;
use APP\plugins\generic\demographicData\classes\observers\listeners\MigrateResponsesOnRegistration;
use APP\plugins\generic\demographicData\classes\DemographicDataDAO;
use APP\plugins\generic\demographicData\classes\DemographicDataService;
use APP\plugins\generic\demographicData\classes\facades\Repo;
use APP\plugins\generic\demographicData\classes\mail\mailables\RequestCollectionContributorData;
use APP\plugins\generic\demographicData\classes\OrcidClient;
use APP\plugins\generic\demographicData\DemographicDataSettingsForm;

class DemographicDataPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path);
        if ($success && $this->getEnabled()) {
            Hook::add('TemplateManager::display', [$this, 'addChangesToUserProfilePage']);
            Hook::add('LoadComponentHandler', [$this, 'setupTabHandler']);
            Hook::add('LoadHandler', [$this, 'addPageHandler']);
            Hook::add('Schema::get::author', [$this, 'editAuthorSchema']);
            Hook::add('Schema::get::demographicQuestion', [$this, 'addCustomSchema']);
            Hook::add('Schema::get::demographicResponse', [$this, 'addCustomSchema']);
            Hook::add('Decision::add', [$this, 'requestDataExternalContributors']);

            Event::subscribe(new MigrateResponsesOnRegistration());

            $this->addDefaultQuestions();
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
        $request = Application::get()->getRequest();
        return $request->getContext() !== null;
    }

    public function getCanDisable()
    {
        $request = Application::get()->getRequest();
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
        if ($page == 'demographicQuestionnaire') {
            define('HANDLER_CLASS', 'APP\plugins\generic\demographicData\pages\demographic\QuestionnaireHandler');
            return true;
        }
        return false;
    }

    public function addChangesToUserProfilePage(string $hookName, array $params)
    {
        $templateMgr = $params[0];
        $template = $params[1];
        if ($template === 'user/profile.tpl') {
            $templateFilterDispatcher = new TemplateFilterDispatcher($this);
            $templateFilterDispatcher->dispatch($templateMgr);
        }
    }

    public function getInstallMigration(): Migration
    {
        return new SchemaMigration();
    }

    /*
     * The following questions are for test purposes, and should be
     * replaced by the real default questions when they be ready.
    */
    private function addDefaultQuestions()
    {
        $request = Application::get()->getRequest();
        $contextId = $request->getContext()->getId();

        $demographicQuestionsCount = Repo::demographicQuestion()
            ->getCollector()
            ->filterByContextIds([$contextId])
            ->getCount();

        if ($demographicQuestionsCount == 0) {
            $firstQuestion = Repo::demographicQuestion()->newDataObject([
                'contextId' => $contextId,
                'questionText' => ['en' => 'Gender'],
                'questionDescription' => ['en' => 'With which gender do you most identify?']
            ]);
            $secondQuestion = Repo::demographicQuestion()->newDataObject([
                'contextId' => $contextId,
                'questionText' => ['en' => 'Ethnicity'],
                'questionDescription' => ['en' => 'How would you identify yourself in terms of ethnicity?']
            ]);

            Repo::demographicQuestion()->add($firstQuestion);
            Repo::demographicQuestion()->add($secondQuestion);
        }
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
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }
                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    public function requestDataExternalContributors(string $hookName, array $params)
    {
        $decision = $params[0];

        if ($decision->getData('decision') != Decision::ACCEPT and $decision->getData('decision') != Decision::SKIP_EXTERNAL_REVIEW) {
            return;
        }

        $submission = Repo::submission()->get($decision->getData('submissionId'));
        $nonRegisteredAuthors = $this->getNonRegisteredAuthors($submission);

        if (!empty($nonRegisteredAuthors)) {
            $demographicDataService  = new DemographicDataService();

            foreach ($nonRegisteredAuthors as $author) {
                if (!$demographicDataService->authorAlreadyAnsweredQuestionnaire($author)) {
                    $this->sendRequestDataCollectionEmail($submission, $author);
                }
            }
        }
    }

    private function getNonRegisteredAuthors($submission): array
    {
        $publication = $submission->getCurrentPublication();
        $nonRegisteredAuthors = [];
        $demographicDataDao = new DemographicDataDAO();

        foreach ($publication->getData('authors') as $author) {
            $authorEmail = $author->getData('email');

            if (!$demographicDataDao->thereIsUserRegistered($authorEmail)) {
                $nonRegisteredAuthors[] = $author;
            }
        }

        return $nonRegisteredAuthors;
    }

    private function sendRequestDataCollectionEmail($submission, $author)
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        $emailTemplate = Repo::emailTemplate()->getByKey(
            $context->getId(),
            'REQUEST_COLLECTION_CONTRIBUTOR_DATA'
        );
        $authorName = $author->getFullName();
        $authorEmail = $author->getData('email');

        $questionnaireUrl = $this->getQuestionnairePageUrl($request, $author);
        $emailBodyParams = [
            'orcidQuestionnaireUrl' => $questionnaireUrl,   //Will be changed in next commits
            'questionnaireUrl' => $questionnaireUrl
        ];

        $email = new RequestCollectionContributorData($context, $submission, $emailBodyParams);
        $email->from($context->getData('contactEmail'), $context->getData('contactName'));
        $email->to([['name' => $authorName, 'email' => $authorEmail]]);
        $email->subject($emailTemplate->getLocalizedData('subject'));
        $email->body($emailTemplate->getLocalizedData('body'));

        Mail::send($email);
    }

    private function getQuestionnairePageUrl($request, $author): string
    {
        $authorToken = md5(microtime() . $author->getData('email'));

        Repo::author()->edit($author, ['demographicToken' => $authorToken]);

        return $request->getDispatcher()->url(
            $request,
            Application::ROUTE_PAGE,
            null,
            'demographicQuestionnaire',
            null,
            null,
            ['authorId' => $author->getId(), 'authorToken' => $authorToken]
        );
    }
}
