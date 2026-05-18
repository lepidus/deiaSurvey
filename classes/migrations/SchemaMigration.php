<?php

namespace APP\plugins\generic\deiaSurvey\classes\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use APP\core\Application;

class SchemaMigration extends Migration
{
    public function up(): void
    {
        $applicationName = Application::get()->getName();

        if (!Schema::hasTable('deia_questions')) {
            Schema::create('deia_questions', function (Blueprint $table) use ($applicationName) {
                $table->bigInteger('deia_question_id')->autoIncrement();
                $table->bigInteger('context_id');
                $table->bigInteger('question_type');

                if ($applicationName == 'ojs2') {
                    $table->foreign('context_id')
                        ->references('journal_id')
                        ->on('journals')
                        ->onDelete('cascade');
                } elseif ($applicationName == 'ops') {
                    $table->foreign('context_id')
                        ->references('server_id')
                        ->on('servers')
                        ->onDelete('cascade');
                }

                $table->index(['context_id'], 'deia_questions_context_id');
            });
        }

        if (!Schema::hasTable('deia_question_settings')) {
            Schema::create('deia_question_settings', function (Blueprint $table) {
                $table->bigIncrements('deia_question_setting_id');
                $table->bigInteger('deia_question_id');
                $table->string('locale', 14)->default('');
                $table->string('setting_name', 255);
                $table->longText('setting_value')->nullable();

                $table->foreign('deia_question_id')
                    ->references('deia_question_id')
                    ->on('deia_questions')
                    ->onDelete('cascade');
                $table->index(['deia_question_id'], 'deia_question_settings_id');
                $table->unique(['deia_question_id', 'locale', 'setting_name'], 'deia_question_settings_pkey');
            });
        }

        if (!Schema::hasTable('deia_response_options')) {
            Schema::create('deia_response_options', function (Blueprint $table) {
                $table->bigInteger('deia_response_option_id')->autoIncrement();
                $table->bigInteger('deia_question_id');

                $table->foreign('deia_question_id')
                    ->references('deia_question_id')
                    ->on('deia_questions')
                    ->onDelete('cascade');
                $table->index(['deia_question_id'], 'deia_response_options_deia_question_id');
            });
        }

        if (!Schema::hasTable('deia_response_option_settings')) {
            Schema::create('deia_response_option_settings', function (Blueprint $table) {
                $table->bigIncrements('deia_response_option_setting_id');
                $table->bigInteger('deia_response_option_id');
                $table->string('locale', 14)->default('');
                $table->string('setting_name', 255);
                $table->longText('setting_value')->nullable();

                $table->foreign('deia_response_option_id', 'deia_response_option_settings_option_id')
                    ->references('deia_response_option_id')
                    ->on('deia_response_options')
                    ->onDelete('cascade');
                $table->index(['deia_response_option_id'], 'deia_response_option_settings_option_id');
                $table->unique(['deia_response_option_id', 'locale', 'setting_name'], 'deia_response_option_settings_pkey');
            });
        }

        if (!Schema::hasTable('deia_responses')) {
            Schema::create('deia_responses', function (Blueprint $table) {
                $table->bigInteger('deia_response_id')->autoIncrement();
                $table->bigInteger('deia_question_id');
                $table->bigInteger('user_id')->nullable();
                $table->string('external_id', 255)->nullable();
                $table->string('external_type', 10)->nullable();

                $table->foreign('deia_question_id')
                    ->references('deia_question_id')
                    ->on('deia_questions')
                    ->onDelete('cascade');
                $table->index(['deia_question_id'], 'deia_responses_deia_question_id');

                $table->foreign('user_id')
                    ->references('user_id')
                    ->on('users')
                    ->onDelete('cascade');
                $table->index(['user_id'], 'deia_responses_user_id');
            });
        }

        if (!Schema::hasTable('deia_response_settings')) {
            Schema::create('deia_response_settings', function (Blueprint $table) {
                $table->bigIncrements('deia_response_setting_id');
                $table->bigInteger('deia_response_id');
                $table->string('locale', 14)->default('');
                $table->string('setting_name', 255);
                $table->longText('setting_value')->nullable();

                $table->foreign('deia_response_id')
                    ->references('deia_response_id')
                    ->on('deia_responses')
                    ->onDelete('cascade');
                $table->index(['deia_response_id'], 'deia_response_setting_id');
                $table->unique(['deia_response_id', 'locale', 'setting_name'], 'deia_response_settings_pkey');
            });
        }
    }
}
