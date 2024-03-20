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

    public function getLocalizedQuestionText()
    {
        return $this->getData('questionText');
    }

    public function setQuestionText($title, $locale)
    {
        $this->setData('questionText', $title, $locale);
    }

    public function getLocalizedQuestionDescription()
    {
        return $this->getData('questionDescription');
    }

    public function setQuestionDescription($descriptionText, $locale)
    {
        $this->setData('questionDescription', $descriptionText, $locale);
    }
}
