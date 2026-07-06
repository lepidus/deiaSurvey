<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers;

use APP\handler\Handler;
use APP\notification\NotificationManager;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\classes\form\QuestionsForm;
use PKP\core\JSONMessage;

class TabHandler extends Handler
{
    public function deiaSurvey($args, $request)
    {
        $context = $request->getContext();
        $deiaDataService = new DeiaDataService();

        if (!$context || !$deiaDataService->hasActiveQuestionBlocks($context->getId())) {
            return new JSONMessage(false);
        }

        $form = new QuestionsForm($request);
        $form->initData();
        return new JSONMessage(true, $form->fetch($request));
    }

    public function saveDeiaData($args, $request)
    {
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
