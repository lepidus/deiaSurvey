<?php

namespace APP\plugins\generic\deiaSurvey\tests\controllers;

use APP\plugins\generic\deiaSurvey\classes\controllers\TabHandler;
use PHPUnit\Framework\TestCase;

class TabHandlerTest extends TestCase
{
    public function testRejectsNonPostRequestBeforeChangingData(): void
    {
        $request = new class () {
            public function isPost(): bool
            {
                return false;
            }
        };

        $response = (new TabHandler())->saveDeiaData([], $request);

        self::assertFalse($response->getStatus());
    }
}
