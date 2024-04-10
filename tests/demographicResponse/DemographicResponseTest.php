<?php

namespace APP\plugins\generic\demographicData\tests\demographicResponse;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\demographicData\classes\demographicResponse\DemographicResponse;

class DemographicResponseTest extends PKPTestCase
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

    public function testGetLocalizedDemographicResponseText(): void
    {
        $expectedDemographicResponseText = "I'm from Parintins";
        $this->demographicResponse->setText($expectedDemographicResponseText, 'en');
        $this->assertEquals($this->demographicResponse->getLocalizedText(), $expectedDemographicResponseText);
    }

    public function testGetDemographicResponseText(): void
    {
        $expectedDemographicResponseText = ["en" => "I'm from Parintins"];
        $this->demographicResponse->setText("I'm from Parintins", 'en');
        $this->assertEquals($this->demographicResponse->getText(), $expectedDemographicResponseText);
    }
}
