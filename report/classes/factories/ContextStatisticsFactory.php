<?php

namespace APP\plugins\generic\deiaSurvey\report\classes\factories;

use APP\plugins\generic\deiaSurvey\report\classes\ContextStatistics;
use APP\plugins\generic\deiaSurvey\report\classes\factories\QuestionStatisticsFactory;
use APP\plugins\generic\deiaSurvey\classes\DefaultQuestionsCreator;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO;

class ContextStatisticsFactory
{
    private $contextId;

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
            $questionStatsFactory = new QuestionStatisticsFactory($question->getId());
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
        $defaultQuestionsData = DefaultQuestionsCreator::getDefaultQuestionsData($this->contextId);
        $printingGuide = [];
        $contextQuestions = Repo::deiaQuestion()
            ->getCollector()
            ->filterByContextIds([$this->contextId])
            ->getMany();

        foreach ($defaultQuestionsData as $questionData) {
            foreach ($contextQuestions as $question) {
                if ($question->getData('questionText') === $questionData['questionText']) {
                    $responseOptionsGuide = $this->createQuestionStatsPrintingGuide(
                        $question->getId(),
                        $questionData['responseOptions']
                    );

                    $printingGuide[$question->getId()] = $responseOptionsGuide;
                }
            }
        }

        return $printingGuide;
    }

    private function createQuestionStatsPrintingGuide(int $questionId, array $responseOptionsData): array
    {
        $responseOptionsGuide = [];
        $responseOptions = Repo::deiaResponseOption()
            ->getCollector()
            ->filterByQuestionIds([$questionId])
            ->getMany();

        foreach ($responseOptionsData as $responseOptionData) {
            foreach ($responseOptions as $option) {
                if ($option->getData('optionText') === $responseOptionData['optionText']) {
                    $responseOptionsGuide[] = $option->getId();
                }
            }
        }

        return $responseOptionsGuide;
    }
}
