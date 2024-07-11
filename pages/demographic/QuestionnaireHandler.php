<?php

namespace APP\plugins\generic\demographicData\pages\demographic;

use APP\handler\Handler;
use APP\core\Application;
use APP\template\TemplateManager;
use PKP\plugins\PluginRegistry;
use PKP\config\Config;
use APP\plugins\generic\demographicData\classes\facades\Repo;
use APP\plugins\generic\demographicData\classes\DemographicDataService;
use APP\plugins\generic\demographicData\classes\OrcidClient;

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

        $demographicDataService  = new DemographicDataService();

        if ($demographicDataService->authorAlreadyAnsweredQuestionnaire($author)) {
            $message = __('plugins.generic.demographicData.questionnairePage.alreadyAnswered');
            $templateMgr->assign('messageToDisplay', $message);
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

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

        $demographicDataService  = new DemographicDataService();
        if (
            !$demographicDataService->authorAlreadyAnsweredQuestionnaire($author)
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

        $responsesExternalId = $author->getData('email');
        $responsesExternalType = 'email';

        if (!is_null($author->getData('demographicOrcid'))) {
            $responsesExternalId = $author->getData('demographicOrcid');
            $responsesExternalType = 'orcid';
        }

        $demographicDataService  = new DemographicDataService();
        $demographicDataService->registerExternalAuthorResponses($responsesExternalId, $responsesExternalType, $responses);

        Repo::author()->edit($author, ['demographicToken' => null, 'demographicOrcid' => null]);

        $plugin = PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->display($plugin->getTemplateResource('questionnairePage/saveSuccess.tpl'));
    }

    public function orcidVerify($args, $request)
    {
        $author = Repo::author()->get($request->getUserVar('authorId'));
        $plugin = PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        $templateMgr = TemplateManager::getManager($request);
        $contextId = $request->getContext()->getId();

        if ($request->getUserVar('error') == 'access_denied') {
            $message = __('plugins.generic.demographicData.questionnairePage.orcidAccessDenied');
            $templateMgr->assign('messageToDisplay', $message);
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        try {
            $code = $request->getUserVar('code');
            $orcidClient = new OrcidClient($plugin, $contextId);
            $authorOrcid = $orcidClient->requestOrcid($code);
        } catch (\GuzzleHttp\Exception\RequestException $exception) {
            $message = __('plugins.generic.demographicData.questionnairePage.orcidAuthError');
            $templateMgr->assign('messageToDisplay', $message);
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        if (strlen($authorOrcid) == 0) {
            return;
        }

        $isSandBox = $plugin->getSetting($contextId, 'orcidAPIPath') == OrcidClient::ORCID_API_URL_MEMBER_SANDBOX ||
            $plugin->getSetting($contextId, 'orcidAPIPath') == OrcidClient::ORCID_API_URL_PUBLIC_SANDBOX;
        $authorOrcidUri = ($isSandBox ? OrcidClient::ORCID_URL_SANDBOX : OrcidClient::ORCID_URL) . $authorOrcid;

        Repo::author()->edit($author, ['demographicOrcid' => $authorOrcidUri]);

        $request->redirect(null, null, 'index', null, ['authorId' => $author->getId(), 'authorToken' => $request->getUserVar('authorToken')]);
    }
}
