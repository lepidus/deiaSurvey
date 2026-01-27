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
        $secondQuestionStats->incrementOptionCount(1);
        $secondQuestionStats->incrementOptionCount(2);
        $secondQuestionStats->incrementOptionCount(2);

        $thirdQuestionStats = new QuestionStatistics();
        $thirdQuestionStats->incrementOptionCount(1);
        $thirdQuestionStats->incrementOptionCount(2);
        $thirdQuestionStats->incrementOptionCount(2);

        $contextStats = new ContextStatistics();
        $contextStats->addQuestionStatistics(1, $questionStats);
        $contextStats->addQuestionStatistics(2, $secondQuestionStats);
        $contextStats->addQuestionStatistics(3, $thirdQuestionStats);
        $contextStats->setUsersConsentCount(5);
        $contextStats->setUsersNoConsentCount(3);

        return $contextStats;
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
        $this->siteReport->addContextStatistics($this->context, $this->contextStats);
        $this->assertEquals(
            [['context' => $this->context, 'statistics' => $this->contextStats]],
            $this->siteReport->getContextsStatistics()
        );
    }

    public function testGetReportHeader()
    {
        $expectedHeader = $this->generateExpectedHeader();
        $this->assertEquals($expectedHeader, $this->siteReport->getReportHeader());
    }
}
