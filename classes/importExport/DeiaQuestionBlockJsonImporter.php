<?php

namespace APP\plugins\generic\deiaSurvey\classes\importExport;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use InvalidArgumentException;
use PKP\facades\Locale;

class DeiaQuestionBlockJsonImporter
{
    public function import(string $json, int $contextId): array
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON file.');
        }

        $this->validatePayload($data);

        $importedBlockIds = [];
        foreach ($data['blocks'] as $blockData) {
            $importedBlockIds[] = $this->importBlock($blockData, $contextId);
        }

        Repo::deiaQuestionBlock()->dao->resequence($contextId);

        return $importedBlockIds;
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

            foreach ((array) ($blockData['questions'] ?? []) as $questionData) {
                $this->validateQuestion($questionData);
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

        foreach ((array) ($blockData['questions'] ?? []) as $questionData) {
            $this->importQuestion($questionData, $contextId, $blockId);
        }

        return $blockId;
    }

    private function importQuestion(array $questionData, int $contextId, int $blockId): void
    {
        $questionText = $this->normalizeTextualData($questionData['questionText']);
        $questionDescription = $this->normalizeTextualData($questionData['questionDescription'] ?? []);

        $question = Repo::deiaQuestion()->newDataObject([
            'contextId' => $contextId,
            'questionBlockId' => $blockId,
            'questionType' => $this->normalizeQuestionType($questionData['questionType']),
            'questionText' => $questionText,
            'questionDescription' => $questionDescription,
            'sequence' => REALLY_BIG_NUMBER,
            'isTranslated' => true,
            'isDefaultQuestion' => false,
        ]);

        $questionId = Repo::deiaQuestion()->add($question);

        foreach (array_values((array) ($questionData['responseOptions'] ?? [])) as $sequence => $optionData) {
            $this->importResponseOption($optionData, $questionId, $sequence + 1);
        }

        Repo::deiaQuestion()->dao->resequence($blockId);
    }

    private function importResponseOption(array $optionData, int $questionId, int $sequence): void
    {
        $optionText = $this->normalizeTextualData($optionData['optionText'] ?? []);

        $responseOption = Repo::deiaResponseOption()->newDataObject([
            'deiaQuestionId' => $questionId,
            'optionText' => $optionText,
            'hasInputField' => !empty($optionData['hasInputField']),
            'sequence' => $sequence,
            'isTranslated' => true,
        ]);

        Repo::deiaResponseOption()->add($responseOption);
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
        if (is_array($value)) {
            return !empty(array_filter($value, fn ($localizedValue): bool => $localizedValue !== ''));
        }

        return is_string($value) && $value !== '';
    }

    private function normalizeTextualData($value)
    {
        if (is_array($value)) {
            return $value;
        }

        return [Locale::getLocale() => (string) $value];
    }
}
