<?php

namespace APP\plugins\generic\deiaSurvey\classes\demographicQuestion;

use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\traits\DemographicModelsTrait;

class DemographicQuestion extends \PKP\core\DataObject
{
    use DemographicModelsTrait;

    public const TYPE_SMALL_TEXT_FIELD = 1;
    public const TYPE_TEXT_FIELD = 2;
    public const TYPE_TEXTAREA = 3;
    public const TYPE_CHECKBOXES = 4;
    public const TYPE_RADIO_BUTTONS = 5;
    public const TYPE_DROP_DOWN_BOX = 6;

    public static function getQuestionTypeConstants(): array
    {
        return [
            'TYPE_SMALL_TEXT_FIELD' => self::TYPE_SMALL_TEXT_FIELD,
            'TYPE_TEXT_FIELD' => self::TYPE_TEXT_FIELD,
            'TYPE_TEXTAREA' => self::TYPE_TEXTAREA,
            'TYPE_CHECKBOXES' => self::TYPE_CHECKBOXES,
            'TYPE_RADIO_BUTTONS' => self::TYPE_RADIO_BUTTONS,
            'TYPE_DROP_DOWN_BOX' => self::TYPE_DROP_DOWN_BOX
        ];
    }

    public function getContextId()
    {
        return $this->getData('contextId');
    }

    public function setContextId($contextId)
    {
        $this->setData('contextId', $contextId);
    }

    public function getQuestionType()
    {
        return $this->getData('questionType');
    }

    public function setQuestionType($questionType)
    {
        $this->setData('questionType', $questionType);
    }

    public function getQuestionInputType(): string
    {
        $mapTypeInput = [
            self::TYPE_SMALL_TEXT_FIELD => 'text',
            self::TYPE_TEXT_FIELD => 'text',
            self::TYPE_TEXTAREA => 'textarea',
            self::TYPE_CHECKBOXES => 'checkbox',
            self::TYPE_RADIO_BUTTONS => 'radio',
            self::TYPE_DROP_DOWN_BOX => 'select'
        ];

        return $mapTypeInput[$this->getQuestionType()];
    }

    public function isTranslated()
    {
        return $this->getData('isTranslated');
    }

    public function setIsTranslated($isTranslated)
    {
        $this->setData('isTranslated', $isTranslated);
    }

    public function isDefaultQuestion()
    {
        return $this->getData('isDefaultQuestion') ?? false;
    }

    public function setIsDefaultQuestion($isDefaultQuestion)
    {
        $this->setData('isDefaultQuestion', $isDefaultQuestion);
    }

    public function getLocalizedQuestionText()
    {
        return $this->getLocalizedTextualData('questionText');
    }

    public function setQuestionText($title, $locale = null)
    {
        $this->setTextualData('questionText', $title, $locale);
    }

    public function getLocalizedQuestionDescription()
    {
        return $this->getLocalizedTextualData('questionDescription');
    }

    public function setQuestionDescription($descriptionText, $locale = null)
    {
        $this->setTextualData('questionDescription', $descriptionText, $locale);
    }

    public function getResponseOptions()
    {
        if (is_null($this->getData('responseOptions'))) {
            $responseOptions = Repo::demographicResponseOption()->getCollector()
                ->filterByQuestionIds([$this->getId()])
                ->getMany()
                ->toArray();

            $this->setData('responseOptions', $responseOptions);
        }

        return $this->getData('responseOptions');
    }
}
