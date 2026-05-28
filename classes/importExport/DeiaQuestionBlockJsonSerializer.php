<?php

namespace APP\plugins\generic\deiaSurvey\classes\importExport;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use PKP\facades\Locale;

class DeiaQuestionBlockJsonSerializer
{
    public function serializeBlocks(array $blocks): array
    {
        return [
            'plugin' => 'deiaSurvey',
            'blocks' => array_map([$this, 'serializeBlock'], array_values($blocks)),
        ];
    }

    private function serializeBlock($block): array
    {
        return [
            'title' => $this->serializeTextualData($block->getData('title')),
            'description' => $this->serializeTextualData($block->getData('description')),
            'questions' => array_map([$this, 'serializeQuestion'], array_values((array) $block->getData('questions'))),
        ];
    }

    private function serializeQuestion($question): array
    {
        $questionText = $this->serializeTextualData($question->getData('questionText'));
        $questionDescription = $this->serializeTextualData($question->getData('questionDescription'));

        return [
            'questionType' => $this->serializeQuestionType((int) $question->getQuestionType()),
            'questionText' => $questionText,
            'questionDescription' => $questionDescription,
            'responseOptions' => array_map(
                [$this, 'serializeResponseOption'],
                array_values((array) $question->getData('responseOptions'))
            ),
        ];
    }

    private function serializeResponseOption($responseOption): array
    {
        $optionText = $this->serializeTextualData($responseOption->getData('optionText'));

        return [
            'optionText' => $optionText,
            'hasInputField' => $responseOption->hasInputField(),
        ];
    }

    private function serializeQuestionType(int $questionType): string
    {
        $questionTypeConstants = array_flip(DeiaQuestion::getQuestionTypeConstants());

        return $questionTypeConstants[$questionType] ?? (string) $questionType;
    }

    private function serializeTextualData($textualData): array
    {
        if (is_array($textualData)) {
            return array_map(
                fn ($value): string => $this->translateIfKey((string) $value),
                $textualData
            );
        }

        return [
            Locale::getLocale() => $this->translateIfKey((string) $textualData),
        ];
    }

    private function translateIfKey(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $translatedValue = Locale::get($value);

        return $translatedValue === '##' . htmlentities($value) . '##'
            ? $value
            : $translatedValue;
    }
}
