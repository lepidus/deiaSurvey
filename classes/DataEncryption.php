<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use Exception;
use Illuminate\Support\Facades\Crypt;

class DataEncryption
{
    public function textIsEncrypted(string $text): bool
    {
        try {
            $this->decryptString($text);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function encryptString(string $plainText): string
    {
        try {
            return Crypt::encryptString($plainText);
        } catch (Exception $e) {
            throw new Exception('DEIA Survey - Failed to encrypt string');
        }
    }

    public function decryptString(string $encryptedText): string
    {
        try {
            return Crypt::decryptString($encryptedText);
        } catch (Exception $e) {
            throw new Exception('DEIA Survey - Failed to decrypt string');
        }
    }
}
