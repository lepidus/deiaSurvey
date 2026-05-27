<?php

namespace APP\plugins\generic\deiaSurvey\tests\migrations;

use APP\plugins\generic\deiaSurvey\classes\migrations\ReencryptResponsesWithAppKeyMigration;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PKP\config\Config;
use PKP\tests\DatabaseTestCase;

class ReencryptResponsesWithAppKeyMigrationTest extends DatabaseTestCase
{
    private const LEGACY_SECRET = 'legacy-secret';
    private const LEGACY_SECRET_SETTING = 'api_key_' . 'secret';
    private const QUESTION_ID_ONE = 990001;
    private const QUESTION_ID_TWO = 990002;
    private const RESPONSE_ID_ONE = 990001;
    private const RESPONSE_ID_TWO = 990002;
    private const SETTING_ID_ONE = 990001;
    private const SETTING_ID_TWO = 990002;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deleteFixtureRows();
    }

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'deia_questions',
            'deia_responses',
            'deia_response_settings',
        ];
    }

    public function testReencryptsLegacyResponseValueWithApplicationKey(): void
    {
        $this->setLegacySecret();
        $legacyPayload = $this->encryptWithLegacySecret(serialize(['en' => 'Test text']));
        $this->createResponseRow(self::QUESTION_ID_ONE, self::RESPONSE_ID_ONE);

        DB::table('deia_response_settings')->insert([
            'deia_response_setting_id' => self::SETTING_ID_ONE,
            'deia_response_id' => self::RESPONSE_ID_ONE,
            'locale' => '',
            'setting_name' => 'responseValue',
            'setting_value' => $legacyPayload,
        ]);

        (new ReencryptResponsesWithAppKeyMigration())->up();

        $updatedValue = DB::table('deia_response_settings')
            ->where('deia_response_setting_id', self::SETTING_ID_ONE)
            ->value('setting_value');

        self::assertSame(serialize(['en' => 'Test text']), Crypt::decryptString($updatedValue));
    }

    public function testDoesNotReencryptApplicationKeyEncryptedResponses(): void
    {
        $encryptedValue = Crypt::encryptString(serialize(['en' => 'Test text']));
        $this->createResponseRow(self::QUESTION_ID_TWO, self::RESPONSE_ID_TWO);

        DB::table('deia_response_settings')->insert([
            'deia_response_setting_id' => self::SETTING_ID_TWO,
            'deia_response_id' => self::RESPONSE_ID_TWO,
            'locale' => '',
            'setting_name' => 'responseValue',
            'setting_value' => $encryptedValue,
        ]);

        (new ReencryptResponsesWithAppKeyMigration())->up();

        $updatedValue = DB::table('deia_response_settings')
            ->where('deia_response_setting_id', self::SETTING_ID_TWO)
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

    private function createResponseRow(int $questionId, int $responseId): void
    {
        DB::table('deia_questions')->insert([
            'deia_question_id' => $questionId,
            'context_id' => 1,
            'deia_question_block_id' => null,
            'seq' => null,
            'question_type' => 1,
        ]);

        DB::table('deia_responses')->insert([
            'deia_response_id' => $responseId,
            'deia_question_id' => $questionId,
            'user_id' => null,
            'external_id' => null,
            'external_type' => null,
        ]);
    }

    private function deleteFixtureRows(): void
    {
        DB::table('deia_response_settings')
            ->whereIn('deia_response_setting_id', [self::SETTING_ID_ONE, self::SETTING_ID_TWO])
            ->delete();
        DB::table('deia_responses')
            ->whereIn('deia_response_id', [self::RESPONSE_ID_ONE, self::RESPONSE_ID_TWO])
            ->delete();
        DB::table('deia_questions')
            ->whereIn('deia_question_id', [self::QUESTION_ID_ONE, self::QUESTION_ID_TWO])
            ->delete();
    }
}
