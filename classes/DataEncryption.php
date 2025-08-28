<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use Firebase\JWT\JWT;
use PKP\config\Config;
use Exception;

class DataEncryption
{
    public function secretConfigExists(): bool
    {
        try {
            $this->getSecretFromConfig();
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    private function getSecretFromConfig(): string
    {
        $secret = Config::getVar('security', 'api_key_secret');
        if ($secret === "") {
            throw new Exception("DEIA Survey - A secret must be set in the config file ('api_key_secret') so that keys can be encrypted and decrypted");
        }
        return $secret;
    }
}
