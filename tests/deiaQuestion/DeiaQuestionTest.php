<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaQuestion;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;

import('lib.pkp.tests.PKPTestCase');

class DeiaQuestionTest extends \PKPTestCase
{
    private $deiaQuestion;

    protected function setUp(): void
    {
        $this->deiaQuestion = new DeiaQuestion();
        parent::setUp();
    }

    public function testGetContextId(): void
    {
        $expectedContextId = 1;
        $this->deiaQuestion->setContextId($expectedContextId);
        $this->assertEquals($expectedContextId, $this->deiaQuestion->getContextId());
    }

    public function testGetQuestionType(): void
    {
        $expectedQuestionType = DeiaQuestion::TYPE_SMALL_TEXT_FIELD;
        $this->deiaQuestion->setQuestionType($expectedQuestionType);
        $this->assertEquals($expectedQuestionType, $this->deiaQuestion->getQuestionType());
    }

    public function testGetQuestionInputType(): void
    {
        $this->deiaQuestion->setQuestionType(DeiaQuestion::TYPE_SMALL_TEXT_FIELD);
        $this->assertEquals('text', $this->deiaQuestion->getQuestionInputType());

        $this->deiaQuestion->setQuestionType(DeiaQuestion::TYPE_TEXT_FIELD);
        $this->assertEquals('text', $this->deiaQuestion->getQuestionInputType());

        $this->deiaQuestion->setQuestionType(DeiaQuestion::TYPE_TEXTAREA);
        $this->assertEquals('textarea', $this->deiaQuestion->getQuestionInputType());

        $this->deiaQuestion->setQuestionType(DeiaQuestion::TYPE_CHECKBOXES);
        $this->assertEquals('checkbox', $this->deiaQuestion->getQuestionInputType());

        $this->deiaQuestion->setQuestionType(DeiaQuestion::TYPE_RADIO_BUTTONS);
        $this->assertEquals('radio', $this->deiaQuestion->getQuestionInputType());

        $this->deiaQuestion->setQuestionType(DeiaQuestion::TYPE_DROP_DOWN_BOX);
        $this->assertEquals('select', $this->deiaQuestion->getQuestionInputType());
    }

    public function testGetQuestionIsTranslated(): void
    {
        $this->assertNull($this->deiaQuestion->isTranslated());

        $this->deiaQuestion->setIsTranslated(true);
        $this->assertTrue($this->deiaQuestion->isTranslated());

        $this->deiaQuestion->setIsTranslated(false);
        $this->assertFalse($this->deiaQuestion->isTranslated());
    }

    public function testGetQuestionIsDefaultQuestion(): void
    {
        $this->assertFalse($this->deiaQuestion->isDefaultQuestion());

        $this->deiaQuestion->setIsDefaultQuestion(true);
        $this->assertTrue($this->deiaQuestion->isDefaultQuestion());

        $this->deiaQuestion->setIsDefaultQuestion(false);
        $this->assertFalse($this->deiaQuestion->isDefaultQuestion());
    }

    public function testGetQuestionTextForTranslated(): void
    {
        $this->deiaQuestion->setIsTranslated(true);

        $expectedQuestionText = "What is your ethnicity?";
        $this->deiaQuestion->setQuestionText($expectedQuestionText, 'en_US');
        $questionText = $this->deiaQuestion->getLocalizedQuestionText();
        $this->assertEquals($expectedQuestionText, $questionText);
    }

    public function testGetQuestionTextForNotTranslated(): void
    {
        $this->deiaQuestion->setIsTranslated(false);

        $questionTextKey = 'plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.title';
        $expectedQuestionText = __($questionTextKey);
        $this->deiaQuestion->setQuestionText($questionTextKey);
        $questionText = $this->deiaQuestion->getLocalizedQuestionText();
        $this->assertEquals($expectedQuestionText, $questionText);
    }

    public function testGetQuestionTextWithNoTranslatedData(): void
    {
        $questionTextKey = 'plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.title';
        $expectedQuestionText = __($questionTextKey);
        $this->deiaQuestion->setData('questionText', $questionTextKey);
        $questionText = $this->deiaQuestion->getLocalizedQuestionText();
        $this->assertEquals($expectedQuestionText, $questionText);

        $this->deiaQuestion->unsetData('questionText');

        $expectedQuestionText = "What is your ethnicity?";
        $this->deiaQuestion->setData('questionText', $expectedQuestionText, 'en_US');
        $questionText = $this->deiaQuestion->getLocalizedQuestionText();
        $this->assertEquals($expectedQuestionText, $questionText);
    }

    public function testGetQuestionDescriptionForTranslated(): void
    {
        $this->deiaQuestion->setIsTranslated(true);

        $expectedQuestionDescription = "Ethnicity refers to a group of people who share
            common cultural, historical, linguistic, or ancestral characteristics.
            These characteristics may include geographic origin, language, religion, customs,
            traditions, and shared history. Ethnicity is often associated with cultural identity
            and can play a significant role in shaping the individual and collective identity
            of a group of people.";

        $this->deiaQuestion->setQuestionDescription($expectedQuestionDescription, 'en_US');
        $questionDescription = $this->deiaQuestion->getLocalizedQuestionDescription();
        $this->assertEquals($expectedQuestionDescription, $questionDescription);
    }

    public function testGetQuestionDescriptionForNotTranslated(): void
    {
        $this->deiaQuestion->setIsTranslated(false);

        $questionDescriptionKey = 'plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.description';
        $expectedQuestionDescription = __($questionDescriptionKey);
        $this->deiaQuestion->setQuestionDescription($questionDescriptionKey);
        $questionDescription = $this->deiaQuestion->getLocalizedQuestionDescription();
        $this->assertEquals($expectedQuestionDescription, $questionDescription);
    }

    public function testGetQuestionDescriptionWithNoTranslatedData(): void
    {
        $questionDescriptionKey = 'plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.description';
        $expectedQuestionDescription = __($questionDescriptionKey);
        $this->deiaQuestion->setData('questionDescription', $questionDescriptionKey);
        $questionDescription = $this->deiaQuestion->getLocalizedQuestionDescription();
        $this->assertEquals($expectedQuestionDescription, $questionDescription);

        $this->deiaQuestion->unsetData('questionDescription');

        $expectedQuestionDescription = "Lorem ipsum dolor sit amet";
        $this->deiaQuestion->setData('questionDescription', $expectedQuestionDescription, 'en_US');
        $questionDescription = $this->deiaQuestion->getLocalizedQuestionDescription();
        $this->assertEquals($expectedQuestionDescription, $questionDescription);
    }

}
