<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use PHPUnit\Framework\TestCase;

class DataCollectionEmailSenderSecurityTest extends TestCase
{
    public function testDeliveryFlowHasNoApplicationLogSinkForSensitiveData(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/classes/DataCollectionEmailSender.php');

        self::assertDoesNotMatchRegularExpression('/\berror_log\s*\(|\bLog::|\blogger\s*\(/', $source);
    }
}
