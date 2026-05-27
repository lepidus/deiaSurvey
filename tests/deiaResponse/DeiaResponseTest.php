<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaResponse;

use APP\plugins\generic\deiaSurvey\classes\deiaResponse\DeiaResponse;
use PKP\tests\PKPTestCase;

class DeiaResponseTest extends PKPTestCase
{
    private DeiaResponse $deiaResponse;

    protected function setUp(): void
    {
        $this->deiaResponse = new DeiaResponse();
        parent::setUp();
    }

    public function testGetDeiaQuestionId(): void
    {
        $expectedDeiaQuestionId = 1;
        $this->deiaResponse->setDeiaQuestionId($expectedDeiaQuestionId);
        $this->assertEquals($this->deiaResponse->getDeiaQuestionId(), $expectedDeiaQuestionId);
    }

    public function testGetDeiaUserId(): void
    {
        $expectedUserId = 1;
        $this->deiaResponse->setUserId($expectedUserId);
        $this->assertEquals($this->deiaResponse->getUserId(), $expectedUserId);
    }

    public function testGetDeiaExternalId(): void
    {
        $expectedExternalId = 'external.author@lepidus.com.br';
        $this->deiaResponse->setExternalId($expectedExternalId);
        $this->assertEquals($this->deiaResponse->getExternalId(), $expectedExternalId);
    }

    public function testGetDeiaExternalType(): void
    {
        $expectedExternalType = 'email';
        $this->deiaResponse->setExternalType($expectedExternalType);
        $this->assertEquals($this->deiaResponse->getExternalType(), $expectedExternalType);
    }

    public function testGetDeiaResponseValue(): void
    {
        $expectedDeiaResponseValue = ['en' => "I'm from Parintins"];
        $this->deiaResponse->setValue(['en' => "I'm from Parintins"]);
        $this->assertEquals($this->deiaResponse->getValue(), $expectedDeiaResponseValue);
    }

    public function testGetDeiaOptionsInputValue(): void
    {
        $expectedOptionsInputValue = [45 => 'Aditional information for response option'];
        $this->deiaResponse->setOptionsInputValue([45 => 'Aditional information for response option']);
        $this->assertEquals($this->deiaResponse->getOptionsInputValue(), $expectedOptionsInputValue);
    }
}
