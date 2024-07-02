<?php

namespace APP\plugins\generic\demographicData\pages\demographic;

use APP\handler\Handler;
use APP\core\Application;
use APP\template\TemplateManager;
use PKP\plugins\PluginRegistry;
use PKP\config\Config;
use APP\plugins\generic\demographicData\classes\facades\Repo;
use APP\plugins\generic\demographicData\classes\DemographicDataService;

class QuestionnaireHandler extends Handler
{
    public function index($args, $request)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        $templateMgr = TemplateManager::getManager($request);

        $demographicDataService  = new DemographicDataService();
        $questions = $demographicDataService->retrieveQuestions();
        $templateMgr->assign('questions', $questions);

        return $templateMgr->display($plugin->getTemplateResource('questionnairePage.tpl'));
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $queryParams = $request->getQueryArray();

        if (empty($queryParams) or !isset($queryParams['authorId']) or !isset($queryParams['authorToken'])) {
            return false;
        }

        $author = Repo::author()->get((int) $queryParams['authorId']);
        if (is_null($author) or $author->getData('demographicToken') != $queryParams['authorToken']) {
            return false;
        }

        return parent::authorize($request, $args, $roleAssignments);
    }
}
