<?php

namespace APP\plugins\generic\deiaSurvey\report\classes\factories;

use APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\report\classes\ContextStatistics;

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
        $questions = Repo::deiaQuestion()
            ->getCollector()
            ->filterByContextIds([$this->contextId])
            ->getMany();

        foreach ($questions as $question) {
            $questionStatsFactory = new QuestionStatisticsFactory($question->getId(), $question->getQuestionType());
            $questionStats = $questionStatsFactory->createQuestionStatistics();

            $contextStats->addQuestionStatistics($question->getId(), $questionStats);
        }

        $deiaDataDao = new DeiaDataDAO();
        $contextConsentStats = $deiaDataDao->getConsentStatsByContext($this->contextId);
        $contextStats->setUsersConsentCount($contextConsentStats['consentCount']);
        $contextStats->setUsersNoConsentCount($contextConsentStats['noConsentCount']);

        return $contextStats;
    }

    public function createContextStatsPrintingGuide(): array
    {
        $printingGuide = [];
        $questionBlocks = Repo::deiaQuestionBlock()
            ->getCollector()
            ->filterByContextIds([$this->contextId])
            ->filterByActive(true)
            ->getMany();

        foreach ($questionBlocks as $questionBlock) {
            $questions = Repo::deiaQuestion()
                ->getCollector()
                ->filterByContextIds([$this->contextId])
                ->filterByQuestionBlockIds([$questionBlock->getId()])
                ->getMany();

            foreach ($questions as $question) {
                $printingGuide[] = [
                    'blockTitle' => $questionBlock->getLocalizedTitle(),
                    'questionId' => $question->getId(),
                    'questionText' => $question->getLocalizedQuestionText(),
                    'questionType' => $question->getQuestionType(),
                    'responseOptions' => $this->createQuestionStatsPrintingGuide($question->getId()),
                ];
            }
        }

        return $printingGuide;
    }

    private function createQuestionStatsPrintingGuide(int $questionId): array
    {
        $responseOptionsGuide = [];
        $responseOptions = Repo::deiaResponseOption()
            ->getCollector()
            ->filterByQuestionIds([$questionId])
            ->getMany();

        foreach ($responseOptions as $option) {
            $responseOptionsGuide[] = [
                'id' => $option->getId(),
                'text' => $option->getLocalizedOptionText(),
            ];
        }

        return $responseOptionsGuide;
    }
}
