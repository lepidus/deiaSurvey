<?php

namespace APP\plugins\generic\deiaSurvey\report\classes;

use APP\plugins\generic\deiaSurvey\report\classes\QuestionStatistics;

class ContextStatistics
{
    private $usersConsentCount;
    private $usersNotConsentCount;
    private $questionsStatistics;

    public function __construct()
    {
        $this->usersConsentCount = 0;
        $this->usersNotConsentCount = 0;
        $this->questionsStatistics = [];
    }

    public function setUsersConsentCount(int $count): void
    {
        $this->usersConsentCount = $count;
    }

    public function getUsersConsentCount(): int
    {
        return $this->usersConsentCount;
    }

    public function setUsersNoConsentCount(int $count): void
    {
        $this->usersNotConsentCount = $count;
    }

    public function getUsersNoConsentCount(): int
    {
        return $this->usersNotConsentCount;
    }

    public function addQuestionStatistics(int $questionId, QuestionStatistics $questionStatistics): void
    {
        $this->questionsStatistics[$questionId] = $questionStatistics;
    }

    public function getQuestionStatistics(int $questionId): ?QuestionStatistics
    {
        return $this->questionsStatistics[$questionId] ?? null;
    }

    public function printStatistics(array $contextPrintGuide): array
    {
        $resultStats = [];

        foreach ($contextPrintGuide as $questionId => $responseOptionIds) {
            $questionStats = $this->getQuestionStatistics($questionId);

            if (is_null($questionStats)) {
                continue;
            }

            foreach ($responseOptionIds as $responseOptionId) {
                $count = $questionStats->getOptionCount($responseOptionId);
                if (!is_null($count)) {
                    $resultStats[] = $count;
                }
            }
        }

        $resultStats[] = $this->getUsersConsentCount();
        $resultStats[] = $this->getUsersNoConsentCount();

        return $resultStats;
    }
}
