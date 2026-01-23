<?php

namespace APP\plugins\generic\deiaSurvey\tests\report;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\deiaSurvey\report\classes\ContextStatistics;

class ContextStatisticsTest extends PKPTestCase
{
    private $contextStatistics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contextStatistics = new ContextStatistics();
    }

    public function testUsersConsentCount()
    {
        $this->assertEquals(0, $this->contextStatistics->getUsersConsentCount());

        $this->contextStatistics->incrementUsersConsentCount();
        $this->assertEquals(1, $this->contextStatistics->getUsersConsentCount());

        $this->contextStatistics->incrementUsersConsentCount();
        $this->assertEquals(2, $this->contextStatistics->getUsersConsentCount());
    }

    public function testUsersNoConsentCount()
    {
        $this->assertEquals(0, $this->contextStatistics->getUsersNoConsentCount());

        $this->contextStatistics->incrementUsersNoConsentCount();
        $this->assertEquals(1, $this->contextStatistics->getUsersNoConsentCount());

        $this->contextStatistics->incrementUsersNoConsentCount();
        $this->assertEquals(2, $this->contextStatistics->getUsersNoConsentCount());
    }
}
