<?php

namespace APP\plugins\generic\deiaSurvey\classes\migrations;

use Exception;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PKP\config\Config;

class ReencryptResponsesWithAppKeyMigration extends Migration
{
    private const LEGACY_ENCRYPTION_CIPHER = 'aes-256-cbc';
    private const LEGACY_BASE64_PREFIX = 'base64:';
    private const LEGACY_SECRET_SETTING = 'api_key_' . 'secret';

    public function up(): void
    {
        DB::table('deia_response_settings')
            ->whereIn('setting_name', ['responseValue', 'optionsInputValue'])
            ->get(['deia_response_setting_id', 'setting_value'])
            ->each(function ($row) {
                if (empty($row->setting_value)) {
                    return;
                }

                try {
                    $decryptedValue = $this->decryptLegacyString($row->setting_value);
                } catch (Exception $e) {
                    return;
                }

                DB::table('deia_response_settings')
                    ->where('deia_response_setting_id', $row->deia_response_setting_id)
                    ->update(['setting_value' => Crypt::encryptString($decryptedValue)]);
            });
    }

    private function decryptLegacyString(string $encryptedText): string
    {
        if (!str_starts_with($encryptedText, self::LEGACY_BASE64_PREFIX)) {
            throw new Exception('DEIA Survey - Response is not encrypted with the legacy format');
        }

        $encrypter = new Encrypter($this->getLegacySecretFromConfig(), self::LEGACY_ENCRYPTION_CIPHER);
        $encryptedText = str_replace(self::LEGACY_BASE64_PREFIX, '', $encryptedText);
        $payload = base64_decode($encryptedText);

        return $encrypter->decrypt($payload);
    }

    private function getLegacySecretFromConfig(): string
    {
        $secret = Config::getVar('security', self::LEGACY_SECRET_SETTING);
        if ($secret === '') {
            throw new Exception('DEIA Survey - The legacy encryption secret is not configured');
        }

        return hash('sha256', $secret, true);
    }
}
