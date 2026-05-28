<?php

namespace APP\plugins\generic\deiaSurvey\classes\importExport;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;

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
            'title' => $block->getData('title'),
            'description' => $block->getData('description'),
            'questions' => array_map([$this, 'serializeQuestion'], array_values((array) $block->getData('questions'))),
        ];
    }

    private function serializeQuestion($question): array
    {
        return [
            'questionType' => $this->serializeQuestionType((int) $question->getQuestionType()),
            'questionText' => $question->getData('questionText'),
            'questionDescription' => $question->getData('questionDescription'),
            'responseOptions' => array_map(
                [$this, 'serializeResponseOption'],
                array_values((array) $question->getData('responseOptions'))
            ),
        ];
    }

    private function serializeResponseOption($responseOption): array
    {
        return [
            'optionText' => $responseOption->getData('optionText'),
            'hasInputField' => $responseOption->hasInputField(),
        ];
    }

    private function serializeQuestionType(int $questionType): string
    {
        $questionTypeConstants = array_flip(DeiaQuestion::getQuestionTypeConstants());

        return $questionTypeConstants[$questionType] ?? (string) $questionType;
    }
}
