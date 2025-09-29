<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use PKP\config\Config;
use Illuminate\Encryption\Encrypter;
use Exception;

class DataEncryption
{
    private const ENCRYPTION_CIPHER = 'aes-256-cbc';

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

        return $this->normalizeSecret($secret);
    }

    private function normalizeSecret(string $secret): string
    {
        return hash('sha256', $secret, true);
    }

    public function textIsEncrypted(string $text): bool
    {
        if (!str_starts_with($text, 'base64:')) {
            return false;
        }

        try {
            $this->decryptString($text);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function encryptString(string $plainText): string
    {
        $secret = $this->getSecretFromConfig();
        $encrypter = new Encrypter($secret, self::ENCRYPTION_CIPHER);

        try {
            $encryptedString = $encrypter->encrypt($plainText);
        } catch (Exception $e) {
            throw new Exception("DEIA Survey - Failed to encrypt string");
        }

        return 'base64:' . base64_encode($encryptedString);
    }

    public function decryptString(string $encryptedText): string
    {
        $secret = $this->getSecretFromConfig();
        $encrypter = new Encrypter($secret, self::ENCRYPTION_CIPHER);

        $encryptedText = str_replace('base64:', '', $encryptedText);
        $payload = base64_decode($encryptedText);

        try {
            $decryptedString = $encrypter->decrypt($payload);
        } catch (Exception $e) {
            throw new Exception("DEIA Survey - Failed to decrypt string");
        }

        return $decryptedString;
    }
}
