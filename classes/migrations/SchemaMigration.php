<?php

namespace APP\plugins\generic\demographicData\classes\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PKP\plugins\PluginRegistry;
use PKP\plugins\Hook;
use PKP\db\DAORegistry;
use APP\plugins\generic\demographicData\classes\DefaultQuestionsCreator;

class SchemaMigration extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('demographic_questions')) {
            Schema::create('demographic_questions', function (Blueprint $table) {
                $table->bigInteger('demographic_question_id')->autoIncrement();
                $table->bigInteger('context_id');
                $table->bigInteger('question_type');

                $table->foreign('context_id')
                    ->references('journal_id')
                    ->on('journals')
                    ->onDelete('cascade');
                $table->index(['context_id'], 'demographic_questions_context_id');
            });
        }

        if (!Schema::hasTable('demographic_question_settings')) {
            Schema::create('demographic_question_settings', function (Blueprint $table) {
                $table->bigIncrements('demographic_question_setting_id');
                $table->bigInteger('demographic_question_id');
                $table->string('locale', 14)->default('');
                $table->string('setting_name', 255);
                $table->longText('setting_value')->nullable();

                $table->foreign('demographic_question_id')
                    ->references('demographic_question_id')
                    ->on('demographic_questions')
                    ->onDelete('cascade');
                $table->index(['demographic_question_id'], 'demographic_question_settings_id');
                $table->unique(['demographic_question_id', 'locale', 'setting_name'], 'demographic_question_settings_pkey');
            });
        }

        if (!Schema::hasTable('demographic_response_options')) {
            Schema::create('demographic_response_options', function (Blueprint $table) {
                $table->bigInteger('demographic_response_option_id')->autoIncrement();
                $table->bigInteger('demographic_question_id');

                $table->foreign('demographic_question_id')
                    ->references('demographic_question_id')
                    ->on('demographic_questions')
                    ->onDelete('cascade');
                $table->index(['demographic_question_id'], 'demographic_response_options_demographic_question_id');
            });
        }

        if (!Schema::hasTable('demographic_response_option_settings')) {
            Schema::create('demographic_response_option_settings', function (Blueprint $table) {
                $table->bigIncrements('demographic_response_option_setting_id');
                $table->bigInteger('demographic_response_option_id');
                $table->string('locale', 14)->default('');
                $table->string('setting_name', 255);
                $table->longText('setting_value')->nullable();

                $table->foreign('demographic_response_option_id', 'demographic_response_option_settings_option_id')
                    ->references('demographic_response_option_id')
                    ->on('demographic_response_options')
                    ->onDelete('cascade');
                $table->index(['demographic_response_option_id'], 'demographic_response_option_settings_option_id');
                $table->unique(['demographic_response_option_id', 'locale', 'setting_name'], 'demographic_response_option_settings_pkey');
            });
        }

        if (!Schema::hasTable('demographic_responses')) {
            Schema::create('demographic_responses', function (Blueprint $table) {
                $table->bigInteger('demographic_response_id')->autoIncrement();
                $table->bigInteger('demographic_question_id');
                $table->bigInteger('user_id')->nullable();
                $table->string('external_id', 255)->nullable();
                $table->string('external_type', 10)->nullable();

                $table->foreign('demographic_question_id')
                    ->references('demographic_question_id')
                    ->on('demographic_questions')
                    ->onDelete('cascade');
                $table->index(['demographic_question_id'], 'demographic_responses_demographic_question_id');

                $table->foreign('user_id')
                    ->references('user_id')
                    ->on('users')
                    ->onDelete('cascade');
                $table->index(['user_id'], 'demographic_responses_user_id');
            });
        }

        if (!Schema::hasTable('demographic_response_settings')) {
            Schema::create('demographic_response_settings', function (Blueprint $table) {
                $table->bigIncrements('demographic_response_setting_id');
                $table->bigInteger('demographic_response_id');
                $table->string('locale', 14)->default('');
                $table->string('setting_name', 255);
                $table->longText('setting_value')->nullable();

                $table->foreign('demographic_response_id')
                    ->references('demographic_response_id')
                    ->on('demographic_responses')
                    ->onDelete('cascade');
                $table->index(['demographic_response_id'], 'demographic_response_setting_id');
                $table->unique(['demographic_response_id', 'locale', 'setting_name'], 'demographic_response_settings_pkey');
            });
        }

        $this->registerHooksForCustomSchemas();
        $this->addDefaultQuestionsToContexts();
    }

    private function registerHooksForCustomSchemas()
    {
        $plugin = PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        Hook::add('Schema::get::demographicQuestion', [$plugin, 'addCustomSchema']);
        Hook::add('Schema::get::demographicResponse', [$plugin, 'addCustomSchema']);
        Hook::add('Schema::get::demographicResponseOption', [$plugin, 'addCustomSchema']);
    }

    private function addDefaultQuestionsToContexts()
    {
        $journalDao = DAORegistry::getDAO('JournalDAO');
        $defaultQuestionsCreator = new DefaultQuestionsCreator();

        $contexts = $journalDao->getAll(true);
        while ($context = $contexts->next()) {
            $defaultQuestionsCreator->createDefaultQuestions($context->getId());
        }
    }
}
