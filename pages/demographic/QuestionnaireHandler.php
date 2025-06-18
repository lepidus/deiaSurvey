<?php

namespace APP\plugins\generic\deiaSurvey\pages\demographic;

use APP\core\Application;
use APP\handler\Handler;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataDAO;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataService;
use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\deiaSurvey\classes\OrcidConfiguration;
use APP\plugins\generic\deiaSurvey\classes\OrcidClient;
use APP\template\TemplateManager;
use PKP\config\Config;
use PKP\plugins\PluginRegistry;

class QuestionnaireHandler extends \Handler
{
    public function index($args, $request)
    {
        $plugin = \PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $templateMgr = \TemplateManager::getManager($request);
        $context = $request->getContext();

        $authorId = $request->getUserVar('authorId');
        $authorToken = $request->getUserVar('authorToken');

        $author = \Services::get('author')->get((int) $authorId);

        $this->addQuestionnairePageStyleSheet($plugin, $request, $templateMgr);
        $demographicDataService = new DemographicDataService();

        if (!$this->authorTokenIsValid($author, $authorToken)) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.accessDenied'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        $authorExternalId = $author->getData('email');
        $authorExternalType = 'email';

        if (!is_null($author->getData('demographicOrcid'))) {
            $authorExternalId = $author->getData('demographicOrcid');
            $authorExternalType = 'orcid';
        }

        $templateToDisplay = 'questionnairePage/index.tpl';
        $questions = $demographicDataService->retrieveAllQuestions($context->getId());
        $templateMgr->assign([
            'questions' => $questions,
            'authorId' => $author->getId(),
            'authorToken' => $authorToken,
            'authorExternalId' => $authorExternalId,
            'authorExternalType' => $authorExternalType,
            'questionTypeConsts' => DemographicQuestion::getQuestionTypeConstants(),
            'privacyUrl' => $this->getPrivacyUrl()
        ]);

        if ($demographicDataService->authorAlreadyAnsweredQuestionnaire($author)) {
            $templateToDisplay = 'questionnairePage/responses.tpl';
            $authorResponses = $demographicDataService->getExternalAuthorResponses($context->getId(), $authorExternalId, $authorExternalType);
            $templateMgr->assign(['responses' => $authorResponses]);
        }

        return $templateMgr->display($plugin->getTemplateResource($templateToDisplay));
    }

    private function getPrivacyUrl(): string
    {
        $request = \Application::get()->getRequest();

        return $request->getDispatcher()->url(
            $request,
            ROUTE_PAGE,
            null,
            'about',
            'privacy'
        );
    }

    private function authorTokenIsValid($author, $token): bool
    {
        return $author->getData('demographicToken') === $token;
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $authorId = $request->getUserVar('authorId');
        $authorToken = $request->getUserVar('authorToken');

        if (!$authorId || !$authorToken) {
            return false;
        }

        $author = \Services::get('author')->get((int) $authorId);
        if (is_null($author)) {
            return false;
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function saveQuestionnaire($args, $request)
    {
        $authorId = $request->getUserVar('authorId');
        $authorToken = $request->getUserVar('authorToken');
        $author = \Services::get('author')->get($authorId);
        $plugin = \PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $templateMgr = \TemplateManager::getManager($request);

        $this->addQuestionnairePageStyleSheet($plugin, $request, $templateMgr);

        if (!$this->authorTokenIsValid($author, $authorToken)) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.accessDenied'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        $responses = [];
        $responseOptionsInputs = [];
        foreach ($request->getUserVars() as $key => $value) {
            if (strpos($key, 'question-') === 0) {
                $responses[$key] = $value;
            } elseif (strpos($key, 'responseOptionInput-') === 0) {
                $responseOptionsInputs[$key] = $value;
            }
        }

        $responsesExternalId = $author->getData('email');
        $responsesExternalType = 'email';

        if (!is_null($author->getData('demographicOrcid'))) {
            $responsesExternalId = $author->getData('demographicOrcid');
            $responsesExternalType = 'orcid';
        }

        $demographicDataService  = new DemographicDataService();
        $demographicDataService->registerExternalAuthorResponses($responsesExternalId, $responsesExternalType, $responses, $responseOptionsInputs);

        $templateMgr->assign([
            'authorId' => $author->getId(),
            'authorToken' => $author->getData('demographicToken')
        ]);

        return $templateMgr->display($plugin->getTemplateResource('questionnairePage/saveSuccess.tpl'));
    }

    public function deleteData($args, $request)
    {
        $authorId = $request->getUserVar('authorId');
        $authorToken = $request->getUserVar('authorToken');
        $author = \Services::get('author')->get($authorId);
        $plugin = \PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $templateMgr = \TemplateManager::getManager($request);

        $this->addQuestionnairePageStyleSheet($plugin, $request, $templateMgr);

        if (!$this->authorTokenIsValid($author, $authorToken)) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.accessDenied'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        $demographicDataService  = new DemographicDataService();
        if (!$demographicDataService->authorAlreadyAnsweredQuestionnaire($author)) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.onlyWhoAnsweredCanDelete'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        if ($request->getUserVar('save')) {
            $contextId = $request->getContext()->getId();
            $authorExternalId = $author->getData('email');
            $authorExternalType = 'email';

            if (!is_null($author->getData('demographicOrcid'))) {
                $authorExternalId = $author->getData('demographicOrcid');
                $authorExternalType = 'orcid';
            }

            $demographicDataService->deleteAuthorResponses($contextId, $authorExternalId, $authorExternalType);
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/deleteSuccess.tpl'));
        }

        $templateMgr->assign([
            'authorId' => $author->getId(),
            'authorToken' => $author->getData('demographicToken')
        ]);

        return $templateMgr->display($plugin->getTemplateResource('questionnairePage/deleteData.tpl'));
    }

    public function orcidVerify($args, $request)
    {
        $author = \Services::get('author')->get($request->getUserVar('authorId'));
        $plugin = \PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $templateMgr = \TemplateManager::getManager($request);
        $contextId = $request->getContext()->getId();

        $this->addQuestionnairePageStyleSheet($plugin, $request, $templateMgr);

        if ($request->getUserVar('error') == 'access_denied') {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.orcidAccessDenied'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        try {
            $code = $request->getUserVar('code');
            $orcidClient = new OrcidClient($plugin, $contextId);
            $authorOrcid = $orcidClient->requestOrcid($code);
        } catch (\GuzzleHttp\Exception\RequestException $exception) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.orcidAuthError'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        if (strlen($authorOrcid) == 0) {
            return;
        }

        $orcidConfiguration = new OrcidConfiguration();
        $orcidConfiguration = $orcidConfiguration->getOrcidConfiguration($contextId);

        $isSandBox = $orcidConfiguration['apiPath'] == OrcidClient::ORCID_API_URL_MEMBER_SANDBOX ||
            $orcidConfiguration['apiPath'] == OrcidClient::ORCID_API_URL_PUBLIC_SANDBOX;
        $authorOrcidUri = ($isSandBox ? OrcidClient::ORCID_URL_SANDBOX : OrcidClient::ORCID_URL) . $authorOrcid;

        $demographicDataDao = new DemographicDataDAO();
        if ($demographicDataDao->thereIsUserWithSetting($authorOrcidUri, 'orcid')) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.userWithOrcidExists'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        $demographicDataService  = new DemographicDataService();
        if ($demographicDataService->authorAlreadyAnsweredQuestionnaire($author, $authorOrcidUri)) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.alreadyAnswered'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        \Services::get('author')->edit($author, ['demographicOrcid' => $authorOrcidUri], $request);

        $request->redirect(null, null, 'index', null, ['authorId' => $author->getId(), 'authorToken' => $request->getUserVar('authorToken')]);
    }

    private function addQuestionnairePageStyleSheet($plugin, $request, $templateMgr)
    {
        $templateMgr->addStyleSheet(
            'questionnairePageStyleSheet',
            $request->getBaseUrl() . '/' . $plugin->getPluginPath() . '/styles/questionnairePage.css',
            [
                'priority' => STYLE_SEQUENCE_LAST,
                'contexts' => ['frontend'],
                'inline' => false,
            ]
        );
    }
}
