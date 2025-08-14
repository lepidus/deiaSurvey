<?php

namespace APP\plugins\generic\deiaSurvey\classes\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\DefaultQuestionsCreator;

class LocalizeQuestionsTextsMigration extends Migration
{
    private const PREVIOUS_STANDARD_QUESTIONS = ['Gender', 'Race', 'Ethnicity'];
    private const BASE_LOCALE = 'en_US';

    public function up(): void
    {
        $allQuestions = Repo::demographicQuestion()->getCollector()->getMany();
        $defaultQuestionsData = (new DefaultQuestionsCreator())->getDefaultQuestionsData(0);

        foreach ($allQuestions as $question) {
            if (!$this->isPreviousStandardQuestion($question)) {
                continue;
            }

            $questionName = strtolower($question->getData('questionText')[self::BASE_LOCALE]);
            $defaultQuestionData = $defaultQuestionsData[$questionName];

            $this->cleanQuestionTextualData($question);
            Repo::demographicQuestion()->edit($question, [
                'isTranslated' => $defaultQuestionData['isTranslated'],
                'isDefaultQuestion' => $defaultQuestionData['isDefaultQuestion'],
                'questionText' => $defaultQuestionData['questionText'],
                'questionDescription' => $defaultQuestionData['questionDescription']
            ]);

            foreach ($question->getResponseOptions() as $responseOption) {
                $responseOptionText = $responseOption->getData('optionText')[self::BASE_LOCALE];

                foreach ($defaultQuestionData['responseOptions'] as $defaultResponseOption) {
                    $defaultResponseOptionText = __($defaultResponseOption['optionText'], [], self::BASE_LOCALE);
                    if (str_contains($responseOptionText, $defaultResponseOptionText)) {
                        $this->cleanResponseOptionTextualData($responseOption);

                        Repo::demographicResponseOption()->edit($responseOption, [
                            'optionText' => $defaultResponseOption['optionText'],
                            'isTranslated' => $defaultResponseOption['isTranslated']
                        ]);
                        break;
                    }
                }
            }
        }
    }

    private function isPreviousStandardQuestion($question): bool
    {
        return is_null($question->getData('isTranslated'))
            && is_null($question->getData('isDefaultQuestion'))
            && is_array($question->getData('questionText'))
            && in_array($question->getData('questionText')[self::BASE_LOCALE], self::PREVIOUS_STANDARD_QUESTIONS);
    }

    private function cleanQuestionTextualData($question)
    {
        DB::table('demographic_question_settings')
            ->where('demographic_question_id', $question->getId())
            ->whereIn('setting_name', ['questionText', 'questionDescription'])
            ->delete();
    }

    private function cleanResponseOptionTextualData($responseOption)
    {
        DB::table('demographic_response_option_settings')
            ->where('demographic_response_option_id', $responseOption->getId())
            ->whereIn('setting_name', ['optionText'])
            ->delete();
    }
}
