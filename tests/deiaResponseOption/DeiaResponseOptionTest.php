<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaResponseOption;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\DeiaResponseOption;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

class DeiaResponseOptionTest extends PKPTestCase
{
    use TestHelperTrait;

    private DeiaResponseOption $deiaResponseOption;

    protected function setUp(): void
    {
        $this->deiaResponseOption = new DeiaResponseOption();
        parent::setUp();
        $this->initializeRequestRouter();
        $this->initializePluginLocaleData();
    }

    public function testGetDeiaQuestionId(): void
    {
        $expectedDeiaQuestionId = 1;
        $this->deiaResponseOption->setDeiaQuestionId($expectedDeiaQuestionId);

        $this->assertEquals($expectedDeiaQuestionId, $this->deiaResponseOption->getDeiaQuestionId());
    }

    public function testGetResponseOptionIsTranslated(): void
    {
        $this->deiaResponseOption->setIsTranslated(true);
        $this->assertTrue($this->deiaResponseOption->isTranslated());

        $this->deiaResponseOption->setIsTranslated(false);
        $this->assertFalse($this->deiaResponseOption->isTranslated());
    }

    public function testGetOptionTextForTranslated(): void
    {
        $this->deiaResponseOption->setIsTranslated(true);

        $expectedResponseOptionText = "Less than a minimum wage";
        $this->deiaResponseOption->setOptionText($expectedResponseOptionText, 'en');
        $optionText = $this->deiaResponseOption->getLocalizedOptionText();

        $this->assertEquals($expectedResponseOptionText, $optionText);
    }

    public function testGetOptionTextForNotTranslated(): void
    {
        $this->deiaResponseOption->setIsTranslated(false);

        $optionTextKey = self::TEST_OPTION_TEXT;
        $expectedResponseOptionText = __($optionTextKey);
        $this->deiaResponseOption->setOptionText($optionTextKey);
        $optionText = $this->deiaResponseOption->getLocalizedOptionText();

        $this->assertEquals($expectedResponseOptionText, $optionText);
    }

    public function testGetOptionTextWithNoTranslatedData(): void
    {
        $optionTextKey = self::TEST_OPTION_TEXT;
        $expectedResponseOptionText = __($optionTextKey);
        $this->deiaResponseOption->setData('optionText', $optionTextKey);
        $optionText = $this->deiaResponseOption->getLocalizedOptionText();
        $this->assertEquals($expectedResponseOptionText, $optionText);

        $expectedResponseOptionText = "Less than a minimum wage";
        $this->deiaResponseOption->setData('optionText', $expectedResponseOptionText, 'en');
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
