<?php

namespace APP\plugins\generic\demographicData\demographicData;

use APP\handler\Handler;
use PKP\core\JSONMessage;
use APP\plugins\generic\demographicData\demographicData\QuestionsForm;

class TabHandler extends Handler
{
    public function demographicData($args, $request)
    {
        $form = new QuestionsForm();
        return new JSONMessage(true, $form->fetch($request));
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\demographicData\demographicData\TabHandler', '\TabHandler');
}
