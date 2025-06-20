<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers;

use APP\handler\Handler;
use PKP\core\JSONMessage;
use APP\plugins\generic\deiaSurvey\classes\form\QuestionsForm;
use APP\notification\NotificationManager;

class TabHandler extends Handler
{
    public function deiaSurvey($args, $request)
    {
        $form = new QuestionsForm($request);
        $form->initData();
        return new JSONMessage(true, $form->fetch($request));
    }

    public function saveDemographicData($args, $request)
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
