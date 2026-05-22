<?php

namespace APP\plugins\generic\deiaSurvey\tests\report;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\report\classes\ContextStatistics;
use APP\plugins\generic\deiaSurvey\report\classes\QuestionStatistics;
use APP\plugins\generic\deiaSurvey\report\classes\SiteStatisticsReport;
use APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin;

import('lib.pkp.tests.PKPTestCase');
import('classes.journal.Journal');

class SiteStatisticsReportTest extends \PKPTestCase
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
        // $this->initializePluginLocaleData();
    }

    private function initializePluginLocaleData(): void
    {
        $plugin = new DeiaSurveyPlugin();
        $plugin->pluginPath = 'plugins/generic/deiaSurvey';
        $plugin->addLocaleData();
    }

    private function createTestJournal(): \Journal
    {
        $journal = new \Journal();
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
        $secondQuestionStats->incrementFilledResponseCount();
        $secondQuestionStats->incrementFilledResponseCount();

        $contextStats = new ContextStatistics();
        $contextStats->addQuestionStatistics(1, $questionStats);
        $contextStats->addQuestionStatistics(2, $secondQuestionStats);
        $contextStats->setUsersConsentCount(5);
        $contextStats->setUsersNoConsentCount(3);

        return $contextStats;
    }

    private function createTestPrintingGuide(): array
    {
        return [
            [
                'blockTitle' => 'SciELO Questions',
                'questionId' => 1,
                'questionText' => 'Gender',
                'questionType' => DeiaQuestion::TYPE_RADIO_BUTTONS,
                'responseOptions' => [
                    ['id' => 1, 'text' => 'Woman'],
                    ['id' => 2, 'text' => 'Man'],
                    ['id' => 3, 'text' => 'Non-binary'],
                ],
            ],
            [
                'blockTitle' => 'Custom Questions',
                'questionId' => 2,
                'questionText' => 'Accessibility needs',
                'questionType' => DeiaQuestion::TYPE_TEXTAREA,
                'responseOptions' => [],
            ],
        ];
    }

    private function generateExpectedHeader(): array
    {
        $firstRow = [__('plugins.generic.deiaSurvey.report.contextName.ojs2', [], $this->locale)];
        $secondRow = [''];
        $thirdRow = [''];

        $firstRow[] = 'SciELO Questions';
        $firstRow[] = '';
        $firstRow[] = '';
        $secondRow[] = 'Gender';
        $secondRow[] = '';
        $secondRow[] = '';
        $thirdRow[] = 'Woman';
        $thirdRow[] = 'Man';
        $thirdRow[] = 'Non-binary';

        $firstRow[] = 'Custom Questions';
        $secondRow[] = 'Accessibility needs';
        $thirdRow[] = __('plugins.generic.deiaSurvey.report.responsesCount', [], $this->locale);

        $firstRow[] = __('plugins.generic.deiaSurvey.report.usersWhoConsented', [], $this->locale);
        $secondRow[] = '';
        $thirdRow[] = '';
        $firstRow[] = __('plugins.generic.deiaSurvey.report.usersWhoDidNotConsent', [], $this->locale);
        $secondRow[] = '';
        $thirdRow[] = '';

        return [$firstRow, $secondRow, $thirdRow];
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
        $this->siteReport->addContextStatistics($this->context, $this->contextStats, $this->createTestPrintingGuide());
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
        $csvFile = fopen($csvFilePath, 'r');
        $UTF8_BOM = chr(0xEF) . chr(0xBB) . chr(0xBF);
        fread($csvFile, strlen($UTF8_BOM));

        $expectedHeader = $this->generateExpectedHeader();
        $row = fgetcsv($csvFile);
        $this->assertEquals($expectedHeader[0], $row);
        $row = fgetcsv($csvFile);
        $this->assertEquals($expectedHeader[1], $row);
        $row = fgetcsv($csvFile);
        $this->assertEquals($expectedHeader[2], $row);

        $expectedDataRow = ['Test Journal', '1', '2', '0', '2', '5', '3'];
        $row = fgetcsv($csvFile);
        $this->assertEquals($expectedDataRow, $row);

        fclose($csvFile);
        unlink($csvFilePath);
    }
}
