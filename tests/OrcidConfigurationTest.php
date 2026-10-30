<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use APP\plugins\generic\deiaSurvey\classes\OrcidConfiguration;
use APP\plugins\generic\deiaSurvey\tests\helpers\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use Illuminate\Support\Facades\DB;
use PKP\db\DAORegistry;
use PKP\orcid\OrcidManager;

class OrcidConfigurationTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $orcidConfiguration;
    private $contextId;
    private $orcidAPIPath = OrcidManager::ORCID_API_URL_PUBLIC_SANDBOX;
    private $orcidClientId = 'APP-F1RSTCL1ENT1ID';
    private $orcidClientSecret = 'first-false-secret-33ba178dc2b9';
    private $deiaAPIPath = 'https://api.sandbox.orcid.org/';

    protected function setUp(): void
    {
        $this->orcidConfiguration = new OrcidConfiguration();
        parent::setUp();
        $this->contextId = $this->createTestContext();
    }

    private function insertPluginSettings($pluginName, $settingName, $settingValue)
    {
        $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
        $pluginSettingsDao->updateSetting($this->contextId, $pluginName, $settingName, $settingValue);
    }

    private function insertOrcidSetting(string $settingName, string $settingValue): void
    {
        DB::table('journal_settings')->updateOrInsert(
            [
                'journal_id' => $this->contextId,
                'locale' => '',
                'setting_name' => $settingName
            ],
            ['setting_value' => $settingValue]
        );
    }

    public function testNoOrcidConfiguration(): void
    {
        $orcidConfiguration = $this->orcidConfiguration->getOrcidConfiguration($this->contextId);
        $this->assertNull($orcidConfiguration);
    }

    public function testOrcidConfigurationFromNativeOrcidSettings(): void
    {
        $this->insertOrcidSetting(OrcidManager::ENABLED, '1');
        $this->insertOrcidSetting(OrcidManager::API_TYPE, OrcidManager::API_PUBLIC_SANDBOX);
        $this->insertOrcidSetting(OrcidManager::CLIENT_ID, $this->orcidClientId);
        $this->insertOrcidSetting(OrcidManager::CLIENT_SECRET, $this->orcidClientSecret);
        $orcidConfiguration = $this->orcidConfiguration->getOrcidConfiguration($this->contextId);

        $expectedConfiguration = [
            'pluginName' => 'orcid',
            'apiPath' => $this->orcidAPIPath,
            'clientId' => $this->orcidClientId,
            'clientSecret' => $this->orcidClientSecret
        ];
        $this->assertEquals($expectedConfiguration, $orcidConfiguration);
    }

    public function testIgnoresOrcidConfigurationFromDeiaPlugin(): void
    {
        $this->insertPluginSettings('deiasurveyplugin', 'orcidAPIPath', $this->deiaAPIPath);
        $this->insertPluginSettings('deiasurveyplugin', 'orcidClientId', 'APP-S3C0NDCL1ENT1D');
        $this->insertPluginSettings('deiasurveyplugin', 'orcidClientSecret', 'second-false-secret-33ba178dc2b9');
        $orcidConfiguration = $this->orcidConfiguration->getOrcidConfiguration($this->contextId);

        $this->assertNull($orcidConfiguration);
    }
}
