<?php

namespace APP\plugins\generic\deiaSurvey\pages\deia;

import('classes.handler.Handler');

use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\classes\OrcidClient;
use APP\plugins\generic\deiaSurvey\classes\OrcidConfiguration;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

class OrcidAssociationHandler extends \Handler
{
    private const SESSION_KEY = 'deiaOrcidAssociation';

    public function index($args, $request)
    {
        $user = $request->getUser();
        $context = $request->getContext();
        if (!$user || !$context || $user->getDisabled() || !$request->isPost() || !$request->checkCSRF()) {
            return $this->message($request, false);
        }
        $user = \DAORegistry::getDAO('UserDAO')->getById($user->getId());
        if (!$user || $user->getDisabled() || !$user->getOrcid()) {
            return $this->message($request, false);
        }
        $state = bin2hex(random_bytes(32));
        $client = $this->createOrcidClient($context->getId());
        $url = $client->buildOAuthUrl(['userId' => $user->getId()], 'deiaOrcidAssociation', 'verify', $state);
        if ($url === '') {
            return $this->message($request, false);
        }
        $request->getSession()->setSessionVar(self::SESSION_KEY, [
            'userId' => $user->getId(), 'orcid' => $user->getOrcid(), 'contextId' => $context->getId(),
            'stateHash' => hash('sha256', $state), 'expiresAt' => Carbon::now()->addMinutes(10)->timestamp,
        ]);
        $request->redirectUrl($url);
    }

    public function verify($args, $request)
    {
        $pending = $request->getSession()->getSessionVar(self::SESSION_KEY);
        $request->getSession()->unsetSessionVar(self::SESSION_KEY);
        $user = $request->getUser();
        $context = $request->getContext();
        $state = $request->getUserVar('state');
        $code = $request->getUserVar('code');
        if (!$user || !$context || !is_array($pending) || !is_string($state) || !is_string($code) || $code === ''
            || $pending['userId'] !== $user->getId() || $pending['contextId'] !== $context->getId()
            || $pending['expiresAt'] <= Carbon::now()->timestamp
            || !hash_equals($pending['stateHash'], hash('sha256', $state))) {
            return $this->message($request, false);
        }
        try {
            $client = $this->createOrcidClient($context->getId());
            $orcid = $client->requestOrcid($code, ['userId' => $user->getId()], 'deiaOrcidAssociation', 'verify');
        } catch (\GuzzleHttp\Exception\RequestException $exception) {
            return $this->message($request, false);
        }
        $expected = $pending['orcid'];
        $configuration = (new OrcidConfiguration())->getOrcidConfiguration($context->getId());
        $sandbox = $configuration && in_array($configuration['apiPath'], [
            OrcidClient::ORCID_API_URL_PUBLIC_SANDBOX, OrcidClient::ORCID_API_URL_MEMBER_SANDBOX,
        ], true);
        $prefix = $sandbox ? OrcidClient::ORCID_URL_SANDBOX : OrcidClient::ORCID_URL;
        if (!is_string($orcid) || !preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/D', $orcid)
            || $prefix . $orcid !== $expected) {
            return $this->message($request, false);
        }
        $associated = DB::connection()->transaction(function () use ($user, $context, $expected): bool {
            $account = DB::table('users')->where('user_id', $user->getId())->lockForUpdate()->first();
            $current = $account ? \DAORegistry::getDAO('UserDAO')->getById($user->getId()) : null;
            if (!$current || $account->disabled || $current->getOrcid() !== $expected) {
                return false;
            }
            return (new DeiaDataService())->migrateResponsesByUserIdentifier($context, $current, 'orcid', true);
        });
        return $this->message($request, $associated);
    }

    private function message($request, bool $associated)
    {
        $plugin = \PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $template = \TemplateManager::getManager($request);
        $template->assign('messageToDisplay', $associated
            ? __('plugins.generic.deiaSurvey.orcidAssociation.associated')
            : __('plugins.generic.deiaSurvey.orcidAssociation.unavailable'));
        return $template->display($plugin->getTemplateResource('questionnairePage/displayMessage.tpl'));
    }

    protected function createOrcidClient(int $contextId): OrcidClient
    {
        return new OrcidClient(null, $contextId);
    }
}
