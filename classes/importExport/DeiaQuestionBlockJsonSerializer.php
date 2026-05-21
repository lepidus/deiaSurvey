<?php

namespace APP\plugins\generic\deiaSurvey\classes\importExport;

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
            'title' => $block->getData('title'),
            'description' => $block->getData('description'),
            'active' => (bool) $block->getActive(),
            'sequence' => $block->getSequence(),
            'questions' => array_map([$this, 'serializeQuestion'], (array) $block->getData('questions')),
        ];
    }

    private function serializeQuestion($question): array
    {
        return [
            'questionType' => $question->getQuestionType(),
            'questionText' => $question->getData('questionText'),
            'questionDescription' => $question->getData('questionDescription'),
            'sequence' => $question->getSequence(),
            'responseOptions' => array_map(
                [$this, 'serializeResponseOption'],
                (array) $question->getData('responseOptions')
            ),
        ];
    }

    private function serializeResponseOption($responseOption): array
    {
        return [
            'optionText' => $responseOption->getData('optionText'),
            'hasInputField' => $responseOption->hasInputField(),
            'sequence' => $responseOption->getData('sequence'),
        ];
    }
}
