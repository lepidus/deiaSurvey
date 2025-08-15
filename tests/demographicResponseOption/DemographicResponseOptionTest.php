<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicResponseOption;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\demographicResponseOption\DemographicResponseOption;

import('lib.pkp.tests.PKPTestCase');

class DemographicResponseOptionTest extends \PKPTestCase
{
    private $demographicResponseOption;

    protected function setUp(): void
    {
        $this->demographicResponseOption = new DemographicResponseOption();
        parent::setUp();
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
        $this->demographicResponseOption->setOptionText($expectedResponseOptionText, 'en_US');
        $optionText = $this->demographicResponseOption->getLocalizedOptionText();

        $this->assertEquals($expectedResponseOptionText, $optionText);
    }

    public function testGetOptionTextForNotTranslated(): void
    {
        $this->demographicResponseOption->setIsTranslated(false);

        $optionTextKey = 'plugins.generic.deiaSurvey.demographicQuestion.exampleQuestion.title';
        $expectedResponseOptionText = __($optionTextKey);
        $this->demographicResponseOption->setOptionText($optionTextKey);
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
