<?php

namespace APP\plugins\generic\deiaSurvey\classes;

class OrcidConfiguration
{
    private const ORCID_PLUGIN_NAME = 'orcidprofileplugin';
    private const DEMOGRAPHIC_PLUGIN_NAME = 'demographicdataplugin';

    public function getOrcidConfiguration(int $contextId): ?array
    {
        $demographicConfiguration = $this->getPluginOrcidConfiguration($contextId, self::DEMOGRAPHIC_PLUGIN_NAME, ['orcidAPIPath', 'orcidClientId', 'orcidClientSecret']);
        if (!is_null($demographicConfiguration)) {
            return $demographicConfiguration;
        }

        $orcidProfileConfiguration = $this->getPluginOrcidConfiguration($contextId, self::ORCID_PLUGIN_NAME, ['orcidProfileAPIPath', 'orcidClientId', 'orcidClientSecret']);
        if (!is_null($orcidProfileConfiguration)) {
            return $orcidProfileConfiguration;
        }

        return null;
    }

    private function getPluginOrcidConfiguration($contextId, $pluginName, $settingsNames): ?array
    {
        $pluginSettingsDao = \DAORegistry::getDAO('PluginSettingsDAO');

        $apiPath = $pluginSettingsDao->getSetting($contextId, $pluginName, $settingsNames[0]);
        if (!is_null($apiPath)) {
            $clientId = $pluginSettingsDao->getSetting($contextId, $pluginName, $settingsNames[1]);
            $clientSecret = $pluginSettingsDao->getSetting($contextId, $pluginName, $settingsNames[2]);

            return [
                'pluginName' => $pluginName,
                'apiPath' => $apiPath,
                'clientId' => $clientId,
                'clientSecret' => $clientSecret
            ];
        }

        return null;
    }
}
