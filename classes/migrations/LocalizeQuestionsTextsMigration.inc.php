<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\DefaultQuestionsCreator;

class LocalizeQuestionsTextsMigration extends Migration
{
    private const PREVIOUS_STANDARD_QUESTIONS = ['Gender', 'Race', 'Ethnicity'];
    private const BASE_LOCALE = 'en_US';

    public function up(): void
    {
        $allQuestions = Repo::demographicQuestion()->getCollector()->getMany();
        $defaultQuestionsData = DefaultQuestionsCreator::getDefaultQuestionsData(0);

        foreach ($allQuestions as $question) {
            $isPreviousStandardQuestion = $this->isPreviousStandardQuestion($question);
            $questionName = $isPreviousStandardQuestion
                ? $this->getDataInBaseLocale($question, 'questionText')
                : __($question->getData('questionText'), [], self::BASE_LOCALE);
            $questionName = strtolower($questionName);
            $defaultQuestionData = $defaultQuestionsData[$questionName];

            if ($isPreviousStandardQuestion) {
                $this->migrateDemographicQuestion($question, $defaultQuestionData);
            }

            foreach ($question->getResponseOptions() as $responseOption) {
                if (!$this->isPreviousStandardResponseOption($responseOption)) {
                    continue;
                }

                $responseOptionText = $this->getDataInBaseLocale($responseOption, 'optionText');

                foreach ($defaultQuestionData['responseOptions'] as $defaultResponseOption) {
                    $defaultResponseOptionText = __($defaultResponseOption['optionText'], [], self::BASE_LOCALE);
                    if (strpos($responseOptionText, $defaultResponseOptionText) !== false) {
                        $this->migrateDemographicResponseOption($responseOption, $defaultResponseOption);
                        break;
                    }
                }
            }
        }
    }

    private function getDataInBaseLocale($dataObject, $dataName)
    {
        $dataValue = $dataObject->getData($dataName);
        return $dataValue[self::BASE_LOCALE] ?? $dataValue['en'];
    }

    private function migrateDemographicQuestion($question, $defaultQuestionData)
    {
        $this->cleanQuestionTextualData($question);
        Repo::demographicQuestion()->edit($question, [
            'isTranslated' => $defaultQuestionData['isTranslated'],
            'isDefaultQuestion' => $defaultQuestionData['isDefaultQuestion'],
            'questionText' => $defaultQuestionData['questionText'],
            'questionDescription' => $defaultQuestionData['questionDescription']
        ]);
    }

    private function migrateDemographicResponseOption($responseOption, $defaultResponseOption)
    {
        $this->cleanResponseOptionTextualData($responseOption);
        Repo::demographicResponseOption()->edit($responseOption, [
            'optionText' => $defaultResponseOption['optionText'],
            'isTranslated' => $defaultResponseOption['isTranslated']
        ]);
    }

    private function isPreviousStandardQuestion($question): bool
    {
        return is_null($question->getData('isTranslated'))
            && is_null($question->getData('isDefaultQuestion'))
            && is_array($question->getData('questionText'))
            && in_array($this->getDataInBaseLocale($question, 'questionText'), self::PREVIOUS_STANDARD_QUESTIONS);
    }

    private function isPreviousStandardResponseOption($responseOption): bool
    {
        return is_null($responseOption->getData('isTranslated'))
            && is_array($responseOption->getData('optionText'));
    }

    private function cleanQuestionTextualData($question)
    {
        Capsule::table('demographic_question_settings')
            ->where('demographic_question_id', $question->getId())
            ->whereIn('setting_name', ['questionText', 'questionDescription'])
            ->delete();
    }

    private function cleanResponseOptionTextualData($responseOption)
    {
        Capsule::table('demographic_response_option_settings')
            ->where('demographic_response_option_id', $responseOption->getId())
            ->whereIn('setting_name', ['optionText'])
            ->delete();
    }
}
