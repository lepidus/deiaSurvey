<?php

namespace APP\plugins\generic\deiaSurvey\tests\dispatchers;

use APP\plugins\generic\deiaSurvey\classes\dispatchers\DeiaDataDispatcher;
use APP\plugins\generic\deiaSurvey\classes\dispatchers\DemographicDataDispatcher;
use PKP\tests\PKPTestCase;

class DemographicDataDispatcherTest extends PKPTestCase
{
    public function testKeepsBackwardCompatibleDispatcherClassName(): void
    {
        self::assertTrue(class_exists(DemographicDataDispatcher::class));
        self::assertTrue(is_subclass_of(DemographicDataDispatcher::class, DeiaDataDispatcher::class));
    }
}
