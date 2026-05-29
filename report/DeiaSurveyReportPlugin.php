<?php

namespace APP\plugins\generic\deiaSurvey\report;

use PKP\plugins\ReportPlugin;
use PKP\config\Config;
use APP\plugins\generic\deiaSurvey\report\DeiaSurveyReportForm;

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
        $form = new DeiaSurveyReportForm($this);
        $form->initData();
        if ($request->isPost($request)) {
            $reportParams = $request->getUserVars();
            if ($form->validateReportGeneration($reportParams)) {
                $form->generateReport($request);
            }
        } else {
            $form->display($request);
        }
    }
}
