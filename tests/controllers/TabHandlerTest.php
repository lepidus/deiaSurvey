<?php

namespace APP\plugins\generic\deiaSurvey\tests\controllers;

use APP\core\Request;
use APP\plugins\generic\deiaSurvey\classes\controllers\TabHandler;
use PHPUnit\Framework\TestCase;

class TabHandlerTest extends TestCase
{
    public function testRejectsAnonymousProfileRequest(): void
    {
        $request = $this->getMockBuilder(Request::class)->onlyMethods(['getUser', 'getContext'])->getMock();
        $request->method('getUser')->willReturn(null);
        $request->method('getContext')->willReturn(null);

        self::assertFalse((new TabHandler())->deiaSurvey([], $request)->getStatus());
    }

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
