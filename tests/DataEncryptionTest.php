<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once(dirname(__DIR__) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\DataEncryption;

import('lib.pkp.tests.PKPTestCase');

class DataEncryptionTest extends \PKPTestCase
{
    public function testTextWithoutEncryptionPrefixIsNotEncrypted(): void
    {
        $encryption = new DataEncryption();

        $this->assertFalse($encryption->textIsEncrypted('plain text'));
        $this->assertFalse($encryption->textIsEncrypted('base'));
        $this->assertFalse($encryption->textIsEncrypted(''));
        $this->assertFalse($encryption->textIsEncrypted('base64:not-valid-encrypted-data'));
    }

    public function testRecognizesEncryptedText(): void
    {
        $encryption = new DataEncryption();
        $encryptedText = $encryption->encryptString('sensitive response');

        $this->assertTrue($encryption->textIsEncrypted($encryptedText));
        $this->assertSame('sensitive response', $encryption->decryptString($encryptedText));
    }
}
