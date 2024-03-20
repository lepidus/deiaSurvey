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

    public function testGetQuestionDescription(): void
    {
        $expectedQuestionDescription = "Ethnicity refers to a group of people who share
            common cultural, historical, linguistic, or ancestral characteristics.
            These characteristics may include geographic origin, language, religion, customs,
            traditions, and shared history. Ethnicity is often associated with cultural identity
            and can play a significant role in shaping the individual and collective identity
            of a group of people.";

        $this->demographicQuestion->setQuestionDescription($expectedQuestionDescription, 'en');
        $questionDescription = $this->demographicQuestion->getLocalizedQuestionDescription()['en'];
        $this->assertEquals($questionDescription, $expectedQuestionDescription);
    }
}
