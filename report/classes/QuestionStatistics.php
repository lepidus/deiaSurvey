<?php

namespace APP\plugins\generic\deiaSurvey\report\classes;

class QuestionStatistics
{
    private array $responseOptionsCounts;
    private int $filledResponseCount;

    public function __construct()
    {
        $this->responseOptionsCounts = [];
        $this->filledResponseCount = 0;
    }

    public function incrementOptionCount(int $responseOptionId): void
    {
        if (!isset($this->responseOptionsCounts[$responseOptionId])) {
            $this->responseOptionsCounts[$responseOptionId] = 0;
        }

        $this->responseOptionsCounts[$responseOptionId]++;
    }

    public function getOptionCount(int $responseOptionId): int
    {
        return $this->responseOptionsCounts[$responseOptionId] ?? 0;
    }

    public function getAllCounts(): array
    {
        return $this->responseOptionsCounts;
    }

    public function incrementFilledResponseCount(): void
    {
        $this->filledResponseCount++;
    }

    public function getFilledResponseCount(): int
    {
        return $this->filledResponseCount;
    }
}
