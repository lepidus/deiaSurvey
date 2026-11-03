<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers;

use APP\handler\Handler;
use APP\notification\NotificationManager;
use APP\plugins\generic\deiaSurvey\classes\DataCollectionEmailSender;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\classes\form\QuestionsForm;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class TabHandler extends Handler
{
    public function requestEmailValidation($args, $request)
    {
        if (!$request->isPost() || !$request->checkCSRF() || !$request->getUser() || !$request->getContext()) {
            return new JSONMessage(false);
        }

        try {
            $sent = (new DataCollectionEmailSender())->sendEmailValidation($request->getContext(), $request->getUser());
        } catch (TransportExceptionInterface $exception) {
            return new JSONMessage(false, __('email.compose.error'));
        }
        $message = $sent
            ? __('plugins.generic.deiaSurvey.emailValidation.sent')
            : __('plugins.generic.deiaSurvey.emailValidation.unavailable');
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'notificationId' => 'deiaEmailValidationResult',
            'notificationStyleClass' => $sent ? 'notifySuccess' : 'notifyWarning',
            'notificationTitle' => '',
            'notificationContents' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
        ]);
        return new JSONMessage(true, '<div class="pkp_notification" role="status">'
            . $templateMgr->fetch('controllers/notification/inPlaceNotificationContent.tpl') . '</div>');
    }

    public function deiaSurvey($args, $request)
    {
        $context = $request->getContext();
        $user = $request->getUser();
        $deiaDataService = new DeiaDataService();

        if (!$user || !$context || !$deiaDataService->hasActiveQuestionBlocks($context->getId())) {
            return new JSONMessage(false);
        }

        $deiaDataService->migrateResponsesByUserIdentifier($context, $user, 'email');

        $form = new QuestionsForm($request);
        $form->initData();
        return new JSONMessage(true, $form->fetch($request));
    }

    public function saveDeiaData($args, $request)
    {
        if (!$request->isPost()) {
            return new JSONMessage(false);
        }

        $form = new QuestionsForm($request, $args);
        if ($form->validate()) {
            $form->execute();
            $notificationMgr = new NotificationManager();
            $user = $request->getUser();
            $notificationMgr->createTrivialNotification($user->getId());
        }
        return new JSONMessage(true);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\deiaSurvey\classes\controllers\TabHandler', '\TabHandler');
}
