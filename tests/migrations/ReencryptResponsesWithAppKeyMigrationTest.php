<?php

namespace APP\plugins\generic\deiaSurvey\tests\migrations;

use APP\plugins\generic\deiaSurvey\classes\migrations\ReencryptResponsesWithAppKeyMigration;
use APP\plugins\generic\deiaSurvey\tests\helpers\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PKP\config\Config;

class ReencryptResponsesWithAppKeyMigrationTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private const LEGACY_SECRET = 'legacy-secret';
    private const LEGACY_SECRET_SETTING = 'api_key_' . 'secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->isolateDeiaTables();
    }

    public function testReencryptsLegacyResponseValueWithApplicationKey(): void
    {
        $this->setLegacySecret();
        $legacyPayload = $this->encryptWithLegacySecret(serialize(['en' => 'Test text']));
        $responseId = $this->createResponseRow();

        DB::table('deia_response_settings')->insert([
            'deia_response_id' => $responseId,
            'locale' => '',
            'setting_name' => 'responseValue',
            'setting_value' => $legacyPayload,
        ]);

        (new ReencryptResponsesWithAppKeyMigration())->up();

        $updatedValue = DB::table('deia_response_settings')
            ->where('deia_response_id', $responseId)
            ->value('setting_value');

        self::assertSame(serialize(['en' => 'Test text']), Crypt::decryptString($updatedValue));
    }

    public function testDoesNotReencryptApplicationKeyEncryptedResponses(): void
    {
        $encryptedValue = Crypt::encryptString(serialize(['en' => 'Test text']));
        $responseId = $this->createResponseRow();

        DB::table('deia_response_settings')->insert([
            'deia_response_id' => $responseId,
            'locale' => '',
            'setting_name' => 'responseValue',
            'setting_value' => $encryptedValue,
        ]);

        (new ReencryptResponsesWithAppKeyMigration())->up();

        $updatedValue = DB::table('deia_response_settings')
            ->where('deia_response_id', $responseId)
            ->value('setting_value');

        self::assertSame($encryptedValue, $updatedValue);
    }

    private function setLegacySecret(): void
    {
        $configData = &Config::getData();
        $configData['security'][self::LEGACY_SECRET_SETTING] = self::LEGACY_SECRET;
    }

    private function encryptWithLegacySecret(string $plainText): string
    {
        $secret = hash('sha256', self::LEGACY_SECRET, true);
        $encrypter = new Encrypter($secret, 'aes-256-cbc');

        return 'base64:' . base64_encode($encrypter->encrypt($plainText));
    }

    private function createResponseRow(): int
    {
        $questionId = DB::table('deia_questions')->insertGetId([
            'context_id' => $this->createTestContext(),
            'deia_question_block_id' => null,
            'seq' => null,
            'question_type' => 1,
        ]);

        return (int) DB::table('deia_responses')->insertGetId([
            'deia_question_id' => $questionId,
            'user_id' => null,
            'external_id' => null,
            'external_type' => null,
        ]);
    }

}
