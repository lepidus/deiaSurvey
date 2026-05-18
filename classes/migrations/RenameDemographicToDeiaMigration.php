<?php

namespace APP\plugins\generic\deiaSurvey\classes\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RenameDemographicToDeiaMigration extends Migration
{
    public function up(): void
    {
        $this->renameTables();
        $this->renameColumns();
        $this->renameSettings();
    }

    private function renameTables(): void
    {
        $renames = [
            'demographic_questions' => 'deia_questions',
            'demographic_question_settings' => 'deia_question_settings',
            'demographic_response_options' => 'deia_response_options',
            'demographic_response_option_settings' => 'deia_response_option_settings',
            'demographic_responses' => 'deia_responses',
            'demographic_response_settings' => 'deia_response_settings',
        ];

        foreach ($renames as $oldTable => $newTable) {
            if (Schema::hasTable($oldTable) && !Schema::hasTable($newTable)) {
                Schema::rename($oldTable, $newTable);
            }
        }
    }

    private function renameColumns(): void
    {
        $renames = [
            'deia_questions' => [
                'demographic_question_id' => 'deia_question_id',
            ],
            'deia_question_settings' => [
                'demographic_question_setting_id' => 'deia_question_setting_id',
                'demographic_question_id' => 'deia_question_id',
            ],
            'deia_response_options' => [
                'demographic_response_option_id' => 'deia_response_option_id',
                'demographic_question_id' => 'deia_question_id',
            ],
            'deia_response_option_settings' => [
                'demographic_response_option_setting_id' => 'deia_response_option_setting_id',
                'demographic_response_option_id' => 'deia_response_option_id',
            ],
            'deia_responses' => [
                'demographic_response_id' => 'deia_response_id',
                'demographic_question_id' => 'deia_question_id',
            ],
            'deia_response_settings' => [
                'demographic_response_setting_id' => 'deia_response_setting_id',
                'demographic_response_id' => 'deia_response_id',
            ],
        ];

        foreach ($renames as $tableName => $columns) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columns, $tableName) {
                foreach ($columns as $oldColumn => $newColumn) {
                    if (Schema::hasColumn($tableName, $oldColumn) && !Schema::hasColumn($tableName, $newColumn)) {
                        $table->renameColumn($oldColumn, $newColumn);
                    }
                }
            });
        }
    }

    private function renameSettings(): void
    {
        if (Schema::hasTable('user_settings')) {
            DB::table('user_settings')
                ->where('setting_name', '=', 'demographicDataConsent')
                ->update(['setting_name' => 'deiaDataConsent']);
        }

        if (Schema::hasTable('author_settings')) {
            DB::table('author_settings')
                ->where('setting_name', '=', 'demographicToken')
                ->update(['setting_name' => 'deiaToken']);
            DB::table('author_settings')
                ->where('setting_name', '=', 'demographicOrcid')
                ->update(['setting_name' => 'deiaOrcid']);
        }
    }
}
