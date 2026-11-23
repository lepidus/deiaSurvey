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
        $serializedQuestions = [];
        $questionPositions = [];
        $optionPositions = [];
        foreach ((array) $block->getData('questions') as $question) {
            $serialized = $this->serializeQuestion($question);
            $condition = $question->getData('showWhen');
            if ($condition) {
                [$sourceId, $optionId] = $condition;
                $serialized['showWhen'] = [$questionPositions[$sourceId], $optionPositions[$optionId]];
            }
            $questionPositions[$question->getId()] = count($serializedQuestions);
            foreach (array_values((array) $question->getData('responseOptions')) as $position => $option) {
                $optionPositions[$option->getId()] = $position;
            }
            $serializedQuestions[] = $serialized;
        }

        return [
            'title' => $block->getData('title'),
            'description' => $block->getData('description'),
            'questions' => $serializedQuestions,
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
