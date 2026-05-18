<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaResponseOption;

use APP\plugins\generic\deiaSurvey\classes\traits\DeiaModelsTrait;

class DeiaResponseOption extends \DataObject
{
    use DeiaModelsTrait;

    public function getDeiaQuestionId(): int
    {
        return $this->getData('deiaQuestionId');
    }

    public function setDeiaQuestionId(int $deiaQuestionId)
    {
        $this->setData('deiaQuestionId', $deiaQuestionId);
    }

    public function getSequence()
    {
        return $this->getData('sequence');
    }

    public function setSequence($sequence)
    {
        $this->setData('sequence', $sequence);
    }

    public function isTranslated(): ?bool
    {
        return $this->getData('isTranslated');
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
