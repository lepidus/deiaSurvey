<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicQuestion;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\DemographicQuestion;

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
        $this->assertEquals($expectedContextId, $this->demographicQuestion->getContextId());
    }

    public function testGetQuestionType(): void
    {
        $expectedQuestionType = DemographicQuestion::TYPE_SMALL_TEXT_FIELD;
        $this->demographicQuestion->setQuestionType($expectedQuestionType);
        $this->assertEquals($expectedQuestionType, $this->demographicQuestion->getQuestionType());
    }

    public function testGetQuestionInputType(): void
    {
        $this->demographicQuestion->setQuestionType(DemographicQuestion::TYPE_SMALL_TEXT_FIELD);
        $this->assertEquals('text', $this->demographicQuestion->getQuestionInputType());

        $this->demographicQuestion->setQuestionType(DemographicQuestion::TYPE_TEXT_FIELD);
        $this->assertEquals('text', $this->demographicQuestion->getQuestionInputType());

        $this->demographicQuestion->setQuestionType(DemographicQuestion::TYPE_TEXTAREA);
        $this->assertEquals('textarea', $this->demographicQuestion->getQuestionInputType());

        $this->demographicQuestion->setQuestionType(DemographicQuestion::TYPE_CHECKBOXES);
        $this->assertEquals('checkbox', $this->demographicQuestion->getQuestionInputType());

        $this->demographicQuestion->setQuestionType(DemographicQuestion::TYPE_RADIO_BUTTONS);
        $this->assertEquals('radio', $this->demographicQuestion->getQuestionInputType());

        $this->demographicQuestion->setQuestionType(DemographicQuestion::TYPE_DROP_DOWN_BOX);
        $this->assertEquals('select', $this->demographicQuestion->getQuestionInputType());
    }

    public function setQuestionIsTranslated(): void
    {
        $this->demographicQuestion->setIsTranslated(true);
        $this->assertTrue($this->demographicQuestion->getIsTranslated());

        $this->demographicQuestion->setIsTranslated(false);
        $this->assertFalse($this->demographicQuestion->getIsTranslated());
    }

    public function testGetQuestionTextForTranslated(): void
    {
        $this->demographicQuestion->setIsTranslated(true);

        $expectedQuestionText = "What is your ethnicity?";
        $this->demographicQuestion->setQuestionText($expectedQuestionText, 'en');
        $questionText = $this->demographicQuestion->getLocalizedQuestionText();
        $this->assertEquals($expectedQuestionText, $questionText);
    }

    public function testGetQuestionTextForNotTranslated(): void
    {
        $this->demographicQuestion->setIsTranslated(false);

        $questionTextKey = 'plugin.generic.deiaSurvey.demographicQuestion.exampleQuestion.title';
        $expectedQuestionText = __($questionTextKey);
        $this->demographicQuestion->setQuestionText($questionTextKey);
        $questionText = $this->demographicQuestion->getLocalizedQuestionText();
        $this->assertEquals($expectedQuestionText, $questionText);
    }

    public function testGetQuestionDescriptionForTranslated(): void
    {
        $this->demographicQuestion->setIsTranslated(true);

        $expectedQuestionDescription = "Ethnicity refers to a group of people who share
            common cultural, historical, linguistic, or ancestral characteristics.
            These characteristics may include geographic origin, language, religion, customs,
            traditions, and shared history. Ethnicity is often associated with cultural identity
            and can play a significant role in shaping the individual and collective identity
            of a group of people.";

        $this->demographicQuestion->setQuestionDescription($expectedQuestionDescription, 'en');
        $questionDescription = $this->demographicQuestion->getLocalizedQuestionDescription();
        $this->assertEquals($expectedQuestionDescription, $questionDescription);
    }

    public function testGetQuestionDescriptionForNotTranslated(): void
    {
        $this->demographicQuestion->setIsTranslated(false);

        $questionDescriptionKey = 'plugin.generic.deiaSurvey.demographicQuestion.exampleQuestion.description';
        $expectedQuestionDescription = __($questionDescriptionKey);
        $this->demographicQuestion->setQuestionDescription($questionDescriptionKey);
        $questionDescription = $this->demographicQuestion->getLocalizedQuestionDescription();
        $this->assertEquals($expectedQuestionDescription, $questionDescription);
    }
}
