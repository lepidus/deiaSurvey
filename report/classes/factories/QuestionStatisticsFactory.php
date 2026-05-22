<?php

namespace APP\plugins\generic\deiaSurvey\report\classes\factories;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\report\classes\QuestionStatistics;

class QuestionStatisticsFactory
{
    private int $deiaQuestionId;
    private ?int $questionType;

    public function __construct(int $deiaQuestionId, ?int $questionType = null)
    {
        $this->deiaQuestionId = $deiaQuestionId;
        $this->questionType = $questionType;
    }

    public function createQuestionStatistics(): QuestionStatistics
    {
        $questionStats = new QuestionStatistics();
        $responses = Repo::deiaResponse()
            ->getCollector()
            ->filterByQuestionIds([$this->deiaQuestionId])
            ->getMany();

        foreach ($responses as $response) {
            $responseValue = $response->getValue();

            if ($this->questionTypeHasResponseOptions()) {
                if (!is_array($responseValue)) {
                    continue;
                }

                foreach ($responseValue as $selectedOptionId) {
                    $questionStats->incrementOptionCount((int) $selectedOptionId);
                }
                continue;
            }

            if ($this->isFilledResponseValue($responseValue)) {
                $questionStats->incrementFilledResponseCount();
            }
        }

        return $questionStats;
    }

    private function questionTypeHasResponseOptions(): bool
    {
        return in_array($this->questionType, [
            DeiaQuestion::TYPE_CHECKBOXES,
            DeiaQuestion::TYPE_RADIO_BUTTONS,
            DeiaQuestion::TYPE_DROP_DOWN_BOX,
        ]);
    }

    private function isFilledResponseValue($responseValue): bool
    {
        if (is_array($responseValue)) {
            return count(array_filter($responseValue, function ($value) {
                return trim((string) $value) !== '';
            })) > 0;
        }

        return trim((string) $responseValue) !== '';
    }
}
