<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicResponseOption;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\deiaSurvey\classes\demographicResponseOption\DemographicResponseOption;

class DemographicResponseOptionTest extends PKPTestCase
{
    private DemographicResponseOption $demographicResponseOption;

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

    public function testGetResponseOptionText(): void
    {
        $expectedResponseOptionText = "Less than a minimum wage";
        $this->demographicResponseOption->setOptionText($expectedResponseOptionText, 'en');
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
