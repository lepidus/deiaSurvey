<?php

namespace APP\plugins\generic\deiaSurvey\classes\importExport;

use PKP\facades\Locale;

class DeiaQuestionBlockJsonSerializer
{
    public function serializeBlocks(array $blocks): array
    {
        return [
            'schemaVersion' => '1.0',
            'plugin' => 'deiaSurvey',
            'blocks' => array_map([$this, 'serializeBlock'], $blocks),
        ];
    }

    private function serializeBlock($block): array
    {
        return [
            'title' => $this->serializeTextualData($block->getData('title')),
            'description' => $this->serializeTextualData($block->getData('description')),
            'active' => (bool) $block->getActive(),
            'sequence' => $block->getSequence(),
            'questions' => array_map([$this, 'serializeQuestion'], (array) $block->getData('questions')),
        ];
    }

    private function serializeQuestion($question): array
    {
        $questionText = $this->serializeTextualData($question->getData('questionText'));
        $questionDescription = $this->serializeTextualData($question->getData('questionDescription'));

        return [
            'questionType' => $question->getQuestionType(),
            'questionText' => $questionText,
            'questionDescription' => $questionDescription,
            'sequence' => $question->getSequence(),
            'responseOptions' => array_map(
                [$this, 'serializeResponseOption'],
                (array) $question->getData('responseOptions')
            ),
        ];
    }

    private function serializeResponseOption($responseOption): array
    {
        $optionText = $this->serializeTextualData($responseOption->getData('optionText'));

        return [
            'optionText' => $optionText,
            'hasInputField' => $responseOption->hasInputField(),
            'sequence' => $responseOption->getData('sequence'),
        ];
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
