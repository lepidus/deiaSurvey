<?php

namespace APP\plugins\generic\deiaSurvey\report\classes\factories;

use APP\plugins\generic\deiaSurvey\report\classes\ContextStatistics;
use APP\plugins\generic\deiaSurvey\report\classes\factories\QuestionStatisticsFactory;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataDAO;

class ContextStatisticsFactory
{
    private int $contextId;

    public function __construct(int $contextId)
    {
        $this->contextId = $contextId;
    }

    public function createContextStatistics(): ContextStatistics
    {
        $contextStats = new ContextStatistics();
        $questions = Repo::demographicQuestion()
            ->getCollector()
            ->filterByContextIds([$this->contextId])
            ->getMany();

        foreach ($questions as $question) {
            $questionStatsFactory = new QuestionStatisticsFactory($question->getId());
            $questionStats = $questionStatsFactory->createQuestionStatistics();

            $contextStats->addQuestionStatistics($question->getId(), $questionStats);
        }

        $demographicDataDao = new DemographicDataDAO();
        $contextConsentStats = $demographicDataDao->getConsentStatsByContext($this->contextId);
        $contextStats->setUsersConsentCount($contextConsentStats['consentCount']);
        $contextStats->setUsersNoConsentCount($contextConsentStats['noConsentCount']);

        return $contextStats;
    }
}
