<?php

namespace APP\plugins\generic\deiaSurvey\pages\deia;

use APP\handler\Handler;
use APP\plugins\generic\deiaSurvey\classes\EmailAssociationService;
use APP\template\TemplateManager;
use PKP\plugins\PluginRegistry;

class EmailAssociationHandler extends Handler
{
    public function index($args, $request)
    {
        $user = $request->getUser();
        if (!$user) {
            $request->redirect(null, 'login', null, null, ['source' => $request->getCompleteUrl()]);
            return;
        }

        $plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $templateMgr = TemplateManager::getManager($request);
        $context = $request->getContext();
        $userId = $request->getUserVar('userId');
        $token = $request->getUserVar('token');
        $validInput = $context && is_scalar($userId) && ctype_digit((string) $userId)
            && (int) $userId === (int) $user->getId() && is_string($token);

        if ($request->isPost() || !$validInput) {
            $associated = $validInput && $request->isPost() && $request->checkCSRF()
                && (new EmailAssociationService())->confirm($context, (int) $userId, $token);
            $templateMgr->assign('message', $associated
                ? 'plugins.generic.deiaSurvey.emailValidation.associated'
                : 'plugins.generic.deiaSurvey.emailValidation.invalid');
            return $templateMgr->display('frontend/pages/message.tpl');
        }

        $templateMgr->assign([
            'pageTitle' => 'plugins.generic.deiaSurvey.emailValidation.confirm',
            'associationUserId' => (int) $userId,
            'associationToken' => $token,
        ]);
        return $templateMgr->display($plugin->getTemplateResource('confirmEmailAssociation.tpl'));
    }
}
