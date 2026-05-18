<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicResponseOption;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\deiaSurvey\classes\demographicResponseOption\DemographicResponseOption;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

class DemographicResponseOptionTest extends PKPTestCase
{
    use TestHelperTrait;

    private DemographicResponseOption $demographicResponseOption;

    protected function setUp(): void
    {
        $this->demographicResponseOption = new DemographicResponseOption();
        parent::setUp();
        $this->initializeRequestRouter();
        $this->initializePluginLocaleData();
    }

    public function testGetDemographicQuestionId(): void
    {
        $expectedDemographicQuestionId = 1;
        $this->demographicResponseOption->setDemographicQuestionId($expectedDemographicQuestionId);

        $this->assertEquals($expectedDemographicQuestionId, $this->demographicResponseOption->getDemographicQuestionId());
    }

    public function testGetResponseOptionIsTranslated(): void
    {
        $this->demographicResponseOption->setIsTranslated(true);
        $this->assertTrue($this->demographicResponseOption->isTranslated());

        $this->demographicResponseOption->setIsTranslated(false);
        $this->assertFalse($this->demographicResponseOption->isTranslated());
    }

    public function testGetOptionTextForTranslated(): void
    {
        $this->demographicResponseOption->setIsTranslated(true);

        $expectedResponseOptionText = "Less than a minimum wage";
        $this->demographicResponseOption->setOptionText($expectedResponseOptionText, 'en');
        $optionText = $this->demographicResponseOption->getLocalizedOptionText();

        $this->assertEquals($expectedResponseOptionText, $optionText);
    }

    public function testGetOptionTextForNotTranslated(): void
    {
        $this->demographicResponseOption->setIsTranslated(false);

        $optionTextKey = self::TEST_OPTION_TEXT;
        $expectedResponseOptionText = __($optionTextKey);
        $this->demographicResponseOption->setOptionText($optionTextKey);
        $optionText = $this->demographicResponseOption->getLocalizedOptionText();

        $this->assertEquals($expectedResponseOptionText, $optionText);
    }

    public function testGetOptionTextWithNoTranslatedData(): void
    {
        $optionTextKey = self::TEST_OPTION_TEXT;
        $expectedResponseOptionText = __($optionTextKey);
        $this->demographicResponseOption->setData('optionText', $optionTextKey);
        $optionText = $this->demographicResponseOption->getLocalizedOptionText();
        $this->assertEquals($expectedResponseOptionText, $optionText);

        $expectedResponseOptionText = "Less than a minimum wage";
        $this->demographicResponseOption->setData('optionText', $expectedResponseOptionText, 'en');
        $optionText = $this->demographicResponseOption->getLocalizedOptionText();
        $this->assertEquals($expectedResponseOptionText, $optionText);
    }

    public function testGetHasInputField(): void
    {
        $hasInputField = true;
        $this->demographicResponseOption->setHasInputField($hasInputField);

        $this->assertEquals($hasInputField, $this->demographicResponseOption->hasInputField());
    }
}
