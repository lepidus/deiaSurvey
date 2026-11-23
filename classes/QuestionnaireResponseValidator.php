<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;

class QuestionnaireResponseValidator
{
    public function validate(
        array $questions,
        array $responses,
        array $responseOptionsInputs,
        bool $multilingualText
    ): array {
        $allowedQuestions = [];
        foreach ($questions as $question) {
            $allowedQuestions[(int) $question['questionId']] = $question;
        }

        $submittedFields = [];

        foreach ($responses as $fieldName => $value) {
            if (!is_string($fieldName)
                || !preg_match('/^question-([1-9][0-9]*)-([a-z]+)$/D', $fieldName, $matches)
            ) {
                throw new \InvalidArgumentException('Malformed question field');
            }

            $questionId = (int) $matches[1];
            if (isset($submittedFields[$questionId]) || !isset($allowedQuestions[$questionId])) {
                throw new \InvalidArgumentException('Question is duplicated or outside the active context');
            }

            $question = $allowedQuestions[$questionId];
            if ($matches[2] !== $question['inputType']) {
                throw new \InvalidArgumentException('Question type does not match the server definition');
            }

            $submittedFields[$questionId] = $fieldName;
        }

        $normalizedResponses = [];
        $selectedOptions = [];
        $allowedOptionInputs = [];
        $definedOptionInputs = [];
        $hiddenQuestions = [];
        $hiddenOptionInputs = [];
        $singleSelections = [];

        foreach ($allowedQuestions as $questionId => $question) {
            $options = $this->getOptions($question['responseOptions']);
            $condition = $question['showWhen'] ?? [];
            if ($condition && ($singleSelections[$condition[0]] ?? null) !== $condition[1]) {
                $hiddenQuestions[] = $questionId;
                $hiddenOptionInputs += array_filter($options);
                continue;
            }

            $fieldName = $submittedFields[$questionId] ?? null;
            if ($fieldName === null) {
                throw new \InvalidArgumentException('Responses must match the active questionnaire');
            }
            $value = $responses[$fieldName];
            $questionType = (int) $question['type'];
            if (in_array($questionType, [
                DeiaQuestion::TYPE_SMALL_TEXT_FIELD,
                DeiaQuestion::TYPE_TEXT_FIELD,
                DeiaQuestion::TYPE_TEXTAREA,
            ], true)) {
                $normalizedResponses[$fieldName] = $this->validateText($value, $multilingualText);
                if ($condition && !$multilingualText && trim($normalizedResponses[$fieldName]) === '') {
                    throw new \InvalidArgumentException('Missing required conditional response');
                }
                continue;
            }

            foreach ($options as $optionId => $hasInputField) {
                if ($hasInputField) {
                    $definedOptionInputs[$optionId] = true;
                }
            }
            $values = $questionType === DeiaQuestion::TYPE_DROP_DOWN_BOX ? [$value] : $value;
            if (!is_array($values) || empty($values)) {
                throw new \InvalidArgumentException('Malformed response options');
            }
            if ($questionType === DeiaQuestion::TYPE_RADIO_BUTTONS && count($values) !== 1) {
                throw new \InvalidArgumentException('Radio questions accept exactly one option');
            }

            $normalizedValues = [];
            foreach ($values as $optionId) {
                if (!is_int($optionId) && !(is_string($optionId) && preg_match('/^[1-9][0-9]*$/D', $optionId))) {
                    throw new \InvalidArgumentException('Malformed response option identifier');
                }
                $optionId = (int) $optionId;
                if (!isset($options[$optionId]) || isset($selectedOptions[$optionId])) {
                    throw new \InvalidArgumentException('Response option is duplicated or belongs to another question');
                }
                $selectedOptions[$optionId] = true;
                $normalizedValues[] = $optionId;
                if ($options[$optionId] === true) {
                    $allowedOptionInputs[$optionId] = true;
                }
            }

            $normalizedResponses[$fieldName] = $questionType === DeiaQuestion::TYPE_DROP_DOWN_BOX
                ? $normalizedValues[0]
                : $normalizedValues;

            if ($questionType !== DeiaQuestion::TYPE_CHECKBOXES) {
                $singleSelections[$questionId] = $normalizedValues[0];
            }
        }

        $normalizedInputs = [];
        foreach ($responseOptionsInputs as $fieldName => $value) {
            if (!is_string($fieldName)
                || !preg_match('/^responseOptionInput-([1-9][0-9]*)$/D', $fieldName, $matches)
                || !is_string($value)
            ) {
                throw new \InvalidArgumentException('Malformed response option input');
            }
            $optionId = (int) $matches[1];
            if (isset($hiddenOptionInputs[$optionId])) {
                continue;
            }
            if (!isset($definedOptionInputs[$optionId])) {
                throw new \InvalidArgumentException('Response option input is not defined');
            }
            if ($value === '' && !isset($allowedOptionInputs[$optionId])) {
                continue;
            }
            if (!isset($allowedOptionInputs[$optionId])) {
                throw new \InvalidArgumentException('Response option input is not allowed');
            }
            $normalizedInputs[$fieldName] = $value;
        }

        return [$normalizedResponses, $normalizedInputs, $hiddenQuestions];
    }

    private function validateText($value, bool $multilingualText)
    {
        if ($multilingualText) {
            if (!is_array($value) || empty($value)) {
                throw new \InvalidArgumentException('Malformed multilingual text response');
            }
            foreach ($value as $locale => $localizedValue) {
                if (!is_string($locale) || !is_string($localizedValue)) {
                    throw new \InvalidArgumentException('Malformed multilingual text response');
                }
            }
            return $value;
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException('Malformed text response');
        }
        return $value;
    }

    private function getOptions($responseOptions): array
    {
        $options = [];
        foreach ($responseOptions as $key => $option) {
            if (is_object($option)) {
                $options[(int) $option->getId()] = $option->hasInputField();
            } else {
                $options[(int) $key] = false;
            }
        }
        return $options;
    }
}
