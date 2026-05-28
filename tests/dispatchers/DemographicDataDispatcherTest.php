<?php

namespace APP\plugins\generic\deiaSurvey\tests\dispatchers;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\dispatchers\DeiaDataDispatcher;
use APP\plugins\generic\deiaSurvey\classes\dispatchers\DemographicDataDispatcher;

import('lib.pkp.tests.PKPTestCase');

class DemographicDataDispatcherTest extends \PKPTestCase
{
    public function testKeepsBackwardCompatibleDispatcherClassName(): void
    {
        self::assertTrue(class_exists(DemographicDataDispatcher::class));
        self::assertTrue(is_subclass_of(DemographicDataDispatcher::class, DeiaDataDispatcher::class));
    }
}
