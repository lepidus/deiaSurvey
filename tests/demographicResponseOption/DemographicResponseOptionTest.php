<?php

namespace APP\plugins\generic\demographicData\tests\demographicResponseOption;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\demographicData\classes\demographicResponseOption\DemographicResponseOption;

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
