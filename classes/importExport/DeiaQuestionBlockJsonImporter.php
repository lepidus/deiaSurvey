<?php

namespace APP\plugins\generic\deiaSurvey\classes\importExport;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use Illuminate\Database\Capsule\Manager as DB;
use InvalidArgumentException;

class DeiaQuestionBlockJsonImporter
{
    public function import(string $json, int $contextId): array
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON file.');
        }

        $this->validatePayload($data);

        return DB::connection()->transaction(function () use ($data, $contextId) {
            $importedBlockIds = [];
            foreach ($data['blocks'] as $blockData) {
                $importedBlockIds[] = $this->importBlock($blockData, $contextId);
            }

            Repo::deiaQuestionBlock()->dao->resequence($contextId);

            return $importedBlockIds;
        });
    }

    private function validatePayload(array $data): void
    {
        if (isset($data['schemaVersion'])) {
            throw new InvalidArgumentException('Schema version is not supported.');
        }

        if (($data['plugin'] ?? null) !== 'deiaSurvey') {
            throw new InvalidArgumentException('Invalid plugin identifier.');
        }

        if (!isset($data['blocks']) || !is_array($data['blocks'])) {
            throw new InvalidArgumentException('Missing blocks.');
        }

        foreach ($data['blocks'] as $blockData) {
            if (empty($blockData['title']) || !is_array($blockData['title'])) {
                throw new InvalidArgumentException('Missing block title.');
            }

            $questions = array_values((array) ($blockData['questions'] ?? []));
            foreach ($questions as $position => $questionData) {
                $this->validateQuestion($questionData);
                if (array_key_exists('showWhen', $questionData)) {
                    $this->validateCondition($questionData['showWhen'], $questions, $position);
                }
            }
        }
    }

    private function validateQuestion(array $questionData): void
    {
        if ($this->normalizeQuestionType($questionData['questionType'] ?? null) === null) {
            throw new InvalidArgumentException('Unknown question type.');
        }

        if (!$this->hasTextualData($questionData['questionText'] ?? null)) {
            throw new InvalidArgumentException('Missing question text.');
        }
    }

    private function validateCondition($condition, array $questions, int $position): void
    {
        if (!is_array($condition) || array_keys($condition) !== [0, 1]) {
            throw new InvalidArgumentException('Invalid question display condition.');
        }

        [$sourcePosition, $optionPosition] = $condition;
        if (!is_int($sourcePosition) || $sourcePosition < 0 || $sourcePosition >= $position
            || !is_int($optionPosition) || $optionPosition < 0
        ) {
            throw new InvalidArgumentException('Invalid question display condition.');
        }

        $source = $questions[$sourcePosition];
        $type = $this->normalizeQuestionType($source['questionType']);
        $options = array_values((array) ($source['responseOptions'] ?? []));
        if (!DeiaQuestion::isSingleSelection($type)
            || !$this->hasTextualData($options[$optionPosition]['optionText'] ?? null)
        ) {
            throw new InvalidArgumentException('Invalid question display condition.');
        }
    }

    private function importBlock(array $blockData, int $contextId): int
    {
        $block = Repo::deiaQuestionBlock()->newDataObject([
            'contextId' => $contextId,
            'title' => $blockData['title'],
            'description' => $blockData['description'] ?? [],
            'active' => 0,
            'sequence' => REALLY_BIG_NUMBER,
        ]);

        $blockId = Repo::deiaQuestionBlock()->add($block);

        $importedQuestions = [];
        foreach (array_values((array) ($blockData['questions'] ?? [])) as $position => $questionData) {
            $showWhen = [];
            if (isset($questionData['showWhen'])) {
                [$sourcePosition, $optionPosition] = $questionData['showWhen'];
                $source = $importedQuestions[$sourcePosition];
                $showWhen = [$source['id'], $source['options'][$optionPosition]];
            }
            $importedQuestions[] = $this->importQuestion($questionData, $contextId, $blockId, $position + 1, $showWhen);
        }

        return $blockId;
    }

    private function importQuestion(
        array $questionData,
        int $contextId,
        int $blockId,
        int $sequence,
        array $showWhen
    ): array {
        $questionText = $this->normalizeTextualData($questionData['questionText']);
        $questionDescription = $this->normalizeTextualData($questionData['questionDescription'] ?? []);

        $question = Repo::deiaQuestion()->newDataObject([
            'contextId' => $contextId,
            'questionBlockId' => $blockId,
            'questionType' => $this->normalizeQuestionType($questionData['questionType']),
            'questionText' => $questionText,
            'questionDescription' => $questionDescription,
            'sequence' => $sequence,
            'showWhen' => $showWhen,
            'isTranslated' => true,
            'isDefaultQuestion' => false,
        ]);

        $questionId = Repo::deiaQuestion()->add($question);

        $optionIds = [];
        foreach (array_values((array) ($questionData['responseOptions'] ?? [])) as $position => $optionData) {
            $optionIds[] = $this->importResponseOption($optionData, $questionId, $position + 1);
        }

        return ['id' => $questionId, 'options' => $optionIds];
    }

    private function importResponseOption(array $optionData, int $questionId, int $sequence): int
    {
        $responseOption = Repo::deiaResponseOption()->newDataObject([
            'deiaQuestionId' => $questionId,
            'optionText' => $this->normalizeTextualData($optionData['optionText'] ?? []),
            'hasInputField' => !empty($optionData['hasInputField']),
            'sequence' => $sequence,
            'isTranslated' => true,
        ]);

        return Repo::deiaResponseOption()->add($responseOption);
    }

    private function normalizeQuestionType($questionType): ?int
    {
        $questionTypeConstants = DeiaQuestion::getQuestionTypeConstants();

        if (is_string($questionType) && isset($questionTypeConstants[$questionType])) {
            return $questionTypeConstants[$questionType];
        }

        return null;
    }

    private function hasTextualData($value): bool
    {
        if (!is_array($value)) {
            return is_string($value) && !empty($value) && trim($value) !== '';
        }

        $hasText = false;
        foreach ($value as $text) {
            if (!is_string($text)) {
                return false;
            }
            $hasText = $hasText || (!empty($text) && trim($text) !== '');
        }
        return $hasText;
    }

    private function normalizeTextualData($value)
    {
        if (is_array($value)) {
            return $value;
        }

        return [\AppLocale::getLocale() => (string) $value];
    }
}
