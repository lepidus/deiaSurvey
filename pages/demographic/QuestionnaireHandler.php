<?php

namespace APP\plugins\generic\demographicData\pages\demographic;

use APP\handler\Handler;
use APP\core\Application;
use APP\template\TemplateManager;
use PKP\plugins\PluginRegistry;
use PKP\config\Config;
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
}
