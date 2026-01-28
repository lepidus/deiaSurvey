<?php

namespace APP\plugins\generic\deiaSurvey\report\classes\factories;

use APP\core\Application;
use APP\plugins\generic\deiaSurvey\report\classes\SiteStatisticsReport;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;

class SiteStatisticsReportFactory
{
    public function createSiteReport(): SiteStatisticsReport
    {
        $locale = Locale::getLocale();
        $report = new SiteStatisticsReport($locale);

        $contextDao = Application::get()->getContextDAO();
        $contexts = $contextDao->getAll(true)->toArray();

        while ($context = $contexts->next()) {
            $demographicQuestionsCount = Repo::demographicQuestion()
                ->getCollector()
                ->filterByContextIds([$context->getId()])
                ->getCount();

            if ($demographicQuestionsCount > 0) {
                $contextStatsFactory = new ContextStatisticsFactory($context->getId());
                $contextPrintingGuide = $contextStatsFactory->createContextStatsPrintingGuide();
                $contextStats = $contextStatsFactory->createContextStatistics();

                $report->addContextStatistics($context, $contextStats, $contextPrintingGuide);
            }
        }

        return $report;
    }
}
