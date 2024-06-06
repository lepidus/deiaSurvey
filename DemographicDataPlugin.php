<?php

namespace APP\plugins\generic\demographicData;

use PKP\plugins\GenericPlugin;
use APP\core\Application;
use Illuminate\Database\Migrations\Migration;
use PKP\plugins\Hook;
use APP\plugins\generic\demographicData\classes\migrations\SchemaMigration;
use APP\plugins\generic\demographicData\classes\DemographicDataDAO;
use APP\plugins\generic\demographicData\classes\facades\Repo;

class DemographicDataPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path);
        if ($success && $this->getEnabled()) {
            Hook::add('TemplateManager::display', [$this, 'addChangesToUserProfilePage']);
            Hook::add('LoadComponentHandler', [$this, 'setupHandler']);
            Hook::add('Schema::get::demographicQuestion', [$this, 'addDemographicQuestionSchema']);
            Hook::add('Schema::get::demographicResponse', [$this, 'addDemographicResponseSchema']);

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

    public function addChangesToUserProfilePage(string $hookName, array $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];
        if ($template === 'user/profile.tpl') {
            $templateMgr->registerFilter('output', [$this, 'demographicDataTabFilter']);

            $request = Application::get()->getRequest();
            $contextId = $request->getContext()->getId();
            $userId = $request->getUser()->getId();
            $demographicDataDao = new DemographicDataDAO();
            $consent = $demographicDataDao->getDemographicConsent($contextId, $userId);

            if (is_null($consent)) {
                $templateMgr->registerFilter('output', [$this, 'requestMessageFilter']);
            }
        }
    }

    public function demographicDataTabFilter($output, $templateMgr)
    {
        $regexListItemTabPosition = '/<div[^>]+id="profileTabs"[^>]*>.*?<ul[^>]*>((?:(?!<\/ul>).)*?<li>\s*<a[^>]*?name="(?:apiSettings)"[^>]*?>.*?<\/li>)/s';
        if (preg_match($regexListItemTabPosition, $output, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0][0];
            $offset = $matches[0][1];
            $newOutput = substr($output, 0, $offset + strlen($match));
            $newOutput .= $templateMgr->fetch($this->getTemplateResource('demographicDataTab.tpl'));
            $newOutput .= substr($output, $offset + strlen($match));
            $output = $newOutput;
            $templateMgr->unregisterFilter('output', [$this, 'demographicDataTabFilter']);
        }
        return $output;
    }

    public function requestMessageFilter($output, $templateMgr)
    {
        $profileTabsPattern = '/<div[^>]+id="profileTabs"/';
        if (preg_match($profileTabsPattern, $output, $matches, PREG_OFFSET_CAPTURE)) {
            $offset = $matches[0][1];

            $newOutput = substr($output, 0, $offset);
            $newOutput .= $templateMgr->fetch($this->getTemplateResource('requestMessage.tpl'));
            $newOutput .= substr($output, $offset);

            $output = $newOutput;
            $templateMgr->unregisterFilter('output', [$this, 'requestMessageFilter']);
        }
        return $output;
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
}
