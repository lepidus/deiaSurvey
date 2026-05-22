<?php

namespace APP\plugins\generic\deiaSurvey\report\classes;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\report\classes\ContextStatistics;

import('lib.pkp.classes.context.Context');

class SiteStatisticsReport
{
    private $locale;
    private $contextsStatistics;
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
        $applicationName = \Application::get()->getName();
        $reportColumns = $this->getReportColumns();

        $firstRow = [__("plugins.generic.deiaSurvey.report.contextName.$applicationName", [], $this->locale)];
        $secondRow = [''];
        $thirdRow = [''];

        foreach ($reportColumns as $index => $column) {
            $previousColumn = $reportColumns[$index - 1] ?? null;
            $firstRow[] = $this->isSameBlock($previousColumn, $column) ? '' : $column['blockTitle'];
            $secondRow[] = $this->isSameQuestion($previousColumn, $column) ? '' : $column['questionText'];
            $thirdRow[] = $column['responseOptionText']
                ?? __('plugins.generic.deiaSurvey.report.responsesCount', [], $this->locale);
        }

        $firstRow[] = __('plugins.generic.deiaSurvey.report.usersWhoConsented', [], $this->locale);
        $secondRow[] = '';
        $thirdRow[] = '';
        $firstRow[] = __('plugins.generic.deiaSurvey.report.usersWhoDidNotConsent', [], $this->locale);
        $secondRow[] = '';
        $thirdRow[] = '';

        return [$firstRow, $secondRow, $thirdRow];
    }

    public function writeReport(string $filePath)
    {
        $csvFile = fopen($filePath, 'wt');
        fprintf($csvFile, $this->UTF8_BOM);

        $reportHeader = $this->getReportHeader();
        fputcsv($csvFile, $reportHeader[0]);
        fputcsv($csvFile, $reportHeader[1]);
        fputcsv($csvFile, $reportHeader[2]);
        $reportColumns = $this->getReportColumns();

        foreach ($this->contextsStatistics as $contextData) {
            $context = $contextData['context'];
            $contextStats = $contextData['statistics'];

            $row = [$context->getLocalizedName($this->locale)];
            $row = array_merge(
                $row,
                $contextStats->printStatistics($this->getContextReportColumns($reportColumns, $contextData['printingGuide']))
            );
            fputcsv($csvFile, $row);
        }

        fclose($csvFile);
    }

    private function getReportColumns(): array
    {
        $columns = [];

        foreach ($this->contextsStatistics as $contextData) {
            foreach ($this->flattenPrintingGuide($contextData['printingGuide']) as $column) {
                if (!isset($columns[$column['key']])) {
                    $columns[$column['key']] = $column;
                }
            }
        }

        return array_values($columns);
    }

    private function getContextReportColumns(array $reportColumns, array $contextPrintingGuide): array
    {
        $contextColumns = [];

        foreach ($this->flattenPrintingGuide($contextPrintingGuide) as $column) {
            $contextColumns[$column['key']] = $column;
        }

        return array_map(function ($reportColumn) use ($contextColumns) {
            return $contextColumns[$reportColumn['key']] ?? $reportColumn;
        }, $reportColumns);
    }

    private function flattenPrintingGuide(array $printingGuide): array
    {
        $columns = [];

        foreach ($printingGuide as $questionGuide) {
            if ($this->questionTypeHasResponseOptions($questionGuide['questionType'])) {
                foreach ($questionGuide['responseOptions'] as $responseOption) {
                    $columns[] = $this->createReportColumn($questionGuide, $responseOption);
                }
                continue;
            }

            $columns[] = $this->createReportColumn($questionGuide);
        }

        return $columns;
    }

    private function createReportColumn(array $questionGuide, array $responseOption = null): array
    {
        $column = [
            'blockTitle' => $questionGuide['blockTitle'],
            'questionId' => $questionGuide['questionId'],
            'questionText' => $questionGuide['questionText'],
            'questionType' => $questionGuide['questionType'],
            'responseOptions' => [],
        ];

        if (!is_null($responseOption)) {
            $column['responseOptions'][] = $responseOption;
            $column['responseOptionText'] = $responseOption['text'];
        }

        $column['key'] = implode("\0", [
            $column['blockTitle'],
            $column['questionText'],
            $column['responseOptionText'] ?? 'responsesCount',
        ]);

        return $column;
    }

    private function isSameBlock(?array $previousColumn, array $column): bool
    {
        return !is_null($previousColumn)
            && $previousColumn['blockTitle'] === $column['blockTitle'];
    }

    private function isSameQuestion(?array $previousColumn, array $column): bool
    {
        return !is_null($previousColumn)
            && $this->isSameBlock($previousColumn, $column)
            && $previousColumn['questionText'] === $column['questionText'];
    }

    private function questionTypeHasResponseOptions(int $questionType): bool
    {
        return in_array($questionType, [
            DeiaQuestion::TYPE_CHECKBOXES,
            DeiaQuestion::TYPE_RADIO_BUTTONS,
            DeiaQuestion::TYPE_DROP_DOWN_BOX,
        ]);
    }
}
