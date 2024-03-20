<?php

namespace APP\plugins\generic\demographicData\tests\demographicQuestion;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;

class DemographicQuestionTest extends PKPTestCase
{
    public function testGetContextId(): void
    {
        $expectedContextId = 1;
        $demographicQuestion = new DemographicQuestion();
        $demographicQuestion->setContextId($expectedContextId);
        $this->assertEquals($demographicQuestion->getContextId(), $expectedContextId);
    }
}
