<?php

namespace APP\plugins\generic\demographicData\classes\demographicQuestion;

class DemographicQuestion extends \PKP\core\DataObject
{
    public function getContextId()
    {
        return $this->getData('contextId');
    }

    public function setContextId($contextId)
    {
        $this->setData('contextId', $contextId);
    }

    public function getLocalizedText()
    {
        return $this->getLocalizedData('questionText');
    }

    public function setQuestionText($title, $locale)
    {
        $this->setData('questionText', $title, $locale);
    }
}
