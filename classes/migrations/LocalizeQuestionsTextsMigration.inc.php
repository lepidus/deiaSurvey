<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use APP\plugins\generic\deiaSurvey\classes\DefaultQuestionsCreator;

class LocalizeQuestionsTextsMigration extends Migration
{
    private const PREVIOUS_STANDARD_QUESTIONS = ['Gender', 'Race', 'Ethnicity'];
    private const BASE_LOCALE = 'en_US';

    public function up(): void
    {
        $allQuestions = Capsule::table('deia_questions')->get();
        $defaultQuestionsData = DefaultQuestionsCreator::getDefaultQuestionsData(0);

        foreach ($allQuestions as $question) {
            $questionSettings = $this->getQuestionSettings($question->deia_question_id);
            $isPreviousStandardQuestion = $this->isPreviousStandardQuestion($questionSettings);
            $questionName = $this->getQuestionName($questionSettings, $isPreviousStandardQuestion);

            if (is_null($questionName)) {
                continue;
            }

            if (empty($defaultQuestionsData[$questionName])) {
                continue;
            }

            $defaultQuestionData = $defaultQuestionsData[$questionName];

            if ($isPreviousStandardQuestion) {
                $this->migrateDeiaQuestion($question->deia_question_id, $defaultQuestionData);
            }

            $responseOptions = Capsule::table('deia_response_options')
                ->where('deia_question_id', '=', $question->deia_question_id)
                ->get();

            foreach ($responseOptions as $responseOption) {
                $responseOptionSettings = $this->getResponseOptionSettings($responseOption->deia_response_option_id);

                if (!$this->isPreviousStandardResponseOption($responseOptionSettings)) {
                    continue;
                }

                $responseOptionText = $this->getDataInBaseLocale($responseOptionSettings, 'optionText');

                foreach ($defaultQuestionData['responseOptions'] as $defaultResponseOption) {
                    $defaultResponseOptionText = __($defaultResponseOption['optionText'], [], self::BASE_LOCALE);
                    if (strpos($responseOptionText, $defaultResponseOptionText) !== false) {
                        $this->migrateDeiaResponseOption(
                            $responseOption->deia_response_option_id,
                            $defaultResponseOption
                        );
                        break;
                    }
                }
            }
        }
    }

    private function getQuestionSettings(int $questionId): array
    {
        return $this->getSettings('deia_question_settings', 'deia_question_id', $questionId);
    }

    private function getResponseOptionSettings(int $responseOptionId): array
    {
        return $this->getSettings(
            'deia_response_option_settings',
            'deia_response_option_id',
            $responseOptionId
        );
    }

    private function getSettings(string $tableName, string $primaryKeyColumn, int $primaryKey): array
    {
        $settings = [];
        $settingRows = Capsule::table($tableName)
            ->where($primaryKeyColumn, '=', $primaryKey)
            ->get();

        foreach ($settingRows as $settingRow) {
            $value = $this->decodeSettingValue($settingRow->setting_value);
            if (!empty($settingRow->locale)) {
                if (!isset($settings[$settingRow->setting_name]) || !is_array($settings[$settingRow->setting_name])) {
                    $settings[$settingRow->setting_name] = [];
                }
                $settings[$settingRow->setting_name][$settingRow->locale] = $value;
            } else {
                $settings[$settingRow->setting_name] = $value;
            }
        }

        return $settings;
    }

    private function decodeSettingValue($settingValue)
    {
        $decodedValue = json_decode($settingValue, true);
        if (!is_null($decodedValue)) {
            return $decodedValue;
        }

        $unserializedValue = @unserialize($settingValue);
        return $unserializedValue === false && $settingValue !== 'b:0;' ? $settingValue : $unserializedValue;
    }

    private function getQuestionName(array $questionSettings, bool $isPreviousStandardQuestion): ?string
    {
        if (empty($questionSettings['questionText'])) {
            return null;
        }

        if ($isPreviousStandardQuestion) {
            $questionName = $this->getDataInBaseLocale($questionSettings, 'questionText');
        } elseif (is_string($questionSettings['questionText'])) {
            $questionName = __($questionSettings['questionText'], [], self::BASE_LOCALE);
        } else {
            return null;
        }

        if (is_null($questionName)) {
            return null;
        }

        return strtolower($questionName);
    }

    private function getDataInBaseLocale(array $data, string $dataName): ?string
    {
        $dataValue = $data[$dataName];
        return $dataValue[self::BASE_LOCALE] ?? $dataValue['en'] ?? null;
    }

    private function migrateDeiaQuestion(int $questionId, array $defaultQuestionData)
    {
        $this->cleanQuestionTextualData($questionId);
        $this->updateQuestionSetting($questionId, 'isTranslated', (int) $defaultQuestionData['isTranslated']);
        $this->updateQuestionSetting($questionId, 'isDefaultQuestion', (int) $defaultQuestionData['isDefaultQuestion']);
        $this->updateQuestionSetting($questionId, 'questionText', $defaultQuestionData['questionText']);
        $this->updateQuestionSetting($questionId, 'questionDescription', $defaultQuestionData['questionDescription']);
    }

    private function updateQuestionSetting(int $questionId, string $settingName, $settingValue): void
    {
        Capsule::table('deia_question_settings')->updateOrInsert(
            [
                'deia_question_id' => $questionId,
                'locale' => '',
                'setting_name' => $settingName,
            ],
            ['setting_value' => $settingValue]
        );
    }

    private function migrateDeiaResponseOption(int $responseOptionId, array $defaultResponseOption)
    {
        $this->cleanResponseOptionTextualData($responseOptionId);
        $this->updateResponseOptionSetting($responseOptionId, 'optionText', $defaultResponseOption['optionText']);
        $this->updateResponseOptionSetting(
            $responseOptionId,
            'isTranslated',
            (int) $defaultResponseOption['isTranslated']
        );
    }

    private function updateResponseOptionSetting(int $responseOptionId, string $settingName, $settingValue): void
    {
        Capsule::table('deia_response_option_settings')->updateOrInsert(
            [
                'deia_response_option_id' => $responseOptionId,
                'locale' => '',
                'setting_name' => $settingName,
            ],
            ['setting_value' => $settingValue]
        );
    }

    private function isPreviousStandardQuestion(array $questionSettings): bool
    {
        return !array_key_exists('isTranslated', $questionSettings)
            && !array_key_exists('isDefaultQuestion', $questionSettings)
            && !empty($questionSettings['questionText'])
            && is_array($questionSettings['questionText'])
            && in_array(
                $this->getDataInBaseLocale($questionSettings, 'questionText'),
                self::PREVIOUS_STANDARD_QUESTIONS
            );
    }

    private function isPreviousStandardResponseOption(array $responseOptionSettings): bool
    {
        return !array_key_exists('isTranslated', $responseOptionSettings)
            && !empty($responseOptionSettings['optionText'])
            && is_array($responseOptionSettings['optionText']);
    }

    private function cleanQuestionTextualData(int $questionId): void
    {
        Capsule::table('deia_question_settings')
            ->where('deia_question_id', $questionId)
            ->whereIn('setting_name', ['questionText', 'questionDescription'])
            ->delete();
    }

    private function cleanResponseOptionTextualData(int $responseOptionId): void
    {
        Capsule::table('deia_response_option_settings')
            ->where('deia_response_option_id', $responseOptionId)
            ->whereIn('setting_name', ['optionText'])
            ->delete();
    }
}
