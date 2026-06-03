<?php

namespace APP\plugins\generic\deiaSurvey\report\classes\factories;

use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\report\classes\ContextReport;

class ContextReportFactory
{
    public function createSiteReport(int $contextId): ContextReport
    {
        $report = new ContextReport();

        $questionBlocks = Repo::deiaQuestionBlock()
            ->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByActive(true)
            ->getMany();

        foreach ($questionBlocks as $questionBlock) {
            $questions = Repo::deiaQuestion()
                ->getCollector()
                ->filterByContextIds([$contextId])
                ->filterByQuestionBlockIds([$questionBlock->getId()])
                ->getMany();

            if ($questions->isNotEmpty()) {
                $report->addQuestionBlock($questionBlock);
                foreach ($questions as $question) {
                    $report->addQuestion($question);
                }
            }
        }

        return $report;
    }
}
