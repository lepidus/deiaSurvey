<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use APP\plugins\generic\deiaSurvey\classes\DataEncryption;
use Illuminate\Support\Facades\Crypt;
use PKP\tests\PKPTestCase;

class DataEncryptionTest extends PKPTestCase
{
    public function testEncryptStringUsesApplicationKeyEncryption(): void
    {
        $plainText = serialize(['en' => 'Test text']);
        $encryptedText = (new DataEncryption())->encryptString($plainText);

        self::assertSame($plainText, Crypt::decryptString($encryptedText));
    }

    public function testDecryptStringReadsApplicationKeyEncryption(): void
    {
        $plainText = serialize(['en' => 'Test text']);
        $encryptedText = Crypt::encryptString($plainText);

        self::assertSame($plainText, (new DataEncryption())->decryptString($encryptedText));
    }
}
