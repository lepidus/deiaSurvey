<?php

namespace APP\plugins\generic\deiaSurvey\report;

use APP\plugins\generic\deiaSurvey\report\classes\factories\{
    ContextReportFactory,
    SiteStatisticsReportFactory
};

import('lib.pkp.classes.form.Form');
import('lib.pkp.classes.security.Validation');

class DeiaSurveyReportForm extends \Form
{
    private const FORM_TEMPLATE = 'report/deiaSurveyReportForm.tpl';
    private const STYLE_SHEET = 'styles/deiaSurveyReportForm.css';
    private const REPORT_TYPE_SITE = 'site';
    private const REPORT_TYPE_CONTEXT = 'context';

    private $plugin;
    private $contextId;

    public function __construct($plugin)
    {
        $request = \Application::get()->getRequest();

        $this->plugin = $plugin;
        $this->contextId = $request->getContext()->getId();

        parent::__construct($plugin->getTemplateResource(self::FORM_TEMPLATE));
        $this->addCheck(new \FormValidatorPost($this));
        $this->addCheck(new \FormValidatorCSRF($this));
    }

    public function initData()
    {
        $userIsSiteAdmin = \Validation::isSiteAdmin();
        $this->setData('userIsSiteAdmin', $userIsSiteAdmin);
    }

    public function validateReportGeneration($reportParams)
    {
        $reportType = $reportParams['reportType'] ?? null;

        if ($reportType === 'site') {
            return \Validation::isSiteAdmin();
        }

        return $reportType === 'context';
    }

    private function emitHttpHeaders($request, $reportType)
    {
        $context = $request->getContext();
        $acronym = \PKPString::regexp_replace('/[^A-Za-z0-9 ]/', '', $context->getLocalizedAcronym());
        $fileName = $reportType == self::REPORT_TYPE_SITE
            ? 'site-deia-report-' . date('YmdHis') . '.csv'
            : $acronym . '-deia-report-' . date('YmdHis') . '.csv';

        header('content-type: text/comma-separated-values');
        header("content-disposition: attachment; filename=$fileName");
    }

    public function generateReport($request, $reportParams)
    {
        $reportType = $reportParams['reportType'];
        $this->emitHttpHeaders($request, $reportType);

        if ($reportType == self::REPORT_TYPE_SITE) {
            $siteStatsReportFactory = new SiteStatisticsReportFactory();
            $report = $siteStatsReportFactory->createSiteReport();
            $report->writeReport('php://output');
        } elseif ($reportType == self::REPORT_TYPE_CONTEXT) {
            $contextId = $request->getContext()->getId();
            $contextReportFactory = new ContextReportFactory();
            $report = $contextReportFactory->createContextReport($contextId);
            $report->writeReport('php://output');
        }
    }

    public function display($request = null, $template = null)
    {
        $templateManager = \TemplateManager::getManager($request);
        $application = \Application::get()->getName();
        $url = $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/' . self::STYLE_SHEET;
        $templateManager->addStyleSheet('deiaSurveyReportStyleSheet', $url, [
            'priority' => \TemplateManager::STYLE_SEQUENCE_CORE,
            'contexts' => 'backend',
        ]);
        $templateManager->assign([
            'application' => $application,
            'userIsSiteAdmin' => $this->getData('userIsSiteAdmin')
        ]);
        $templateManager->display($this->plugin->getTemplateResource(self::FORM_TEMPLATE));
    }
}
