<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaResponseOption;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\DeiaResponseOption;

import('lib.pkp.tests.PKPTestCase');

class DeiaResponseOptionTest extends \PKPTestCase
{
    private $deiaResponseOption;

    protected function setUp(): void
    {
        $this->deiaResponseOption = new DeiaResponseOption();
        parent::setUp();
    }

    public function testGetDeiaQuestionId(): void
    {
        $expectedDeiaQuestionId = 1;
        $this->deiaResponseOption->setDeiaQuestionId($expectedDeiaQuestionId);

        $this->assertEquals($expectedDeiaQuestionId, $this->deiaResponseOption->getDeiaQuestionId());
    }

    public function testGetResponseOptionIsTranslated(): void
    {
        $this->assertNull($this->deiaResponseOption->isTranslated());

        $this->deiaResponseOption->setIsTranslated(true);
        $this->assertTrue($this->deiaResponseOption->isTranslated());

        $this->deiaResponseOption->setIsTranslated(false);
        $this->assertFalse($this->deiaResponseOption->isTranslated());
    }

    public function testGetOptionTextForTranslated(): void
    {
        $this->deiaResponseOption->setIsTranslated(true);

        $expectedResponseOptionText = "Less than a minimum wage";
        $this->deiaResponseOption->setOptionText($expectedResponseOptionText, 'en_US');
        $optionText = $this->deiaResponseOption->getLocalizedOptionText();

        $this->assertEquals($expectedResponseOptionText, $optionText);
    }

    public function testGetOptionTextForNotTranslated(): void
    {
        $this->deiaResponseOption->setIsTranslated(false);

        $optionTextKey = 'plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.title';
        $expectedResponseOptionText = __($optionTextKey);
        $this->deiaResponseOption->setOptionText($optionTextKey);
        $optionText = $this->deiaResponseOption->getLocalizedOptionText();

        $this->assertEquals($expectedResponseOptionText, $optionText);
    }

    public function testGetOptionTextWithNoTranslatedData(): void
    {
        $optionTextKey = 'plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.title';
        $expectedResponseOptionText = __($optionTextKey);
        $this->deiaResponseOption->setData('optionText', $optionTextKey);
        $optionText = $this->deiaResponseOption->getLocalizedOptionText();
        $this->assertEquals($expectedResponseOptionText, $optionText);

        $this->deiaResponseOption->unsetData('optionText');

        $expectedResponseOptionText = "Less than a minimum wage";
        $this->deiaResponseOption->setData('optionText', $expectedResponseOptionText, 'en_US');
        $optionText = $this->deiaResponseOption->getLocalizedOptionText();
        $this->assertEquals($expectedResponseOptionText, $optionText);
    }

    public function testGetHasInputField(): void
    {
        $hasInputField = true;
        $this->deiaResponseOption->setHasInputField($hasInputField);

        $this->assertEquals($hasInputField, $this->deiaResponseOption->hasInputField());
    }
}
