<?php

namespace APP\plugins\generic\deiaSurvey\tests\report;

use PKP\tests\PKPTestCase;
use APP\journal\Journal;
use APP\plugins\generic\deiaSurvey\classes\DefaultQuestionsCreator;
use APP\plugins\generic\deiaSurvey\report\classes\ContextStatistics;
use APP\plugins\generic\deiaSurvey\report\classes\QuestionStatistics;
use APP\plugins\generic\deiaSurvey\report\classes\SiteStatisticsReport;
use APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin;

class SiteStatisticsReportTest extends PKPTestCase
{
    private $siteReport;
    private $context;
    private $contextStats;
    private $locale = 'en';

    protected function setUp(): void
    {
        parent::setUp();
        $this->siteReport = new SiteStatisticsReport($this->locale);
        $this->context  = $this->createTestJournal();
        $this->contextStats = $this->createTestContextStats();
        $this->initializePluginLocaleData();
    }

    private function initializePluginLocaleData(): void
    {
        $plugin = new DeiaSurveyPlugin();
        $plugin->pluginPath = 'plugins/generic/deiaSurvey';
        $plugin->addLocaleData();
    }

    private function createTestJournal(): Journal
    {
        $journal = new Journal();
        $journal->setData('name', 'Test Journal', $this->locale);

        return $journal;
    }

    private function createTestContextStats(): ContextStatistics
    {
        $questionStats = new QuestionStatistics();
        $questionStats->incrementOptionCount(1);
        $questionStats->incrementOptionCount(2);
        $questionStats->incrementOptionCount(2);

        $secondQuestionStats = new QuestionStatistics();
        $secondQuestionStats->incrementOptionCount(4);
        $secondQuestionStats->incrementOptionCount(5);
        $secondQuestionStats->incrementOptionCount(5);

        $thirdQuestionStats = new QuestionStatistics();
        $thirdQuestionStats->incrementOptionCount(7);
        $thirdQuestionStats->incrementOptionCount(8);
        $thirdQuestionStats->incrementOptionCount(8);

        $contextStats = new ContextStatistics();
        $contextStats->addQuestionStatistics(1, $questionStats);
        $contextStats->addQuestionStatistics(2, $secondQuestionStats);
        $contextStats->addQuestionStatistics(3, $thirdQuestionStats);
        $contextStats->setUsersConsentCount(5);
        $contextStats->setUsersNoConsentCount(3);

        return $contextStats;
    }

    private function createTestPrintingGuide(): array
    {
        return [
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9]
        ];
    }

    private function generateExpectedHeader(): array
    {
        $defaultQuestionsData = DefaultQuestionsCreator::getDefaultQuestionsData(1);

        $firstRow = [__('plugins.generic.deiaSurvey.report.contextName.ojs2', [], $this->locale)];
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

    public function testHasContextsStatistics()
    {
        $emptyPrintingGuide = [];
        $this->siteReport->addContextStatistics($this->context, $this->contextStats, $emptyPrintingGuide);
        $this->assertEquals(
            [['context' => $this->context, 'statistics' => $this->contextStats, 'printingGuide' => $emptyPrintingGuide]],
            $this->siteReport->getContextsStatistics()
        );
    }

    public function testGetReportHeader()
    {
        $expectedHeader = $this->generateExpectedHeader();
        $this->assertEquals($expectedHeader, $this->siteReport->getReportHeader());
    }

    public function testWritesReport()
    {
        $printingGuide = $this->createTestPrintingGuide();
        $csvFilePath = '/tmp/deia_survey_site_report_test.csv';
        $this->siteReport->addContextStatistics($this->context, $this->contextStats, $printingGuide);
        $this->siteReport->writeReport($csvFilePath);

        $this->assertFileExists($csvFilePath);
        $csvRows = array_map('str_getcsv', file($csvFilePath));

        $expectedHeader = $this->generateExpectedHeader();
        $this->assertEquals($expectedHeader[0], $csvRows[0]);
        $this->assertEquals($expectedHeader[1], $csvRows[1]);

        $expectedDataRow = ['Test Journal', '1', '2', '0', '1', '2', '0', '1', '2', '0', '5', '3'];
        $this->assertEquals($expectedDataRow, $csvRows[2]);

        unlink($csvFilePath);
    }
}
