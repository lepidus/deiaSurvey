<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\core\Application;
use PKP\orcid\OrcidManager;

class OrcidConfiguration
{
    private const ORCID_INTEGRATION_NAME = 'orcid';

    public function getOrcidConfiguration(int $contextId): ?array
    {
        $context = Application::getContextDAO()->getById($contextId);
        if (!$context || !OrcidManager::isEnabled($context)) {
            return null;
        }

        $clientId = OrcidManager::getClientId($context);
        $clientSecret = OrcidManager::getClientSecret($context);
        if (!$clientId || !$clientSecret) {
            return null;
        }

        return [
            'pluginName' => self::ORCID_INTEGRATION_NAME,
            'apiPath' => OrcidManager::getApiPath($context),
            'clientId' => $clientId,
            'clientSecret' => $clientSecret
        ];
    }
}
