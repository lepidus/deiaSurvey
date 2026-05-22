<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class RenameDemographicToDeiaMigration extends Migration
{
    public function up(): void
    {
        $this->renameTables();
        $this->renameColumns();
        $this->renameSettings();
        $this->addQuestionBlocks();
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
            if (Capsule::schema()->hasTable($oldTable) && !Capsule::schema()->hasTable($newTable)) {
                Capsule::schema()->rename($oldTable, $newTable);
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
            if (!Capsule::schema()->hasTable($tableName)) {
                continue;
            }

            Capsule::schema()->table($tableName, function (Blueprint $table) use ($columns, $tableName) {
                foreach ($columns as $oldColumn => $newColumn) {
                    if (Capsule::schema()->hasColumn($tableName, $oldColumn)
                        && !Capsule::schema()->hasColumn($tableName, $newColumn)
                    ) {
                        $table->renameColumn($oldColumn, $newColumn);
                    }
                }
            });
        }
    }

    private function addQuestionBlocks(): void
    {
        if (!Capsule::schema()->hasTable('deia_question_blocks')) {
            Capsule::schema()->create('deia_question_blocks', function (Blueprint $table) {
                $table->bigInteger('deia_question_block_id')->autoIncrement();
                $table->bigInteger('context_id');
                $table->float('seq', 8, 2)->nullable();
                $table->smallInteger('is_active')->nullable();
                $table->index(['context_id'], 'deia_question_blocks_context_id');
            });
        }

        if (!Capsule::schema()->hasTable('deia_question_block_settings')) {
            Capsule::schema()->create('deia_question_block_settings', function (Blueprint $table) {
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

        if (!Capsule::schema()->hasColumn('deia_questions', 'deia_question_block_id')) {
            Capsule::schema()->table('deia_questions', function (Blueprint $table) {
                $table->bigInteger('deia_question_block_id')->nullable()->after('context_id');
            });
        }

        if (!Capsule::schema()->hasColumn('deia_questions', 'seq')) {
            Capsule::schema()->table('deia_questions', function (Blueprint $table) {
                $table->float('seq', 8, 2)->nullable()->after('deia_question_block_id');
            });
        }

        if (!Capsule::schema()->hasColumn('deia_response_options', 'seq')) {
            Capsule::schema()->table('deia_response_options', function (Blueprint $table) {
                $table->float('seq', 8, 2)->nullable()->after('deia_question_id');
            });
        }

        $contextIds = Capsule::table('deia_questions')
            ->select('context_id')
            ->distinct()
            ->pluck('context_id');

        foreach ($contextIds as $contextId) {
            $questionBlockId = Capsule::table('deia_question_blocks')
                ->where('context_id', '=', $contextId)
                ->value('deia_question_block_id');

            if (!$questionBlockId) {
                Capsule::table('deia_question_blocks')->insert([
                    'context_id' => $contextId,
                    'seq' => 1,
                    'is_active' => 1,
                ]);
                $questionBlockId = (int) Capsule::getPdo()->lastInsertId();

                $this->insertDefaultQuestionBlockSettings($questionBlockId);
            }

            $questions = Capsule::table('deia_questions')
                ->where('context_id', '=', $contextId)
                ->orderBy('deia_question_id', 'asc')
                ->pluck('deia_question_id');

            $sequence = 0;
            foreach ($questions as $questionId) {
                Capsule::table('deia_questions')
                    ->where('deia_question_id', '=', $questionId)
                    ->update([
                        'deia_question_block_id' => $questionBlockId,
                        'seq' => ++$sequence,
                    ]);
            }
        }

        $questionIds = Capsule::table('deia_response_options')
            ->select('deia_question_id')
            ->distinct()
            ->pluck('deia_question_id');

        foreach ($questionIds as $questionId) {
            $responseOptionIds = Capsule::table('deia_response_options')
                ->where('deia_question_id', '=', $questionId)
                ->orderBy('deia_response_option_id', 'asc')
                ->pluck('deia_response_option_id');

            $sequence = 0;
            foreach ($responseOptionIds as $responseOptionId) {
                Capsule::table('deia_response_options')
                    ->where('deia_response_option_id', '=', $responseOptionId)
                    ->update(['seq' => ++$sequence]);
            }
        }
    }

    private function insertDefaultQuestionBlockSettings(int $questionBlockId): void
    {
        $defaultTitles = [
            'en_US' => 'SciELO Questions',
            'pt_BR' => 'Perguntas SciELO',
            'es_ES' => 'Preguntas SciELO',
        ];
        $defaultDescriptions = [
            'en_US' => 'Standard SciELO questions for collecting demographic and identity data.',
            'pt_BR' => 'Perguntas padrão SciELO para coletar dados demográficos e identitários.',
            'es_ES' => 'Preguntas estándar SciELO para recopilar datos demográficos e identitarios.',
        ];

        foreach ($defaultTitles as $locale => $title) {
            Capsule::table('deia_question_block_settings')->insert([
                'deia_question_block_id' => $questionBlockId,
                'locale' => $locale,
                'setting_name' => 'title',
                'setting_value' => $title,
            ]);
            Capsule::table('deia_question_block_settings')->insert([
                'deia_question_block_id' => $questionBlockId,
                'locale' => $locale,
                'setting_name' => 'description',
                'setting_value' => $defaultDescriptions[$locale],
            ]);
        }
    }

    private function renameSettings(): void
    {
        if (Capsule::schema()->hasTable('user_settings')) {
            Capsule::table('user_settings')
                ->where('setting_name', '=', 'demographicDataConsent')
                ->update(['setting_name' => 'deiaDataConsent']);
        }

        if (Capsule::schema()->hasTable('author_settings')) {
            Capsule::table('author_settings')
                ->where('setting_name', '=', 'demographicToken')
                ->update(['setting_name' => 'deiaToken']);
            Capsule::table('author_settings')
                ->where('setting_name', '=', 'demographicOrcid')
                ->update(['setting_name' => 'deiaOrcid']);
        }
    }
}
