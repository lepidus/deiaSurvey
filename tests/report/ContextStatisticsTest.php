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

    public function testUsersConsentCount()
    {
        $this->assertEquals(0, $this->contextStatistics->getUsersConsentCount());

        $this->contextStatistics->incrementUsersConsentCount();
        $this->assertEquals(1, $this->contextStatistics->getUsersConsentCount());

        $this->contextStatistics->incrementUsersConsentCount();
        $this->assertEquals(2, $this->contextStatistics->getUsersConsentCount());
    }

    public function testUsersNoConsentCount()
    {
        $this->assertEquals(0, $this->contextStatistics->getUsersNoConsentCount());

        $this->contextStatistics->incrementUsersNoConsentCount();
        $this->assertEquals(1, $this->contextStatistics->getUsersNoConsentCount());

        $this->contextStatistics->incrementUsersNoConsentCount();
        $this->assertEquals(2, $this->contextStatistics->getUsersNoConsentCount());
    }

    public function testHasQuestionsStatistics()
    {
        $firstQuestionId = 2112;
        $firstQuestionStatistics = new QuestionStatistics();
        $firstQuestionStatistics->incrementOptionCount(12);

        $this->contextStatistics->addQuestionStatistics($firstQuestionId, $firstQuestionStatistics);
        $this->assertEquals($firstQuestionStatistics, $this->contextStatistics->getQuestionStatistics($firstQuestionId));
    }

    public function testGestNonExistentQuestionStatistics()
    {
        $questionId = 2113;
        $this->assertNull($this->contextStatistics->getQuestionStatistics($questionId));
    }
}
