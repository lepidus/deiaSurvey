<?php

namespace APP\plugins\generic\demographicData\classes\demographicResponseOption;

class DemographicResponseOption extends \PKP\core\DataObject
{
    public function getDemographicQuestionId(): int
    {
        return $this->getData('demographicQuestionId');
    }

    public function setDemographicQuestionId($demographicQuestionId)
    {
        $this->setData('demographicQuestionId', $demographicQuestionId);
    }

    public function getLocalizedOptionText()
    {
        return $this->getLocalizedData('optionText');
    }

    public function setOptionText(string $text, string $locale)
    {
        $this->setData('optionText', $text, $locale);
    }

    public function hasInputField(): bool
    {
        return $this->getData('hasInputField');
    }

    public function setHasInputField(bool $hasInputField)
    {
        $this->setData('hasInputField', $hasInputField);
    }
}
