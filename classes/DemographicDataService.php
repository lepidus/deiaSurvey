<?php

namespace APP\plugins\generic\demographicData\classes;

use APP\core\Application;
use APP\plugins\generic\demographicData\classes\DemographicDataDAO;
use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\demographicData\classes\facades\Repo;
use PKP\facades\Locale;

class DemographicDataService
{
    public function retrieveAllQuestions(int $contextId, bool $shouldRetrieveResponses = false)
    {
        $request = \Application::get()->getRequest();
        $questions = array();
        $demographicQuestions = Repo::demographicQuestion()
            ->getCollector()
            ->filterByContextIds([$contextId])
            ->getMany();

        foreach ($demographicQuestions as $demographicQuestion) {
            $questionData = [
                'questionId' => $demographicQuestion->getId(),
                'type' => $demographicQuestion->getQuestionType(),
                'inputType' => $demographicQuestion->getQuestionInputType(),
                'title' => $demographicQuestion->getLocalizedQuestionText(),
                'description' => $demographicQuestion->getLocalizedQuestionDescription(),
                'responseOptions' => $demographicQuestion->getResponseOptions()
            ];

            if ($demographicQuestion->getQuestionType() == DemographicQuestion::TYPE_DROP_DOWN_BOX) {
                $questionData['responseOptions'] = [];
                foreach ($demographicQuestion->getResponseOptions() as $responseOption) {
                    $questionData['responseOptions'][$responseOption->getId()] = $responseOption->getLocalizedOptionText();
                }
            }

            if ($shouldRetrieveResponses) {
                $user = $request->getUser();
                $questionData['response'] = $this->getUserResponse($demographicQuestion, $user->getId());
            }

            $questions[] = $questionData;
        }

        return $questions;
    }

    private function getUserResponse(DemographicQuestion $question, int $userId)
    {
        $demographicResponses = Repo::demographicResponse()
            ->getCollector()
            ->filterByQuestionIds([$question->getId()])
            ->filterByUserIds([$userId])
            ->getMany()
            ->toArray();

        if (empty($demographicResponses)) {
            if (
                $question->getQuestionType() == DemographicQuestion::TYPE_CHECKBOXES
                || $question->getQuestionType() == DemographicQuestion::TYPE_RADIO_BUTTONS
            ) {
                return ['value' => [], 'optionsInputValue' => []];
            }

            return ['value' => null];
        }

        $firstResponse = array_shift($demographicResponses);
        return ['value' => $firstResponse->getValue(), 'optionsInputValue' => $firstResponse->getOptionsInputValue()];
    }

    public function registerUserResponses(int $userId, array $responses, array $responseOptionsInputs)
    {
        foreach ($responses as $question => $responseInput) {
            $questionId = explode("-", $question)[1];
            $demographicResponses = Repo::demographicResponse()
                ->getCollector()
                ->filterByQuestionIds([$questionId])
                ->filterByUserIds([$userId])
                ->getMany()
                ->toArray();
            $demographicResponse = array_shift($demographicResponses);

            $optionsInputValue = $this->getResponseOptionsInputValue($questionId, $responseOptionsInputs, $responseInput);

            if ($demographicResponse) {
                Repo::demographicResponse()->edit($demographicResponse, [
                    'responseValue' => $responseInput,
                    'optionsInputValue' => $optionsInputValue
                ]);
            } else {
                $response = Repo::demographicResponse()->newDataObject();
                $response->setUserId($userId);
                $response->setDemographicQuestionId($questionId);
                $response->setData('responseValue', $responseInput);
                $response->setOptionsInputValue($optionsInputValue);
                Repo::demographicResponse()->add($response);
            }
        }
    }

    private function getResponseOptionsInputValue($questionId, $responseOptionsInputs, $responseInput)
    {
        $demographicQuestion = Repo::demographicQuestion()->get($questionId);

        if ($demographicQuestion->getQuestionType() == DemographicQuestion::TYPE_CHECKBOXES
            || $demographicQuestion->getQuestionType() == DemographicQuestion::TYPE_RADIO_BUTTONS
        ) {
            $responseOptionsInputValue = [];
            foreach ($responseInput as $responseOptionId) {
                $responseOptionInputName = "responseOptionInput-$responseOptionId";
                if (isset($responseOptionsInputs[$responseOptionInputName])) {
                    $responseOptionsInputValue[$responseOptionId] = $responseOptionsInputs[$responseOptionInputName];
                }
            }

            return $responseOptionsInputValue;
        }

        return null;
    }

    public function registerExternalAuthorResponses(string $externalId, string $externalType, array $responses, array $responseOptionsInputs)
    {
        $locale = \Locale::getLocale();

        foreach ($responses as $question => $responseInput) {
            $questionParts = explode("-", $question);
            $questionId = $questionParts[1];
            $questionType = $questionParts[2];

            $optionsInputValue = $this->getResponseOptionsInputValue($questionId, $responseOptionsInputs, $responseInput);

            $response = Repo::demographicResponse()->newDataObject();
            $response->setDemographicQuestionId($questionId);
            $response->setExternalId($externalId);
            $response->setExternalType($externalType);
            $response->setOptionsInputValue($optionsInputValue);

            if ($questionType == 'text' or $questionType == 'textarea') {
                $response->setData('responseValue', $responseInput, $locale);
            } else {
                $response->setData('responseValue', $responseInput);
            }

            Repo::demographicResponse()->add($response);
        }
    }

    public function getExternalAuthorResponses(int $contextId, string $externalId, string $externalType): array
    {
        $externalAuthorResponses = [];
        $authorResponses = Repo::demographicResponse()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByExternalIds([$externalId])
            ->filterByExternalTypes([$externalType])
            ->getMany();

        foreach ($authorResponses as $response) {
            $question = Repo::demographicQuestion()->get($response->getDemographicQuestionId());
            $responseValueForDisplay = $this->getResponseValueForDisplay($question, $response);
            $externalAuthorResponses[$question->getId()] = $responseValueForDisplay;
        }

        return $externalAuthorResponses;
    }

    private function getResponseValueForDisplay($question, $response): string
    {
        if (in_array(
            $question->getQuestionType(),
            [DemographicQuestion::TYPE_SMALL_TEXT_FIELD, DemographicQuestion::TYPE_TEXT_FIELD, DemographicQuestion::TYPE_TEXTAREA]
        )) {
            return $response->getLocalizedData('responseValue');
        }

        if (
            $question->getQuestionType() == DemographicQuestion::TYPE_CHECKBOXES
            || $question->getQuestionType() == DemographicQuestion::TYPE_RADIO_BUTTONS
        ) {
            $responseOptions = $question->getResponseOptions();
            $selectedResponseOptionsTexts = [];

            foreach ($response->getValue() as $selectedResponseOptionId) {
                $selectedResponseOption = $responseOptions[$selectedResponseOptionId];
                $selectedResponseOptionsText = $selectedResponseOption->getLocalizedOptionText();

                if ($selectedResponseOption->hasInputField()) {
                    $optionsInputValue = $response->getOptionsInputValue();
                    $selectedResponseOptionsText .= ' "' . $optionsInputValue[$selectedResponseOptionId] . '"';
                }

                $selectedResponseOptionsTexts[] = $selectedResponseOptionsText;
            }

            return implode(', ', $selectedResponseOptionsTexts);
        }

        if ($question->getQuestionType() == DemographicQuestion::TYPE_DROP_DOWN_BOX) {
            $responseOptions = $question->getResponseOptions();
            $selectedResponseOption = $responseOptions[$response->getValue()];

            return $selectedResponseOption->getLocalizedOptionText();
        }

        return '';
    }

    public function deleteUserResponses(int $userId, int $contextId)
    {
        $userResponses = Repo::demographicResponse()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByUserIds([$userId])
            ->getMany();

        foreach ($userResponses as $response) {
            Repo::demographicResponse()->delete($response);
        }
    }

    public function deleteAuthorResponses(int $contextId, string $externalId, string $externalType)
    {
        $authorResponses = Repo::demographicResponse()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByExternalIds([$externalId])
            ->filterByExternalTypes([$externalType])
            ->getMany();

        foreach ($authorResponses as $response) {
            Repo::demographicResponse()->delete($response);
        }
    }

    public function authorAlreadyAnsweredQuestionnaire($author, $authorOrcid = null): bool
    {
        $externalId = $author->getData('email');
        $externalType = 'email';

        if (!is_null($authorOrcid)) {
            $externalId = $authorOrcid;
            $externalType = 'orcid';
        } elseif (!is_null($author->getData('demographicOrcid'))) {
            $externalId = $author->getData('demographicOrcid');
            $externalType = 'orcid';
        }

        $countAuthorResponses = Repo::demographicResponse()
            ->getCollector()
            ->filterByExternalIds([$externalId])
            ->filterByExternalTypes([$externalType])
            ->getCount();

        return ($countAuthorResponses > 0);
    }

    public function migrateResponsesByUserIdentifier($context, $user, $idName)
    {
        $contextQuestions = Repo::demographicQuestion()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany()
            ->toArray();

        if (empty($contextQuestions)) {
            return;
        }

        $questionsIds = array_map(function ($question) {
            return $question->getId();
        }, $contextQuestions);

        $userResponses = Repo::demographicResponse()->getCollector()
            ->filterByExternalIds([$user->getData($idName)])
            ->filterByExternalTypes([$idName])
            ->filterByQuestionIds($questionsIds)
            ->getMany()
            ->toArray();

        if (!empty($userResponses)) {
            foreach ($userResponses as $response) {
                Repo::demographicResponse()->edit($response, [
                    'userId' => $user->getId(),
                    'externalId' => null,
                    'externalType' => null
                ]);
            }

            $demographicDataDao = new DemographicDataDAO();
            $demographicDataDao->updateDemographicConsent($context->getId(), $user->getId(), true);
        }
    }
}
