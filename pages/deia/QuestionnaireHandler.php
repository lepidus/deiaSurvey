<?php

namespace APP\plugins\generic\deiaSurvey\pages\deia;

use APP\core\Application;
use APP\handler\Handler;
use APP\plugins\generic\deiaSurvey\classes\DataCollectionEmailSender;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\OrcidClient;
use APP\plugins\generic\deiaSurvey\classes\OrcidConfiguration;
use APP\plugins\generic\deiaSurvey\classes\QuestionnaireTokenService;
use APP\template\TemplateManager;
use Illuminate\Support\Facades\DB;
use PKP\plugins\PluginRegistry;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class QuestionnaireHandler extends Handler
{
    public function index($args, $request)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $templateMgr = TemplateManager::getManager($request);
        $context = $request->getContext();

        $queryParams = $request->getQueryArray();
        $author = Repo::author()->get((int) $queryParams['authorId']);

        $this->addQuestionnairePageStyleSheet($plugin, $request, $templateMgr);
        $deiaDataService = new DeiaDataService();

        $authorToken = $queryParams['authorToken'];
        if (!$this->authorTokenIsValid($author, $authorToken)) {
            if ((new QuestionnaireTokenService())->canRenew($author, $authorToken)) {
                $templateMgr->assign(['authorId' => $author->getId(), 'authorToken' => $authorToken]);
                return $templateMgr->display($plugin->getTemplateResource('questionnairePage/expiredLink.tpl'));
            }
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.accessDenied'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        if (!$deiaDataService->hasActiveQuestionBlocks($context->getId())) {
            $templateMgr->assign(
                'messageToDisplay',
                __('plugins.generic.deiaSurvey.questionnairePage.unavailable')
            );
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        $authorExternalId = $author->getData('email');
        $authorExternalType = 'email';

        if (!is_null($author->getData('deiaOrcid'))) {
            $authorExternalId = $author->getData('deiaOrcid');
            $authorExternalType = 'orcid';
        }

        $templateToDisplay = 'questionnairePage/index.tpl';
        $questions = $deiaDataService->retrieveAllQuestions($context->getId());
        $templateMgr->assign([
            'questions' => $questions,
            'authorId' => $author->getId(),
            'authorToken' => $authorToken,
            'authorExternalId' => $authorExternalId,
            'authorExternalType' => $authorExternalType,
            'questionTypeConsts' => DeiaQuestion::getQuestionTypeConstants(),
            'privacyUrl' => $this->getPrivacyUrl()
        ]);

        if ($deiaDataService->authorAlreadyAnsweredQuestionnaire($author)) {
            $templateToDisplay = 'questionnairePage/responses.tpl';
            $authorResponses = $deiaDataService->getExternalAuthorResponses($context->getId(), $authorExternalId, $authorExternalType);
            $templateMgr->assign(['responses' => $authorResponses]);
        }

        return $templateMgr->display($plugin->getTemplateResource($templateToDisplay));
    }

    private function getPrivacyUrl(): string
    {
        $request = Application::get()->getRequest();

        return $request->getDispatcher()->url(
            $request,
            Application::ROUTE_PAGE,
            null,
            'about',
            'privacy'
        );
    }

    private function authorTokenIsValid($author, $token): bool
    {
        return (new QuestionnaireTokenService())->isValid($author, $token);
    }

    private function authorBelongsToContext($author, int $contextId): bool
    {
        if (!$author) {
            return false;
        }

        $publication = Repo::publication()->get((int) $author->getData('publicationId'));
        if (!$publication) {
            return false;
        }

        $submission = Repo::submission()->get((int) $publication->getData('submissionId'));
        return $submission && (int) $submission->getData('contextId') === $contextId;
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $queryParams = $request->getQueryArray();

        if (empty($queryParams) or !isset($queryParams['authorId']) or !isset($queryParams['authorToken'])) {
            return false;
        }

        $author = Repo::author()->get((int) $queryParams['authorId']);
        $context = $request->getContext();
        if (is_null($author) || is_null($context) || !$this->authorBelongsToContext($author, $context->getId())) {
            return false;
        }
        $validToken = $this->authorTokenIsValid($author, $queryParams['authorToken']);
        $recoveryOnly = !$validToken
            && in_array($request->getRequestedOp(), ['index', 'orcidAuthorize', 'requestNewLink'], true)
            && (new QuestionnaireTokenService())->canRenew($author, $queryParams['authorToken']);
        if (!$validToken && !$recoveryOnly) {
            return false;
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function requestNewLink($args, $request)
    {
        $sent = false;
        $context = $request->getContext();
        $token = $request->getUserVar('authorToken');
        if ($request->isPost() && $request->checkCSRF() && $context && is_string($token)) {
            try {
                $sent = (new DataCollectionEmailSender())->renewQuestionnaireLink(
                    $context,
                    (int) $request->getUserVar('authorId'),
                    $token
                );
            } catch (TransportExceptionInterface $exception) {
                $sent = false;
            }
        }
        $plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $templateMgr = TemplateManager::getManager($request);
        $this->addQuestionnairePageStyleSheet($plugin, $request, $templateMgr);
        $templateMgr->assign('messageToDisplay', $sent
            ? __('plugins.generic.deiaSurvey.questionnairePage.renewalSent')
            : __('plugins.generic.deiaSurvey.questionnairePage.renewalUnavailable'));
        return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
    }

    public function saveQuestionnaire($args, $request)
    {
        $authorId = $request->getUserVar('authorId');
        $authorToken = $request->getUserVar('authorToken');
        $author = Repo::author()->get($authorId);
        $plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $templateMgr = TemplateManager::getManager($request);

        $this->addQuestionnairePageStyleSheet($plugin, $request, $templateMgr);

        if (!$request->isPost() || !$request->checkCSRF()) {
            $templateMgr->assign('messageToDisplay', __('form.csrfInvalid'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        if (!$this->authorTokenIsValid($author, $authorToken)) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.accessDenied'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        if (!$this->authorBelongsToContext($author, $request->getContext()->getId())) {
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

        if (!is_null($author->getData('deiaOrcid'))) {
            $responsesExternalId = $author->getData('deiaOrcid');
            $responsesExternalType = 'orcid';
        }

        $deiaDataService = new DeiaDataService();
        try {
            [$responses, $responseOptionsInputs] = $deiaDataService->validateResponsesForContext(
                $request->getContext()->getId(),
                $responses,
                $responseOptionsInputs,
                false
            );
        } catch (\InvalidArgumentException $exception) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.accessDenied'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        DB::transaction(function () use (
            $deiaDataService,
            $request,
            $responsesExternalId,
            $responsesExternalType,
            $responses,
            $responseOptionsInputs
        ) {
            $deiaDataService->deleteAuthorResponses(
                $request->getContext()->getId(),
                $responsesExternalId,
                $responsesExternalType
            );
            $deiaDataService->registerExternalAuthorResponses(
                $responsesExternalId,
                $responsesExternalType,
                $responses,
                $responseOptionsInputs
            );
        });

        $templateMgr->assign([
            'authorId' => $author->getId(),
            'authorToken' => $authorToken
        ]);

        return $templateMgr->display($plugin->getTemplateResource('questionnairePage/saveSuccess.tpl'));
    }

    public function deleteData($args, $request)
    {
        $authorId = $request->getUserVar('authorId');
        $authorToken = $request->getUserVar('authorToken');
        $author = Repo::author()->get($authorId);
        $plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $templateMgr = TemplateManager::getManager($request);

        $this->addQuestionnairePageStyleSheet($plugin, $request, $templateMgr);

        if (!$this->authorTokenIsValid($author, $authorToken)) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.accessDenied'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        if (!$this->authorBelongsToContext($author, $request->getContext()->getId())) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.accessDenied'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        $deiaDataService = new DeiaDataService();
        if (!$deiaDataService->authorAlreadyAnsweredQuestionnaire($author)) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.onlyWhoAnsweredCanDelete'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        if ($request->isPost()) {
            if (!$request->checkCSRF() || $request->getUserVar('confirm') !== '1') {
                $templateMgr->assign(
                    'messageToDisplay',
                    __('plugins.generic.deiaSurvey.questionnairePage.accessDenied')
                );
                return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
            }

            $contextId = $request->getContext()->getId();
            $authorExternalId = $author->getData('email');
            $authorExternalType = 'email';

            if (!is_null($author->getData('deiaOrcid'))) {
                $authorExternalId = $author->getData('deiaOrcid');
                $authorExternalType = 'orcid';
            }

            $deiaDataService->deleteAuthorResponses($contextId, $authorExternalId, $authorExternalType);
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/deleteSuccess.tpl'));
        }

        if (!$request->isGet()) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.accessDenied'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        $templateMgr->assign([
            'authorId' => $author->getId(),
            'authorToken' => $authorToken
        ]);

        return $templateMgr->display($plugin->getTemplateResource('questionnairePage/deleteData.tpl'));
    }

    public function orcidAuthorize($args, $request)
    {
        $author = Repo::author()->get((int) $request->getUserVar('authorId'));
        $token = $request->getUserVar('authorToken');
        $context = $request->getContext();
        if ($context && $this->authorBelongsToContext($author, $context->getId())
            && (new QuestionnaireTokenService())->canRenew($author, $token)) {
            return $this->index($args, $request);
        }
        if (!$context || !$this->authorTokenIsValid($author, $token)
            || !$this->authorBelongsToContext($author, $context->getId())) {
            return false;
        }

        $plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $client = new OrcidClient($plugin, $context->getId());
        $url = $client->buildOAuthUrl(['authorId' => $author->getId(), 'authorToken' => $token]);
        if ($url === '') {
            return false;
        }
        $request->redirectUrl($url);
    }

    public function orcidVerify($args, $request)
    {
        $author = Repo::author()->get($request->getUserVar('authorId'));
        $plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $templateMgr = TemplateManager::getManager($request);
        $contextId = $request->getContext()->getId();

        $this->addQuestionnairePageStyleSheet($plugin, $request, $templateMgr);

        $authorToken = $request->getUserVar('authorToken');
        $orcidClient = new OrcidClient($plugin, $contextId);
        if (!$this->authorTokenIsValid($author, $authorToken)
            || !$this->authorBelongsToContext($author, $contextId)
            || !$orcidClient->consumeOAuthState($request->getUserVar('state'), $author->getId(), $authorToken)) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.accessDenied'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        if ($request->getUserVar('error') == 'access_denied') {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.orcidAccessDenied'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        try {
            $code = $request->getUserVar('code');
            if (!is_string($code) || $code === '') {
                return false;
            }
            $authorOrcid = $orcidClient->requestOrcid($code, [
                'authorId' => $author->getId(), 'authorToken' => $authorToken,
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $exception) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.orcidAuthError'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        if (!is_string($authorOrcid) || !preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/D', $authorOrcid)) {
            return;
        }

        $orcidConfiguration = new OrcidConfiguration();
        $orcidConfiguration = $orcidConfiguration->getOrcidConfiguration($contextId);

        $isSandBox = $orcidConfiguration['apiPath'] == OrcidClient::ORCID_API_URL_MEMBER_SANDBOX ||
            $orcidConfiguration['apiPath'] == OrcidClient::ORCID_API_URL_PUBLIC_SANDBOX;
        $authorOrcidUri = ($isSandBox ? OrcidClient::ORCID_URL_SANDBOX : OrcidClient::ORCID_URL) . $authorOrcid;

        $deiaDataDao = new DeiaDataDAO();
        if ($deiaDataDao->thereIsUserWithSetting($authorOrcidUri, 'orcid')) {
            $templateMgr->assign('messageToDisplay', __('plugins.generic.deiaSurvey.questionnairePage.userWithOrcidExists'));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        // OAuth proves ownership even when this ORCID already has external responses.
        $refusalMessage = DB::transaction(function () use ($author, $authorToken, $authorOrcidUri, $contextId): ?string {
            DB::table('authors')->where('author_id', $author->getId())->lockForUpdate()->first();
            $currentAuthor = Repo::author()->get($author->getId());
            if (!$this->authorTokenIsValid($currentAuthor, $authorToken)
                || !$this->authorBelongsToContext($currentAuthor, $contextId)) {
                return 'plugins.generic.deiaSurvey.questionnairePage.accessDenied';
            }
            if ($this->answeredUnderAnotherIdentifier($currentAuthor, $authorOrcidUri, $contextId)) {
                return 'plugins.generic.deiaSurvey.questionnairePage.orcidNotLinkedAfterAnswering';
            }
            Repo::author()->edit($currentAuthor, ['deiaOrcid' => $authorOrcidUri]);
            return null;
        });
        if ($refusalMessage !== null) {
            $templateMgr->assign('messageToDisplay', __($refusalMessage));
            return $templateMgr->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
        }

        $request->redirect(null, null, 'index', null, ['authorId' => $author->getId(), 'authorToken' => $request->getUserVar('authorToken')]);
    }

    /**
     * Changing the identifier after answering would keep the answers given under the previous
     * identifier and let the author answer again under the new one, counting the author twice.
     */
    private function answeredUnderAnotherIdentifier($author, string $orcidUri, int $contextId): bool
    {
        $currentOrcid = $author->getData('deiaOrcid');
        if ($currentOrcid === $orcidUri) {
            return false;
        }

        return Repo::deiaResponse()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByExternalIds([is_null($currentOrcid) ? $author->getData('email') : $currentOrcid])
            ->filterByExternalTypes([is_null($currentOrcid) ? 'email' : 'orcid'])
            ->getCount() > 0;
    }

    private function addQuestionnairePageStyleSheet($plugin, $request, $templateMgr)
    {
        $templateMgr->addStyleSheet(
            'questionnairePageStyleSheet',
            $request->getBaseUrl() . '/' . $plugin->getPluginPath() . '/styles/questionnairePage.css',
            [
                'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
                'contexts' => ['frontend'],
                'inline' => false,
            ]
        );
    }
}
