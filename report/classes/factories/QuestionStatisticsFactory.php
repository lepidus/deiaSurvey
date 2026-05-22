<?php

namespace APP\plugins\generic\deiaSurvey\report\classes\factories;

use APP\plugins\generic\deiaSurvey\report\classes\QuestionStatistics;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;

class QuestionStatisticsFactory
{
    private $deiaQuestionId;

    public function __construct(int $deiaQuestionId)
    {
        $this->deiaQuestionId = $deiaQuestionId;
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
            if (!is_array($responseValue)) {
                continue;
            }

            foreach ($responseValue as $selectedOptionId) {
                $questionStats->incrementOptionCount((int) $selectedOptionId);
            }
        }

        return $questionStats;
    }
}
