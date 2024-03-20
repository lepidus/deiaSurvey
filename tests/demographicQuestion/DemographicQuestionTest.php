<?php

namespace APP\plugins\generic\demographicData\tests\demographicQuestion;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;

class DemographicQuestionTest extends PKPTestCase
{
    public function testGetContextId(): void
    {
        $expectedContextId = 1;
        $demographicQuestion = new DemographicQuestion();
        $demographicQuestion->setContextId($expectedContextId);
        $this->assertEquals($demographicQuestion->getContextId(), $expectedContextId);
    }

    public function testGetQuestionText(): void
    {
        $expectedQuestionText = "What is your ethnicity?";
        $demographicQuestion = new DemographicQuestion();
        $demographicQuestion->setQuestionText($expectedQuestionText, 'en');
        $questionText = $demographicQuestion->getLocalizedQuestionText()['en'];
        $this->assertEquals($questionText, $expectedQuestionText);
    }
}
