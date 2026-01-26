<?php

namespace APP\plugins\generic\deiaSurvey\tests\report;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\deiaSurvey\report\classes\ContextStatistics;
use APP\plugins\generic\deiaSurvey\report\classes\QuestionStatistics;

class ContextStatisticsTest extends PKPTestCase
{
    private $contextStatistics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contextStatistics = new ContextStatistics();
    }

    public function testHasConsentCount()
    {
        $this->contextStatistics->setUsersConsentCount(123);
        $this->assertEquals(123, $this->contextStatistics->getUsersConsentCount());
    }

    public function testHasNoConsentCount()
    {
        $this->contextStatistics->setUsersNoConsentCount(987);
        $this->assertEquals(987, $this->contextStatistics->getUsersNoConsentCount());
    }

    public function testHasQuestionsStatistics()
    {
        $firstQuestionId = 2112;
        $firstQuestionStatistics = new QuestionStatistics();
        $firstQuestionStatistics->incrementOptionCount(12);

        $this->contextStatistics->addQuestionStatistics($firstQuestionId, $firstQuestionStatistics);
        $this->assertEquals($firstQuestionStatistics, $this->contextStatistics->getQuestionStatistics($firstQuestionId));
    }

    public function testGetsNonExistentQuestionStatistics()
    {
        $questionId = 2113;
        $this->assertNull($this->contextStatistics->getQuestionStatistics($questionId));
    }
}
