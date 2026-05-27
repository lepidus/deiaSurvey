<?php

namespace APP\plugins\generic\deiaSurvey\classes\migrations;

use APP\plugins\generic\deiaSurvey\classes\DefaultQuestionsCreator;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class LocalizeQuestionsTextsMigration extends Migration
{
    private const PREVIOUS_STANDARD_QUESTIONS = ['Gender', 'Race', 'Ethnicity'];
    private const BASE_LOCALE = 'en';

    public function up(): void
    {
        $allQuestions = Repo::deiaQuestion()->getCollector()->getMany();
        $defaultQuestionsData = DefaultQuestionsCreator::getDefaultQuestionsData(0);

        foreach ($allQuestions as $question) {
            $isPreviousStandardQuestion = $this->isPreviousStandardQuestion($question);
            $questionName = $isPreviousStandardQuestion
                ? $question->getData('questionText')[self::BASE_LOCALE]
                : __($question->getData('questionText'), [], self::BASE_LOCALE);
            $questionName = strtolower($questionName);
            $defaultQuestionData = $defaultQuestionsData[$questionName];

            if ($isPreviousStandardQuestion) {
                $this->migrateDeiaQuestion($question, $defaultQuestionData);
            }

            foreach ($question->getResponseOptions() as $responseOption) {
                if (!$this->isPreviousStandardResponseOption($responseOption)) {
                    continue;
                }

                $responseOptionText = $responseOption->getData('optionText')[self::BASE_LOCALE];

                foreach ($defaultQuestionData['responseOptions'] as $defaultResponseOption) {
                    $defaultResponseOptionText = __($defaultResponseOption['optionText'], [], self::BASE_LOCALE);
                    if (str_contains($responseOptionText, $defaultResponseOptionText)) {
                        $this->migrateDeiaResponseOption($responseOption, $defaultResponseOption);
                        break;
                    }
                }
            }
        }
    }

    private function migrateDeiaQuestion($question, $defaultQuestionData)
    {
        $this->cleanQuestionTextualData($question);
        Repo::deiaQuestion()->edit($question, [
            'isTranslated' => $defaultQuestionData['isTranslated'],
            'isDefaultQuestion' => $defaultQuestionData['isDefaultQuestion'],
            'questionText' => $defaultQuestionData['questionText'],
            'questionDescription' => $defaultQuestionData['questionDescription']
        ]);
    }

    private function migrateDeiaResponseOption($responseOption, $defaultResponseOption)
    {
        $this->cleanResponseOptionTextualData($responseOption);
        Repo::deiaResponseOption()->edit($responseOption, [
            'optionText' => $defaultResponseOption['optionText'],
            'isTranslated' => $defaultResponseOption['isTranslated']
        ]);
    }

    private function isPreviousStandardQuestion($question): bool
    {
        return is_null($question->getData('isTranslated'))
            && is_null($question->getData('isDefaultQuestion'))
            && is_array($question->getData('questionText'))
            && in_array($question->getData('questionText')[self::BASE_LOCALE], self::PREVIOUS_STANDARD_QUESTIONS);
    }

    private function isPreviousStandardResponseOption($responseOption): bool
    {
        return is_null($responseOption->getData('isTranslated'))
            && is_array($responseOption->getData('optionText'));
    }

    private function cleanQuestionTextualData($question)
    {
        DB::table('deia_question_settings')
            ->where('deia_question_id', $question->getId())
            ->whereIn('setting_name', ['questionText', 'questionDescription'])
            ->delete();
    }

    private function cleanResponseOptionTextualData($responseOption)
    {
        DB::table('deia_response_option_settings')
            ->where('deia_response_option_id', $responseOption->getId())
            ->whereIn('setting_name', ['optionText'])
            ->delete();
    }
}
