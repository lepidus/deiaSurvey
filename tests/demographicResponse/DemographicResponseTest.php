<?php

namespace APP\plugins\generic\demographicData\tests\demographicResponse;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\demographicData\classes\demographicResponse\DemographicResponse;

import('lib.pkp.tests.PKPTestCase');

class DemographicResponseTest extends \PKPTestCase
{
    private DemographicResponse $demographicResponse;

    protected function setUp(): void
    {
        $this->demographicResponse = new DemographicResponse();
        parent::setUp();
    }

    public function testGetDemographicQuestionId(): void
    {
        $expectedDemographicQuestionId = 1;
        $this->demographicResponse->setDemographicQuestionId($expectedDemographicQuestionId);
        $this->assertEquals($this->demographicResponse->getDemographicQuestionId(), $expectedDemographicQuestionId);
    }

    public function testGetDemographicUserId(): void
    {
        $expectedUserId = 1;
        $this->demographicResponse->setUserId($expectedUserId);
        $this->assertEquals($this->demographicResponse->getUserId(), $expectedUserId);
    }

    public function testGetDemographicExternalId(): void
    {
        $expectedExternalId = 'external.author@lepidus.com.br';
        $this->demographicResponse->setExternalId($expectedExternalId);
        $this->assertEquals($this->demographicResponse->getExternalId(), $expectedExternalId);
    }

    public function testGetDemographicExternalType(): void
    {
        $expectedExternalType = 'email';
        $this->demographicResponse->setExternalType($expectedExternalType);
        $this->assertEquals($this->demographicResponse->getExternalType(), $expectedExternalType);
    }

    public function testGetDemographicResponseValue(): void
    {
        $expectedDemographicResponseValue = ["en" => "I'm from Parintins"];
        $this->demographicResponse->setValue(['en' => "I'm from Parintins"]);
        $this->assertEquals($this->demographicResponse->getValue(), $expectedDemographicResponseValue);
    }

    public function testGetDemographicOptionsInputValue(): void
    {
        $expectedOptionsInputValue = [45 => 'Aditional information for response option'];
        $this->demographicResponse->setOptionsInputValue([45 => 'Aditional information for response option']);
        $this->assertEquals($this->demographicResponse->getOptionsInputValue(), $expectedOptionsInputValue);
    }
}
