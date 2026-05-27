<?php

namespace APP\plugins\generic\deiaSurvey\report\classes\factories;

use APP\core\Application;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\report\classes\SiteStatisticsReport;
use PKP\facades\Locale;

class SiteStatisticsReportFactory
{
    public function createSiteReport(): SiteStatisticsReport
    {
        $locale = Locale::getLocale();
        $report = new SiteStatisticsReport($locale);

        $contextDao = Application::get()->getContextDAO();
        $contexts = $contextDao->getAll(true);

        while ($context = $contexts->next()) {
            $deiaQuestionsCount = Repo::deiaQuestion()
                ->getCollector()
                ->filterByContextIds([$context->getId()])
                ->getCount();

            if ($deiaQuestionsCount > 0) {
                $contextStatsFactory = new ContextStatisticsFactory($context->getId());
                $contextPrintingGuide = $contextStatsFactory->createContextStatsPrintingGuide();
                $contextStats = $contextStatsFactory->createContextStatistics();

                $report->addContextStatistics($context, $contextStats, $contextPrintingGuide);
            }
        }

        return $report;
    }
}
