<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use DomainException;

class QuestionnaireResponseValidator
{
    public function normalize(array $questions, array $responses, array $responseOptionsInputs): array
    {
        $allowedQuestions = [];
        foreach ($questions as $question) {
            $allowedQuestions[(int) $question['questionId']] = $question;
        }

        $definedOptionInputs = [];
        foreach ($allowedQuestions as $question) {
            foreach ($this->getAllowedOptions($question['responseOptions']) as $optionId => $option) {
                if ($option['hasInput']) {
                    $definedOptionInputs[$optionId] = true;
                }
            }
        }

        $normalizedResponses = [];
        $selectedOptions = [];
        foreach ($responses as $fieldName => $value) {
            if (!is_string($fieldName)
                || !preg_match('/^question-([1-9][0-9]*)-([a-z]+)$/D', $fieldName, $matches)
            ) {
                throw new DomainException('Malformed questionnaire field.');
            }

            $questionId = (int) $matches[1];
            if (!isset($allowedQuestions[$questionId])) {
                throw new DomainException('Question is not active in this context.');
            }

            $question = $allowedQuestions[$questionId];
            $canonicalFieldName = "question-{$questionId}-{$question['inputType']}";
            if ($fieldName !== $canonicalFieldName || array_key_exists($canonicalFieldName, $normalizedResponses)) {
                throw new DomainException('Question type or identifier is invalid.');
            }

            [$normalizedValue, $selected] = $this->normalizeValue($question, $value);
            $normalizedResponses[$canonicalFieldName] = $normalizedValue;
            $selectedOptions += $selected;
        }

        if (count($normalizedResponses) !== count($allowedQuestions)) {
            throw new DomainException('Responses must match all active questions.');
        }

        $normalizedInputs = [];
        foreach ($responseOptionsInputs as $fieldName => $value) {
            if (!is_string($fieldName)
                || !preg_match('/^responseOptionInput-([1-9][0-9]*)$/D', $fieldName, $matches)
                || is_array($value)
                || is_object($value)
            ) {
                throw new DomainException('Malformed response option input.');
            }

            $optionId = (int) $matches[1];
            if (!isset($definedOptionInputs[$optionId])) {
                throw new DomainException('Response option input is not defined.');
            }
            if ((string) $value === '' && empty($selectedOptions[$optionId]['hasInput'])) {
                continue;
            }
            if (empty($selectedOptions[$optionId]['hasInput'])) {
                throw new DomainException('Response option input is outside the selected question.');
            }
            $normalizedInputs["responseOptionInput-{$optionId}"] = (string) $value;
        }

        return [
            'responses' => $normalizedResponses,
            'responseOptionsInputs' => $normalizedInputs,
        ];
    }

    private function normalizeValue(array $question, $value): array
    {
        $type = (int) $question['type'];
        if (in_array($type, [
            DeiaQuestion::TYPE_SMALL_TEXT_FIELD,
            DeiaQuestion::TYPE_TEXT_FIELD,
            DeiaQuestion::TYPE_TEXTAREA,
        ], true)) {
            if (is_array($value)) {
                foreach ($value as $locale => $localizedValue) {
                    if (!is_string($locale) || is_array($localizedValue) || is_object($localizedValue)) {
                        throw new DomainException('Malformed localized response.');
                    }
                }
            } elseif (is_object($value)) {
                throw new DomainException('Malformed textual response.');
            }
            return [$value, []];
        }

        $allowedOptions = $this->getAllowedOptions($question['responseOptions']);
        if ($type === DeiaQuestion::TYPE_DROP_DOWN_BOX) {
            if (is_array($value) || is_object($value) || !ctype_digit((string) $value)) {
                throw new DomainException('Malformed select response.');
            }
            $values = [(int) $value];
        } else {
            if (!is_array($value) || empty($value)) {
                throw new DomainException('Malformed option response.');
            }
            $values = [];
            foreach ($value as $optionId) {
                if (is_array($optionId) || is_object($optionId) || !ctype_digit((string) $optionId)) {
                    throw new DomainException('Malformed response option identifier.');
                }
                $values[] = (int) $optionId;
            }
            if (count($values) !== count(array_unique($values))) {
                throw new DomainException('Repeated response option identifier.');
            }
            if ($type === DeiaQuestion::TYPE_RADIO_BUTTONS && count($values) !== 1) {
                throw new DomainException('Radio response must contain one option.');
            }
        }

        $selected = [];
        foreach ($values as $optionId) {
            if (!array_key_exists($optionId, $allowedOptions)) {
                throw new DomainException('Response option does not belong to the question.');
            }
            $selected[$optionId] = $allowedOptions[$optionId];
        }

        return [$type === DeiaQuestion::TYPE_DROP_DOWN_BOX ? $values[0] : $values, $selected];
    }

    private function getAllowedOptions($responseOptions): array
    {
        $allowed = [];
        foreach ($responseOptions as $key => $responseOption) {
            if (is_object($responseOption)) {
                $allowed[(int) $responseOption->getId()] = ['hasInput' => $responseOption->hasInputField()];
            } else {
                $allowed[(int) $key] = ['hasInput' => false];
            }
        }
        return $allowed;
    }
}
