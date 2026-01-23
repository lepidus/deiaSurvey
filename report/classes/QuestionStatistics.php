<?php

namespace APP\plugins\generic\deiaSurvey\report\classes;

class QuestionStatistics
{
    private array $responseOptionsCounts;


    public function __construct()
    {
        $this->responseOptionsCounts = [];
    }

    public function incrementOptionCount(int $responseOptionId): void
    {
        if (!isset($this->responseOptionsCounts[$responseOptionId])) {
            $this->responseOptionsCounts[$responseOptionId] = 0;
        }

        $this->responseOptionsCounts[$responseOptionId]++;
    }

    public function getOptionCount(int $responseOptionId): ?int
    {
        return $this->responseOptionsCounts[$responseOptionId] ?? null;
    }

    public function getAllCounts(): array
    {
        return $this->responseOptionsCounts;
    }
}
