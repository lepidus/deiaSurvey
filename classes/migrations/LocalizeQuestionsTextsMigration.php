<?php

namespace APP\plugins\generic\deiaSurvey\classes\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;

class LocalizeQuestionsTextsMigration extends Migration
{
    private const PREVIOUS_STANDARD_QUESTIONS = ['Gender', 'Race', 'Ethnicity'];

    public function up(): void
    {
        $allQuestions = Repo::demographicQuestion()->getCollector()->getMany();

        foreach ($allQuestions as $question) {
            if ($this->isPreviousStandardQuestion($question)) {
                $questionName = strtolower($question->getData('questionText')['en']);

                $this->cleanQuestionTextualData($question);
                Repo::demographicQuestion()->edit($question, [
                    'isTranslated' => true,
                    'isDefaultQuestion' => true,
                    'questionText' => "plugins.generic.deiaSurvey.defaultQuestion.$questionName.title",
                    'questionDescription' => "plugins.generic.deiaSurvey.defaultQuestion.$questionName.description"
                ]);
            }
        }
    }

    private function isPreviousStandardQuestion($question): bool
    {
        return is_null($question->getData('isTranslated'))
            && is_null($question->getData('isDefaultQuestion'))
            && is_array($question->getData('questionText'))
            && in_array($question->getData('questionText')['en'], self::PREVIOUS_STANDARD_QUESTIONS);
    }

    private function cleanQuestionTextualData($question)
    {
        DB::table('demographic_question_settings')
            ->where('demographic_question_id', $question->getId())
            ->whereIn('setting_name', ['questionText', 'questionDescription'])
            ->delete();
    }
}
