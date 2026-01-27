<?php

namespace APP\plugins\generic\deiaSurvey\tests\report;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\deiaSurvey\report\classes\QuestionStatistics;

class QuestionStatisticsTest extends PKPTestCase
{
    private $questionStatistics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->questionStatistics = new QuestionStatistics();
    }

    public function testResponseOptionsCountStartsEmpty(): void
    {
        $this->assertEquals([], $this->questionStatistics->getAllCounts());
    }

    public function testCanIncrementCountForResponseOptions(): void
    {
        $firstResponseOptionId = 15;
        $secondResponseOptionId = 16;

        $this->questionStatistics->incrementOptionCount($firstResponseOptionId);
        $this->assertEquals(1, $this->questionStatistics->getOptionCount($firstResponseOptionId));

        $this->questionStatistics->incrementOptionCount($firstResponseOptionId);
        $this->assertEquals(2, $this->questionStatistics->getOptionCount($firstResponseOptionId));

        $this->questionStatistics->incrementOptionCount($secondResponseOptionId);
        $this->assertEquals(1, $this->questionStatistics->getOptionCount($secondResponseOptionId));

        $this->assertEquals(
            [$firstResponseOptionId => 2, $secondResponseOptionId => 1],
            $this->questionStatistics->getAllCounts()
        );
    }

    public function testGetNonexistentResponseOptionCount(): void
    {
        $nonExistentResponseOptionId = 123;
        $this->assertEquals(0, $this->questionStatistics->getOptionCount($nonExistentResponseOptionId));
    }
}
