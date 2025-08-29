<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use PKP\config\Config;
use Exception;

class DataEncryption
{
    private const ENCRYPTION_CIPHER = 'AES-256-CBC';

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

    public function encryptString(string $plainText): string
    {
        $secret = $this->getSecretFromConfig();
        $initializationVector = openssl_random_pseudo_bytes(
            openssl_cipher_iv_length(self::ENCRYPTION_CIPHER)
        );
        $encryptedString = openssl_encrypt(
            $plainText,
            self::ENCRYPTION_CIPHER,
            $secret,
            0,
            $initializationVector
        );

        if ($encryptedString === false) {
            throw new Exception("DEIA Survey - Failed to encrypt string");
        }

        return base64_encode($initializationVector . '::' . $encryptedString);
    }

    public function decryptString(string $encryptedText): string
    {
        $secret = $this->getSecretFromConfig();
        $data = base64_decode($encryptedText);
        if ($data === false) {
            throw new Exception("DEIA Survey - Invalid base64 encoded data");
        }

        list($initializationVector, $encryptedString) = explode('::', $data, 2);
        $decrypted = openssl_decrypt(
            $encryptedString,
            self::ENCRYPTION_CIPHER,
            $secret,
            0,
            $initializationVector
        );

        if ($decrypted === false) {
            throw new Exception("DEIA Survey - Failed to decrypt string");
        }

        return $decrypted;
    }
}
