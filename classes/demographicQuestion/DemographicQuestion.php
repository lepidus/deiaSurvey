<?php

namespace APP\plugins\generic\demographicData\classes\demographicQuestion;

class DemographicQuestion extends \PKP\core\DataObject
{
    public function getContextId(): int
    {
        return $this->getData('contextId');
    }

    public function setContextId($contextId)
    {
        $this->setData('contextId', $contextId);
    }
}
