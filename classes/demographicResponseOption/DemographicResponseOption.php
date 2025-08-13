<?php

namespace APP\plugins\generic\deiaSurvey\classes\demographicResponseOption;

use APP\plugins\generic\deiaSurvey\classes\traits\DemographicModelsTrait;

class DemographicResponseOption extends \DataObject
{
    use DemographicModelsTrait;

    public function getDemographicQuestionId(): int
    {
        return $this->getData('demographicQuestionId');
    }

    public function setDemographicQuestionId(int $demographicQuestionId)
    {
        $this->setData('demographicQuestionId', $demographicQuestionId);
    }

    public function isTranslated(): bool
    {
        return $this->getData('isTranslated') ?? false;
    }

    public function setIsTranslated(bool $isTranslated)
    {
        $this->setData('isTranslated', $isTranslated);
    }

    public function getLocalizedOptionText(): string
    {
        return $this->getLocalizedTextualData('optionText');
    }

    public function setOptionText(string $text, ?string $locale = null)
    {
        $this->setTextualData('optionText', $text, $locale);
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
