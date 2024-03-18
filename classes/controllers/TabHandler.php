<?php

namespace APP\plugins\generic\demographicData\classes\controllers;

use APP\handler\Handler;
use PKP\core\JSONMessage;
use APP\plugins\generic\demographicData\classes\form\QuestionsForm;

class TabHandler extends Handler
{
    public function demographicData($args, $request)
    {
        $form = new QuestionsForm();
        return new JSONMessage(true, $form->fetch($request));
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\demographicData\classes\controllers\TabHandler', '\TabHandler');
}
