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
        DB::transaction(function () {
            $this->renameTables();
            $this->renameColumns();
            $this->renameSettings();
            $this->addQuestionBlocks();
        });
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
            'demographic_forms' => 'deia_question_blocks',
            'demographic_form_settings' => 'deia_question_block_settings',
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
            'deia_question_blocks' => [
                'demographic_form_id' => 'deia_question_block_id',
            ],
            'deia_question_block_settings' => [
                'demographic_form_setting_id' => 'deia_question_block_setting_id',
                'demographic_form_id' => 'deia_question_block_id',
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

    private function addQuestionBlocks(): void
    {
        if (!Schema::hasTable('deia_question_blocks')) {
            Schema::create('deia_question_blocks', function (Blueprint $table) {
                $table->bigInteger('deia_question_block_id')->autoIncrement();
                $table->bigInteger('context_id');
                $table->float('seq', 8, 2)->nullable();
                $table->smallInteger('is_active')->nullable();
                $table->index(['context_id'], 'deia_question_blocks_context_id');
            });
        }

        if (!Schema::hasTable('deia_question_block_settings')) {
            Schema::create('deia_question_block_settings', function (Blueprint $table) {
                $table->bigIncrements('deia_question_block_setting_id');
                $table->bigInteger('deia_question_block_id');
                $table->string('locale', 14)->default('');
                $table->string('setting_name', 255);
                $table->longText('setting_value')->nullable();
                $table->index(['deia_question_block_id'], 'deia_question_block_settings_id');
                $table->unique(
                    ['deia_question_block_id', 'locale', 'setting_name'],
                    'deia_question_block_settings_pkey'
                );
            });
        }

        if (Schema::hasTable('deia_questions') && !Schema::hasColumn('deia_questions', 'deia_question_block_id')) {
            Schema::table('deia_questions', function (Blueprint $table) {
                $table->bigInteger('deia_question_block_id')->nullable()->after('context_id');
            });
        }

        if (Schema::hasTable('deia_questions') && !Schema::hasColumn('deia_questions', 'seq')) {
            Schema::table('deia_questions', function (Blueprint $table) {
                $table->float('seq', 8, 2)->nullable()->after('deia_question_block_id');
            });
        }

        if (Schema::hasTable('deia_response_options') && !Schema::hasColumn('deia_response_options', 'seq')) {
            Schema::table('deia_response_options', function (Blueprint $table) {
                $table->float('seq', 8, 2)->nullable()->after('deia_question_id');
            });
        }

        if (!Schema::hasTable('deia_questions')) {
            return;
        }

        $contextIds = DB::table('deia_questions')
            ->select('context_id')
            ->distinct()
            ->pluck('context_id');

        $updates = [];
        foreach ($contextIds as $contextId) {
            $questionBlockId = DB::table('deia_question_blocks')
                ->where('context_id', '=', $contextId)
                ->value('deia_question_block_id');

            if (!$questionBlockId) {
                $questionBlockId = DB::table('deia_question_blocks')->insertGetId([
                    'context_id' => $contextId,
                    'seq' => 1,
                    'is_active' => 1,
                ]);

                $this->insertDefaultQuestionBlockSettings($questionBlockId);
            }

            $questions = DB::table('deia_questions')
                ->where('context_id', '=', $contextId)
                ->orderBy('deia_question_id', 'asc')
                ->pluck('deia_question_id');

            $sequence = 0;
            foreach ($questions as $questionId) {
                $updates[] = [
                    'deia_question_id' => $questionId,
                    'deia_question_block_id' => $questionBlockId,
                    'seq' => ++$sequence,
                ];
            }
        }
        if (!empty($updates)) {
            DB::table('deia_questions')->upsert(
                $updates,
                ['deia_question_id'],
                ['deia_question_block_id', 'seq']
            );
        }

        if (!Schema::hasTable('deia_response_options')) {
            return;
        }

        $questionIds = DB::table('deia_response_options')
            ->select('deia_question_id')
            ->distinct()
            ->pluck('deia_question_id');

        $updates = [];
        foreach ($questionIds as $questionId) {
            $responseOptionIds = DB::table('deia_response_options')
                ->where('deia_question_id', '=', $questionId)
                ->orderBy('deia_response_option_id', 'asc')
                ->pluck('deia_response_option_id');

            $sequence = 0;
            foreach ($responseOptionIds as $responseOptionId) {
                $updates[] = [
                    'deia_response_option_id' => $responseOptionId,
                    'seq' => ++$sequence
                ];
            }
        }
        if (!empty($updates)) {
            DB::table('deia_response_options')->upsert(
                $updates,
                ['deia_response_option_id'],
                ['seq']
            );
        }
    }

    private function insertDefaultQuestionBlockSettings(int $questionBlockId): void
    {
        $defaultTitles = [
            'en' => 'SciELO Questions',
            'pt_BR' => 'Perguntas SciELO',
            'es' => 'Preguntas SciELO',
        ];
        $defaultDescriptions = [
            'en' => 'Standard SciELO questions for collecting demographic and identity data.',
            'pt_BR' => 'Perguntas padrão SciELO para coletar dados demográficos e identitários.',
            'es' => 'Preguntas estándar SciELO para recopilar datos demográficos e identitarios.',
        ];

        foreach ($defaultTitles as $locale => $title) {
            DB::table('deia_question_block_settings')->insert([
                'deia_question_block_id' => $questionBlockId,
                'locale' => $locale,
                'setting_name' => 'title',
                'setting_value' => $title,
            ]);
            DB::table('deia_question_block_settings')->insert([
                'deia_question_block_id' => $questionBlockId,
                'locale' => $locale,
                'setting_name' => 'description',
                'setting_value' => $defaultDescriptions[$locale],
            ]);
        }
    }
}
