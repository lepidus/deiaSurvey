<?php

namespace APP\plugins\generic\deiaSurvey\classes\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use APP\plugins\generic\deiaSurvey\classes\DataEncryption;

class EncryptResponsesMigration extends Migration
{
    private const CHUNK_SIZE = 1000;

    private DataEncryption $encrypter;

    public function up(): void
    {
        $this->encrypter = new DataEncryption();
        if (!$this->encrypter->secretConfigExists()) {
            return;
        }

        DB::transaction(function () {
            DB::table('deia_response_settings')
                ->whereIn('setting_name', ['responseValue', 'optionsInputValue'])
                ->chunkById(
                    self::CHUNK_SIZE,
                    $this->checkResponsesChunkEncryption(...),
                    'deia_response_setting_id'
                );
        });
    }

    private function checkResponsesChunkEncryption($rows)
    {
        $updates = [];
        foreach ($rows as $row) {
            $row = get_object_vars($row);
            $settingValue = $row['setting_value'];

            if ($this->encrypter->textIsEncrypted($settingValue)) {
                continue;
            }

            if ($row['setting_name'] === 'optionsInputValue') {
                $optionsInputValue = unserialize($settingValue, ['allowed_classes' => false]);
                if (empty($optionsInputValue)) {
                    continue;
                }
            }

            $row['setting_value'] = $this->encrypter->encryptString($settingValue);
            $updates[] = $row;
        }

        if (!empty($updates)) {
            DB::table('deia_response_settings')->upsert(
                $updates,
                ['deia_response_setting_id'],
                ['setting_value']
            );
        }
    }
}
