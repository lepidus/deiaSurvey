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

        $queryParams = $request->getQueryArray();
        $authorId = $queryParams['authorId'];
        $authorToken = $queryParams['authorToken'];

        $demographicDataService  = new DemographicDataService();
        $questions = $demographicDataService->retrieveQuestions();
        $templateMgr->assign([
            'questions' => $questions,
            'authorId' => $authorId,
            'authorToken' => $authorToken
        ]);

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

    public function saveQuestionnaire($args, $request)
    {
        $authorId = $request->getUserVar('authorId');

        $responses = [];
        foreach ($request->getUserVars() as $key => $value) {
            if (strpos($key, 'question-') === 0) {
                $responses[$key] = $value;
            }
        }

        $demographicDataService  = new DemographicDataService();
        //Modify data structure so it can accept an e-mail address
        //$demographicDataService->registerResponse($authorEmail, $responses));

        //Code bellow will be removed soon
        $plugin = PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->display($plugin->getTemplateResource('questionnairePage.tpl'));
    }
}
