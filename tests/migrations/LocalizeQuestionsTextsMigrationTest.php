<?php

namespace APP\plugins\generic\deiaSurvey\tests\migrations;

use APP\plugins\generic\deiaSurvey\classes\migrations\LocalizeQuestionsTextsMigration;
use APP\plugins\generic\deiaSurvey\tests\helpers\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use Illuminate\Support\Facades\DB;

class LocalizeQuestionsTextsMigrationTest extends DatabaseTestCase
{
    use TestHelperTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->isolateDeiaTables();
    }

    public function testLocalizesQuestionsWithoutSchemaHooks(): void
    {
        $this->initializePluginLocaleData();
        $questionId = $this->createPreviousStandardQuestion();
        $responseOptionId = $this->createPreviousStandardResponseOption($questionId);

        (new LocalizeQuestionsTextsMigration())->up();

        self::assertSame(
            'plugins.generic.deiaSurvey.defaultQuestion.gender.title',
            $this->getQuestionSetting($questionId, 'questionText')
        );
        self::assertSame(
            'plugins.generic.deiaSurvey.defaultQuestion.gender.description',
            $this->getQuestionSetting($questionId, 'questionDescription')
        );
        self::assertEquals(0, $this->getQuestionSetting($questionId, 'isTranslated'));
        self::assertEquals(1, $this->getQuestionSetting($questionId, 'isDefaultQuestion'));
        self::assertSame(
            'plugins.generic.deiaSurvey.defaultQuestion.gender.responseOption.woman',
            $this->getResponseOptionSetting($responseOptionId, 'optionText')
        );
        self::assertEquals(0, $this->getResponseOptionSetting($responseOptionId, 'isTranslated'));
        self::assertFalse($this->hasLocalizedQuestionTextualSettings($questionId));
        self::assertFalse($this->hasLocalizedResponseOptionTextualSettings($responseOptionId));
    }

    private function createPreviousStandardQuestion(): int
    {
        $questionId = DB::table('deia_questions')->insertGetId([
            'context_id' => $this->createTestContext(),
            'deia_question_block_id' => null,
            'seq' => 1,
            'question_type' => 1,
        ], 'deia_question_id');

        DB::table('deia_question_settings')->insert([
            [
                'deia_question_id' => $questionId,
                'locale' => 'en',
                'setting_name' => 'questionText',
                'setting_value' => 'Gender',
            ],
            [
                'deia_question_id' => $questionId,
                'locale' => 'en',
                'setting_name' => 'questionDescription',
                'setting_value' => 'Legacy gender question description',
            ],
        ]);

        return $questionId;
    }

    private function createPreviousStandardResponseOption(int $questionId): int
    {
        $responseOptionId = DB::table('deia_response_options')->insertGetId([
            'deia_question_id' => $questionId,
            'seq' => 1,
        ], 'deia_response_option_id');

        DB::table('deia_response_option_settings')->insert([
            'deia_response_option_id' => $responseOptionId,
            'locale' => 'en',
            'setting_name' => 'optionText',
            'setting_value' => 'Woman',
        ]);

        return $responseOptionId;
    }

    private function getQuestionSetting(int $questionId, string $settingName)
    {
        return DB::table('deia_question_settings')
            ->where('deia_question_id', $questionId)
            ->where('locale', '')
            ->where('setting_name', $settingName)
            ->value('setting_value');
    }

    private function getResponseOptionSetting(int $responseOptionId, string $settingName)
    {
        return DB::table('deia_response_option_settings')
            ->where('deia_response_option_id', $responseOptionId)
            ->where('locale', '')
            ->where('setting_name', $settingName)
            ->value('setting_value');
    }

    private function hasLocalizedQuestionTextualSettings(int $questionId): bool
    {
        return DB::table('deia_question_settings')
            ->where('deia_question_id', $questionId)
            ->where('locale', '<>', '')
            ->whereIn('setting_name', ['questionText', 'questionDescription'])
            ->exists();
    }

    private function hasLocalizedResponseOptionTextualSettings(int $responseOptionId): bool
    {
        return DB::table('deia_response_option_settings')
            ->where('deia_response_option_id', $responseOptionId)
            ->where('locale', '<>', '')
            ->whereIn('setting_name', ['optionText'])
            ->exists();
    }
}
