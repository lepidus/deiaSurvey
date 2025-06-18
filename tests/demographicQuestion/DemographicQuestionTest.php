<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicQuestion;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\DemographicQuestion;

import('lib.pkp.tests.PKPTestCase');

class DemographicQuestionTest extends \PKPTestCase
{
    private $demographicQuestion;

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

    public function testGetQuestionText(): void
    {
        $expectedQuestionText = "What is your ethnicity?";
        $this->demographicQuestion->setQuestionText($expectedQuestionText, 'en');
        $questionText = $this->demographicQuestion->getLocalizedQuestionText();
        $this->assertEquals($expectedQuestionText, $questionText);
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
        $questionDescription = $this->demographicQuestion->getLocalizedQuestionDescription();
        $this->assertEquals($expectedQuestionDescription, $questionDescription);
    }
}
