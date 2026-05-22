<?php

namespace APP\plugins\generic\deiaSurvey\report\classes;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;

class ContextStatistics
{
    private int $usersConsentCount;
    private int $usersNotConsentCount;
    private array $questionsStatistics;

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

        foreach ($contextPrintGuide as $questionGuide) {
            $questionId = $questionGuide['questionId'] ?? null;
            if (is_null($questionId)) {
                $resultStats[] = 0;
                continue;
            }

            $questionStats = $this->getQuestionStatistics($questionId);

            if (is_null($questionStats)) {
                $resultStats[] = 0;
                continue;
            }

            if ($this->questionTypeHasResponseOptions($questionGuide['questionType'])) {
                foreach ($questionGuide['responseOptions'] as $responseOption) {
                    $resultStats[] = $questionStats->getOptionCount($responseOption['id'] ?? null);
                }
                continue;
            }

            $resultStats[] = $questionStats->getFilledResponseCount();
        }

        $resultStats[] = $this->getUsersConsentCount();
        $resultStats[] = $this->getUsersNoConsentCount();

        return $resultStats;
    }

    private function questionTypeHasResponseOptions(int $questionType): bool
    {
        return in_array($questionType, [
            DeiaQuestion::TYPE_CHECKBOXES,
            DeiaQuestion::TYPE_RADIO_BUTTONS,
            DeiaQuestion::TYPE_DROP_DOWN_BOX,
        ]);
    }
}
