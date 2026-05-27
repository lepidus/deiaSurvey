<?php

namespace APP\plugins\generic\deiaSurvey\report;

use APP\plugins\generic\deiaSurvey\report\classes\factories\SiteStatisticsReportFactory;
use PKP\config\Config;
use PKP\plugins\ReportPlugin;

class DeiaSurveyReportPlugin extends ReportPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && Config::getVar('general', 'installed')) {
            $this->addLocaleData();
        }
        return $success;
    }

    public function getName()
    {
        return 'deiaSurveyReportPlugin';
    }

    public function getDisplayName()
    {
        return __('plugins.generic.deiaSurvey.report.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.deiaSurvey.report.description');
    }

    public function display($args, $request)
    {
        header('content-type: text/comma-separated-values');
        header('content-disposition: attachment; filename=site-deia-report-' . date('Ymd') . '.csv');

        $siteStatsReportFactory = new SiteStatisticsReportFactory();
        $report = $siteStatsReportFactory->createSiteReport();
        $report->writeReport('php://output');
    }
}
