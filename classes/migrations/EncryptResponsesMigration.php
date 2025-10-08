<?php

namespace APP\plugins\generic\deiaSurvey\classes\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use APP\plugins\generic\deiaSurvey\classes\DataEncryption;

class EncryptResponsesMigration extends Migration
{
    public function up(): void
    {
        $encrypter = new DataEncryption();
        if (!$encrypter->secretConfigExists()) {
            return;
        }

        $result = DB::table('demographic_response_settings')->get();

        foreach ($result as $row) {
            $row = get_object_vars($row);
            $settingValue = $row['setting_value'];

            if (
                !in_array($row['setting_name'], ['responseValue', 'optionsInputValue'])
                || $encrypter->textIsEncrypted($settingValue)
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
            DB::table('demographic_response_settings')
                ->where('demographic_response_setting_id', $row['demographic_response_setting_id'])
                ->update([
                    'setting_value' => $encryptedValue
                ]);
        }
    }
}
