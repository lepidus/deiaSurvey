<?php

namespace APP\plugins\generic\demographicData\tests;

use PKP\tests\PKPTestCase;
use PKP\db\DAORegistry;
use APP\plugins\generic\demographicData\classes\OrcidConfiguration;

class OrcidConfigurationTest extends PKPTestCase
{
    private $orcidConfiguration;
    private $contextId = 10;
    private $orcidAPIPath = 'https://pub.sandbox.orcid.org/';
    private $orcidClientId = 'APP-F1RSTCL1ENT1ID';
    private $orcidClientSecret = 'first-false-secret-33ba178dc2b9';
    private $demographicAPIPath = 'https://api.sandbox.orcid.org/';
    private $demographicClientId = 'APP-S3C0NDCL1ENT1D';
    private $demographicClientSecret = 'second-false-secret-33ba178dc2b9';

    protected function setUp(): void
    {
        $this->orcidConfiguration = new OrcidConfiguration();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $pluginSettingsToClean = [
            'demographicdataplugin' => ['orcidAPIPath', 'orcidClientId', 'orcidClientSecret'],
            'orcidprofileplugin' => ['orcidProfileAPIPath', 'orcidClientId', 'orcidClientSecret']
        ];
        $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');

        foreach ($pluginSettingsToClean as $pluginName => $settings) {
            foreach ($settings as $settingName) {
                $pluginSettingsDao->deleteSetting($this->contextId, $pluginName, $settingName);
            }
        }

        parent::tearDown();
    }

    private function insertPluginSettings($pluginName, $settingName, $settingValue)
    {
        $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
        $pluginSettingsDao->updateSetting($this->contextId, $pluginName, $settingName, $settingValue);
    }

    public function testNoOrcidConfiguration(): void
    {
        $orcidConfiguration = $this->orcidConfiguration->getOrcidConfiguration($this->contextId);
        $this->assertNull($orcidConfiguration);
    }

    public function testOrcidConfigurationFromOrcidPlugin(): void
    {
        $this->insertPluginSettings('orcidprofileplugin', 'orcidProfileAPIPath', $this->orcidAPIPath);
        $this->insertPluginSettings('orcidprofileplugin', 'orcidClientId', $this->orcidClientId);
        $this->insertPluginSettings('orcidprofileplugin', 'orcidClientSecret', $this->orcidClientSecret);
        $orcidConfiguration = $this->orcidConfiguration->getOrcidConfiguration($this->contextId);

        $expectedConfiguration = [
            'pluginName' => 'orcidprofileplugin',
            'apiPath' => $this->orcidAPIPath,
            'clientId' => $this->orcidClientId,
            'clientSecret' => $this->orcidClientSecret
        ];
        $this->assertEquals($expectedConfiguration, $orcidConfiguration);
    }

    public function testOrcidConfigurationFromDemographicPlugin(): void
    {
        $this->insertPluginSettings('orcidprofileplugin', 'orcidProfileAPIPath', $this->orcidAPIPath);
        $this->insertPluginSettings('orcidprofileplugin', 'orcidClientId', $this->orcidClientId);
        $this->insertPluginSettings('orcidprofileplugin', 'orcidClientSecret', $this->orcidClientSecret);

        $this->insertPluginSettings('demographicdataplugin', 'orcidAPIPath', $this->demographicAPIPath);
        $this->insertPluginSettings('demographicdataplugin', 'orcidClientId', $this->demographicClientId);
        $this->insertPluginSettings('demographicdataplugin', 'orcidClientSecret', $this->demographicClientSecret);
        $orcidConfiguration = $this->orcidConfiguration->getOrcidConfiguration($this->contextId);

        $expectedConfiguration = [
            'pluginName' => 'demographicdataplugin',
            'apiPath' => $this->demographicAPIPath,
            'clientId' => $this->demographicClientId,
            'clientSecret' => $this->demographicClientSecret
        ];
        $this->assertEquals($expectedConfiguration, $orcidConfiguration);
    }
}
