<?php

namespace APP\plugins\generic\demographicData\tests\demographicQuestion;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;

class DemographicQuestionTest extends PKPTestCase
{
    private DemographicQuestion $demographicQuestion;

    protected function setUp(): void
    {
        $this->demographicQuestion = new DemographicQuestion();
        parent::setUp();
    }

    public function testGetContextId(): void
    {
        $expectedContextId = 1;
        $this->demographicQuestion->setContextId($expectedContextId);
        $this->assertEquals($this->demographicQuestion->getContextId(), $expectedContextId);
    }

    public function testGetQuestionText(): void
    {
        $expectedQuestionText = "What is your ethnicity?";
        $this->demographicQuestion->setQuestionText($expectedQuestionText, 'en');
        $questionText = $this->demographicQuestion->getLocalizedQuestionText()['en'];
        $this->assertEquals($questionText, $expectedQuestionText);
    }
}
