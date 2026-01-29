<?php

namespace APP\plugins\generic\deiaSurvey\report\classes;

use APP\plugins\generic\deiaSurvey\classes\DefaultQuestionsCreator;
use APP\plugins\generic\deiaSurvey\report\classes\ContextStatistics;

import('lib.pkp.classes.context.Context');

class SiteStatisticsReport
{
    private string $locale;
    private array $contextsStatistics;
    private $UTF8_BOM;

    public function __construct(string $locale)
    {
        $this->locale = $locale;
        $this->contextsStatistics = [];
        $this->UTF8_BOM = chr(0xEF) . chr(0xBB) . chr(0xBF);
    }

    public function addContextStatistics(\Context $context, ContextStatistics $statistics, array $printingGuide): void
    {
        $this->contextsStatistics[] = [
            'context' => $context,
            'statistics' => $statistics,
            'printingGuide' => $printingGuide
        ];
    }

    public function getContextsStatistics(): array
    {
        return $this->contextsStatistics;
    }

    public function getReportHeader(): array
    {
        $defaultQuestionsData = DefaultQuestionsCreator::getDefaultQuestionsData(1);
        $applicationName = \Application::get()->getName();

        $firstRow = [__("plugins.generic.deiaSurvey.report.contextName.$applicationName", [], $this->locale)];
        $secondRow = [''];

        foreach ($defaultQuestionsData as $questionData) {
            $firstRow[] = __($questionData['questionText'], [], $this->locale);

            foreach ($questionData['responseOptions'] as $index => $optionData) {
                if ($index > 0) {
                    $firstRow[] = '';
                }
                $secondRow[] = __($optionData['optionText'], [], $this->locale);
            }
        }

        $firstRow[] = __('plugins.generic.deiaSurvey.report.usersWhoConsented', [], $this->locale);
        $secondRow[] = '';
        $firstRow[] = __('plugins.generic.deiaSurvey.report.usersWhoDidNotConsent', [], $this->locale);
        $secondRow[] = '';

        return [$firstRow, $secondRow];
    }

    public function writeReport(string $filePath)
    {
        $csvFile = fopen($filePath, 'wt');
        fprintf($csvFile, $this->UTF8_BOM);

        $reportHeader = $this->getReportHeader();
        fputcsv($csvFile, $reportHeader[0]);
        fputcsv($csvFile, $reportHeader[1]);

        foreach ($this->contextsStatistics as $contextData) {
            $context = $contextData['context'];
            $contextStats = $contextData['statistics'];

            $row = [$context->getLocalizedName($this->locale)];
            $row = array_merge($row, $contextStats->printStatistics($contextData['printingGuide']));
            fputcsv($csvFile, $row);
        }

        fclose($csvFile);
    }
}
