<?php

// namespace APP\plugins\generic\deiaSurvey\classes\controllers;

use APP\handler\Handler;
use APP\notification\NotificationManager;
use APP\plugins\generic\deiaSurvey\classes\form\QuestionsForm;
use PKP\core\JSONMessage;

class TabHandler extends \Handler
{
    public function demographicData($args, $request)
    {
        $form = new QuestionsForm($request);
        $form->initData();
        return new \JSONMessage(true, $form->fetch($request));
    }

    public function saveDemographicData($args, $request)
    {
        $form = new QuestionsForm($request, $args);
        if ($form->validate()) {
            $form->execute();
            $notificationMgr = new \NotificationManager();
            $user = $request->getUser();
            $notificationMgr->createTrivialNotification($user->getId());
        }
        return new \JSONMessage(true);
    }
}
