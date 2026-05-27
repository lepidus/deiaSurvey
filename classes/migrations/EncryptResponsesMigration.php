<?php

namespace APP\plugins\generic\deiaSurvey\classes\migrations;

use APP\plugins\generic\deiaSurvey\classes\DataEncryption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class EncryptResponsesMigration extends Migration
{
    public function up(): void
    {
        $encrypter = new DataEncryption();

        $result = DB::table('deia_response_settings')->get();

        foreach ($result as $row) {
            $row = get_object_vars($row);
            $settingValue = $row['setting_value'];

            if (
                !in_array($row['setting_name'], ['responseValue', 'optionsInputValue'])
                || $encrypter->textIsEncrypted($settingValue)
                || str_starts_with($settingValue, 'base64:')
            ) {
                continue;
            }

            if ($row['setting_name'] == 'optionsInputValue') {
                $optionsInputValue = unserialize($settingValue);
                if (empty($optionsInputValue)) {
                    continue;
                }
            }

            $encryptedValue = $encrypter->encryptString($settingValue);
            DB::table('deia_response_settings')
                ->where('deia_response_setting_id', $row['deia_response_setting_id'])
                ->update([
                    'setting_value' => $encryptedValue
                ]);
        }
    }
}
