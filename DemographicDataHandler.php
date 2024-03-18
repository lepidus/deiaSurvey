<?php

namespace APP\plugins\generic\demographicData;

use APP\handler\Handler;
use PKP\core\JSONMessage;
use APP\plugins\generic\demographicData\DemographicDataForm;

class DemographicDataHandler extends Handler
{
    public function demographicData($args, $request)
    {
        $form = new DemographicDataForm();
        return new JSONMessage(true, $form->fetch($request));
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\demographicData\DemographicDataHandler', '\DemographicDataHandler');
}
