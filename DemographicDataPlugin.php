<?php

namespace APP\plugins\generic\demographicData;

use PKP\plugins\GenericPlugin;
use APP\core\Application;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Mail;
use PKP\plugins\Hook;
use APP\decision\Decision;
use APP\plugins\generic\demographicData\classes\dispatchers\TemplateFilterDispatcher;
use APP\plugins\generic\demographicData\classes\migrations\SchemaMigration;
use APP\plugins\generic\demographicData\classes\DemographicDataDAO;
use APP\plugins\generic\demographicData\classes\facades\Repo;
use APP\plugins\generic\demographicData\classes\mail\mailables\RequestCollectionContributorData;

class DemographicDataPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path);
        if ($success && $this->getEnabled()) {
            Hook::add('TemplateManager::display', [$this, 'addChangesToUserProfilePage']);
            Hook::add('LoadComponentHandler', [$this, 'setupHandler']);
            Hook::add('LoadHandler', [$this, 'addPageHandler']);
            Hook::add('Schema::get::demographicQuestion', [$this, 'addDemographicQuestionSchema']);
            Hook::add('Schema::get::demographicResponse', [$this, 'addDemographicResponseSchema']);
            Hook::add('Decision::add', [$this, 'requestDataExternalContributors']);

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

    public function addDemographicQuestionSchema(string $hookName, array $params): bool
    {
        $schema = &$params[0];
        $schema = $this->getJsonSchema('demographicQuestion');
        return true;
    }

    public function addDemographicResponseSchema(string $hookName, array $params): bool
    {
        $schema = &$params[0];
        $schema = $this->getJsonSchema('demographicResponse');
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

    public function setupHandler($hookName, $params)
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

    public function requestDataExternalContributors(string $hookName, array $params)
    {
        $decision = $params[0];

        if ($decision->getData('decision') != Decision::ACCEPT and $decision->getData('decision') != Decision::SKIP_EXTERNAL_REVIEW) {
            return;
        }

        $submission = Repo::submission()->get($decision->getData('submissionId'));
        $nonRegisteredAuthors = $this->getNonRegisteredAuthors($submission);

        if (!empty($nonRegisteredAuthors)) {
            foreach ($nonRegisteredAuthors as $author) {
                $this->sendRequestDataCollectionEmail($submission, $author);
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

        $email = new RequestCollectionContributorData($context, $submission, []);
        $email->from($context->getData('contactEmail'), $context->getData('contactName'));
        $email->to([['name' => $authorName, 'email' => $authorEmail]]);
        $email->subject($emailTemplate->getLocalizedData('subject'));
        $email->body($emailTemplate->getLocalizedData('body'));

        Mail::send($email);
    }
}
