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
        $context = $request->getContext();

        $queryParams = $request->getQueryArray();
        $author = Repo::author()->get((int) $queryParams['authorId']);
        $authorToken = $queryParams['authorToken'];

        if ($this->authorAlreadyAnsweredQuestionnaire($author)) {
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/alreadyAnswered.tpl'));
        }

        $demographicDataService  = new DemographicDataService();
        $questions = $demographicDataService->retrieveAllQuestions($context->getId());
        $templateMgr->assign([
            'questions' => $questions,
            'authorId' => $author->getId(),
            'authorToken' => $authorToken
        ]);

        return $templateMgr->display($plugin->getTemplateResource('questionnairePage/index.tpl'));
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $queryParams = $request->getQueryArray();

        if (empty($queryParams) or !isset($queryParams['authorId']) or !isset($queryParams['authorToken'])) {
            return false;
        }

        $author = Repo::author()->get((int) $queryParams['authorId']);
        if (is_null($author)) {
            return false;
        }

        if (
            !$this->authorAlreadyAnsweredQuestionnaire($author)
            and $author->getData('demographicToken') != $queryParams['authorToken']
        ) {
            return false;
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function saveQuestionnaire($args, $request)
    {
        $authorId = $request->getUserVar('authorId');
        $author = Repo::author()->get($authorId);

        $responses = [];
        foreach ($request->getUserVars() as $key => $value) {
            if (strpos($key, 'question-') === 0) {
                $responses[$key] = $value;
            }
        }

        $demographicDataService  = new DemographicDataService();
        $demographicDataService->registerExternalAuthorResponses($author->getData('email'), 'email', $responses);

        Repo::author()->edit($author, ['demographicToken' => null]);

        $plugin = PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->display($plugin->getTemplateResource('questionnairePage/saveSuccess.tpl'));
    }

    private function authorAlreadyAnsweredQuestionnaire($author): bool
    {
        $email = $author->getData('email');

        $countAuthorResponses = Repo::demographicResponse()
            ->getCollector()
            ->filterByExternalIds([$email])
            ->filterByExternalTypes(['email'])
            ->getCount();

        return ($countAuthorResponses > 0);
    }
}
