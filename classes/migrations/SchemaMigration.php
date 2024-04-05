<?php

namespace APP\plugins\generic\demographicData\classes\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SchemaMigration extends Migration
{
    public function up(): void
    {
        Schema::create('demographic_questions', function (Blueprint $table) {
            $table->bigInteger('demographic_question_id')->autoIncrement();
            $table->bigInteger('context_id');

            $table->foreign('context_id')
                ->references('journal_id')
                ->on('journals')
                ->onDelete('cascade');
            $table->index(['context_id'], 'demographic_questions_context_id');
        });

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

        Schema::create('demographic_question_responses', function (Blueprint $table) {
            $table->bigIncrements('demographic_question_response_id');
            $table->bigInteger('demographic_question_id');
            $table->text('response_value')->nullable();

            $table->foreign('demographic_question_id')
                ->references('demographic_question_id')
                ->on('demographic_questions')
                ->onDelete('cascade');
            $table->index(['demographic_question_id'], 'demographic_question_responses_demographic_question_id');
        });
    }
}
