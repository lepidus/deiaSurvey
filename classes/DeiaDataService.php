<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\core\Application;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use PKP\facades\Locale;

class DeiaDataService
{
    public function retrieveAllQuestions(int $contextId, bool $shouldRetrieveResponses = false)
    {
        $questionBlocks = $this->retrieveQuestionBlocks($contextId, $shouldRetrieveResponses);
        $questions = [];

        foreach ($questionBlocks as $questionBlock) {
            foreach ($questionBlock['questions'] as $question) {
                $questions[] = $question;
            }
        }

        return $questions;
    }

    public function retrieveQuestionBlocks(int $contextId, bool $shouldRetrieveResponses = false): array
    {
        $request = \Application::get()->getRequest();
        $questionBlocks = [];
        $deiaQuestionBlocks = Repo::deiaQuestionBlock()
            ->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByActive(true)
            ->getMany();

        foreach ($deiaQuestionBlocks as $deiaQuestionBlock) {
            $questionBlockData = [
                'id' => $deiaQuestionBlock->getId(),
                'title' => $deiaQuestionBlock->getLocalizedTitle(),
                'description' => $deiaQuestionBlock->getLocalizedDescription(),
                'questions' => []
            ];

            $deiaQuestions = Repo::deiaQuestion()
                ->getCollector()
                ->filterByContextIds([$contextId])
                ->filterByQuestionBlockIds([$deiaQuestionBlock->getId()])
                ->getMany();

            foreach ($deiaQuestions as $deiaQuestion) {
                $questionData = [
                    'id' => $deiaQuestion->getId(),
                    'type' => $deiaQuestion->getQuestionType(),
                    'inputType' => $deiaQuestion->getQuestionInputType(),
                    'title' => $deiaQuestion->getLocalizedQuestionText(),
                    'description' => $this->getCleanText($deiaQuestion->getLocalizedQuestionDescription()),
                    'responseOptions' => $deiaQuestion->getResponseOptions()
                ];

                if ($deiaQuestion->getQuestionType() == DeiaQuestion::TYPE_DROP_DOWN_BOX) {
                    $questionData['responseOptions'] = [];
                    foreach ($deiaQuestion->getResponseOptions() as $responseOption) {
                        $questionData['responseOptions'][$responseOption->getId()] = $responseOption->getLocalizedOptionText();
                    }
                }

                if ($shouldRetrieveResponses) {
                    $user = $request->getUser();
                    $questionData['response'] = $this->getUserResponse($deiaQuestion, $user->getId());
                }

                $questionBlockData['questions'][] = $questionData;
            }

            $questionBlocks[] = $questionBlockData;
        }

        return $questionBlocks;
    }

    private function getUserResponse(DeiaQuestion $question, int $userId)
    {
        $deiaResponses = Repo::deiaResponse()
            ->getCollector()
            ->filterByQuestionIds([$question->getId()])
            ->filterByUserIds([$userId])
            ->getMany()
            ->toArray();

        if (empty($deiaResponses)) {
            if (
                $question->getQuestionType() == DeiaQuestion::TYPE_CHECKBOXES
                || $question->getQuestionType() == DeiaQuestion::TYPE_RADIO_BUTTONS
            ) {
                return ['value' => [], 'optionsInputValue' => []];
            }

            return ['value' => null];
        }

        $firstResponse = array_shift($deiaResponses);
        return ['value' => $firstResponse->getValue(), 'optionsInputValue' => $firstResponse->getOptionsInputValue()];
    }

    private function getCleanText(?string $text): string
    {
        if (!$text) {
            return '';
        }

        $text = preg_replace('/<br\s*\/?>|<\/p>|<\/div>|<\/li>/i', ' ', $text);
        $text = strip_tags($text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function registerUserResponses(int $userId, array $responses, array $responseOptionsInputs)
    {
        foreach ($responses as $question => $responseInput) {
            $questionId = explode("-", $question)[1];
            $deiaResponses = Repo::deiaResponse()
                ->getCollector()
                ->filterByQuestionIds([$questionId])
                ->filterByUserIds([$userId])
                ->getMany()
                ->toArray();
            $deiaResponse = array_shift($deiaResponses);

            $optionsInputValue = $this->getResponseOptionsInputValue($questionId, $responseOptionsInputs, $responseInput);

            if ($deiaResponse) {
                Repo::deiaResponse()->edit($deiaResponse, [
                    'responseValue' => $responseInput,
                    'optionsInputValue' => $optionsInputValue
                ]);
            } else {
                $response = Repo::deiaResponse()->newDataObject();
                $response->setUserId($userId);
                $response->setDeiaQuestionId($questionId);
                $response->setData('responseValue', $responseInput);
                $response->setOptionsInputValue($optionsInputValue);
                Repo::deiaResponse()->add($response);
            }
        }
    }

    private function getResponseOptionsInputValue($questionId, $responseOptionsInputs, $responseInput)
    {
        $deiaQuestion = Repo::deiaQuestion()->get($questionId);

        if ($deiaQuestion->getQuestionType() == DeiaQuestion::TYPE_CHECKBOXES
            || $deiaQuestion->getQuestionType() == DeiaQuestion::TYPE_RADIO_BUTTONS
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
        $locale = \AppLocale::getLocale();

        foreach ($responses as $question => $responseInput) {
            $questionParts = explode("-", $question);
            $questionId = $questionParts[1];
            $questionType = $questionParts[2];

            $optionsInputValue = $this->getResponseOptionsInputValue($questionId, $responseOptionsInputs, $responseInput);

            $response = Repo::deiaResponse()->newDataObject();
            $response->setDeiaQuestionId($questionId);
            $response->setExternalId($externalId);
            $response->setExternalType($externalType);
            $response->setOptionsInputValue($optionsInputValue);

            if ($questionType == 'text' || $questionType == 'textarea') {
                $response->setData('responseValue', $responseInput, $locale);
            } else {
                $response->setData('responseValue', $responseInput);
            }

            Repo::deiaResponse()->add($response);
        }
    }

    public function getExternalAuthorResponses(int $contextId, string $externalId, string $externalType): array
    {
        $externalAuthorResponses = [];
        $authorResponses = Repo::deiaResponse()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByExternalIds([$externalId])
            ->filterByExternalTypes([$externalType])
            ->getMany();

        foreach ($authorResponses as $response) {
            $question = Repo::deiaQuestion()->get($response->getDeiaQuestionId());
            $responseValueForDisplay = $this->getResponseValueForDisplay($question, $response);
            $externalAuthorResponses[$question->getId()] = $responseValueForDisplay;
        }

        return $externalAuthorResponses;
    }

    private function getResponseValueForDisplay($question, $response): string
    {
        if (in_array(
            $question->getQuestionType(),
            [DeiaQuestion::TYPE_SMALL_TEXT_FIELD, DeiaQuestion::TYPE_TEXT_FIELD, DeiaQuestion::TYPE_TEXTAREA]
        )) {
            return $response->getLocalizedData('responseValue');
        }

        if (
            $question->getQuestionType() == DeiaQuestion::TYPE_CHECKBOXES
            || $question->getQuestionType() == DeiaQuestion::TYPE_RADIO_BUTTONS
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

        if ($question->getQuestionType() == DeiaQuestion::TYPE_DROP_DOWN_BOX) {
            $responseOptions = $question->getResponseOptions();
            $selectedResponseOption = $responseOptions[$response->getValue()];

            return $selectedResponseOption->getLocalizedOptionText();
        }

        return '';
    }

    public function deleteUserResponses(int $userId, int $contextId)
    {
        $userResponses = Repo::deiaResponse()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByUserIds([$userId])
            ->getMany();

        foreach ($userResponses as $response) {
            Repo::deiaResponse()->delete($response);
        }
    }

    public function deleteAuthorResponses(int $contextId, string $externalId, string $externalType)
    {
        $authorResponses = Repo::deiaResponse()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByExternalIds([$externalId])
            ->filterByExternalTypes([$externalType])
            ->getMany();

        foreach ($authorResponses as $response) {
            Repo::deiaResponse()->delete($response);
        }
    }

    public function authorAlreadyAnsweredQuestionnaire($author, $authorOrcid = null): bool
    {
        $externalId = $author->getData('email');
        $externalType = 'email';

        if (!is_null($authorOrcid)) {
            $externalId = $authorOrcid;
            $externalType = 'orcid';
        } elseif (!is_null($author->getData('deiaOrcid'))) {
            $externalId = $author->getData('deiaOrcid');
            $externalType = 'orcid';
        }

        $countAuthorResponses = Repo::deiaResponse()
            ->getCollector()
            ->filterByExternalIds([$externalId])
            ->filterByExternalTypes([$externalType])
            ->getCount();

        return ($countAuthorResponses > 0);
    }

    public function migrateResponsesByUserIdentifier($context, $user, $idName)
    {
        $contextQuestions = Repo::deiaQuestion()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany()
            ->toArray();

        if (empty($contextQuestions)) {
            return;
        }

        $questionsIds = array_map(function ($question) {
            return $question->getId();
        }, $contextQuestions);

        $userResponses = Repo::deiaResponse()->getCollector()
            ->filterByExternalIds([$user->getData($idName)])
            ->filterByExternalTypes([$idName])
            ->filterByQuestionIds($questionsIds)
            ->getMany()
            ->toArray();

        if (!empty($userResponses)) {
            foreach ($userResponses as $response) {
                Repo::deiaResponse()->edit($response, [
                    'userId' => $user->getId(),
                    'externalId' => null,
                    'externalType' => null
                ]);
            }

            $deiaDataDao = new DeiaDataDAO();
            $deiaDataDao->updateDeiaConsent($context->getId(), $user->getId(), true);
        }
    }
}
