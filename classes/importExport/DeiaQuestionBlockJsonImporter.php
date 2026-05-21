<?php

namespace APP\plugins\generic\deiaSurvey\classes\importExport;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
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

        $importedBlockIds = [];
        foreach ($this->sortBySequence($data['blocks']) as $blockData) {
            $importedBlockIds[] = $this->importBlock($blockData, $contextId);
        }

        Repo::deiaQuestionBlock()->dao->resequence($contextId);

        return $importedBlockIds;
    }

    private function validatePayload(array $data): void
    {
        if (($data['schemaVersion'] ?? null) !== '1.0') {
            throw new InvalidArgumentException('Unsupported schema version.');
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
        if (!in_array((int) ($questionData['questionType'] ?? 0), DeiaQuestion::getQuestionTypeConstants(), true)) {
            throw new InvalidArgumentException('Unknown question type.');
        }

        if (empty($questionData['questionText']) || !is_array($questionData['questionText'])) {
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

        foreach ($this->sortBySequence((array) ($blockData['questions'] ?? [])) as $questionData) {
            $this->importQuestion($questionData, $contextId, $blockId);
        }

        return $blockId;
    }

    private function importQuestion(array $questionData, int $contextId, int $blockId): void
    {
        $question = Repo::deiaQuestion()->newDataObject([
            'contextId' => $contextId,
            'questionBlockId' => $blockId,
            'questionType' => (int) $questionData['questionType'],
            'questionText' => $questionData['questionText'],
            'questionDescription' => $questionData['questionDescription'] ?? [],
            'sequence' => REALLY_BIG_NUMBER,
            'isTranslated' => true,
            'isDefaultQuestion' => false,
        ]);

        $questionId = Repo::deiaQuestion()->add($question);

        foreach ($this->sortBySequence((array) ($questionData['responseOptions'] ?? [])) as $optionData) {
            $this->importResponseOption($optionData, $questionId);
        }

        Repo::deiaQuestion()->dao->resequence($blockId);
    }

    private function importResponseOption(array $optionData, int $questionId): void
    {
        $responseOption = Repo::deiaResponseOption()->newDataObject([
            'deiaQuestionId' => $questionId,
            'optionText' => $optionData['optionText'] ?? [],
            'hasInputField' => !empty($optionData['hasInputField']),
            'sequence' => (int) ($optionData['sequence'] ?? REALLY_BIG_NUMBER),
            'isTranslated' => true,
        ]);

        Repo::deiaResponseOption()->add($responseOption);
    }

    private function sortBySequence(array $items): array
    {
        usort(
            $items,
            fn (array $first, array $second): int => ($first['sequence'] ?? REALLY_BIG_NUMBER)
                <=> ($second['sequence'] ?? REALLY_BIG_NUMBER)
        );

        return $items;
    }
}
