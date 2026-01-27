<?php

namespace APP\plugins\generic\deiaSurvey\tests\report;

use PKP\tests\PKPTestCase;
use APP\journal\Journal;
use APP\plugins\generic\deiaSurvey\report\classes\ContextStatistics;
use APP\plugins\generic\deiaSurvey\report\classes\QuestionStatistics;
use APP\plugins\generic\deiaSurvey\report\classes\SiteStatisticsReport;

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

    public function testHasContextsStatistics()
    {
        $this->siteReport->addContextStatistics($this->context, $this->contextStats);
        $this->assertEquals(
            [['context' => $this->context, 'statistics' => $this->contextStats]],
            $this->siteReport->getContextsStatistics()
        );
    }
}
