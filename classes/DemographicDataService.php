<?php

namespace APP\plugins\generic\demographicData\classes;

use APP\core\Application;
use PKP\facades\Locale;
use APP\plugins\generic\demographicData\classes\DemographicDataDAO;
use APP\plugins\generic\demographicData\classes\facades\Repo;
use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;

class DemographicDataService
{
    public function retrieveAllQuestions(int $contextId, bool $shouldRetrieveResponses = false)
    {
        $request = Application::get()->getRequest();
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
                'possibleResponses' => $demographicQuestion->getLocalizedPossibleResponses()
            ];

            if ($shouldRetrieveResponses) {
                $user = $request->getUser();
                $response = $this->getUserResponse($demographicQuestion, $user->getId());

                $questionData['response'] = $response;
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
                return [];
            }

            return null;
        }

        $firstResponse = array_shift($demographicResponses);
        return $firstResponse->getValue();
    }

    public function registerUserResponses(int $userId, array $responses)
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
            if ($demographicResponse) {
                Repo::demographicResponse()->edit($demographicResponse, ['responseValue' => $responseInput]);
            } else {
                $response = Repo::demographicResponse()->newDataObject();
                $response->setUserId($userId);
                $response->setDemographicQuestionId($questionId);
                $response->setData('responseValue', $responseInput);
                Repo::demographicResponse()->add($response);
            }
        }
    }

    public function registerExternalAuthorResponses(string $externalId, string $externalType, array $responses)
    {
        $locale = Locale::getLocale();

        foreach ($responses as $question => $responseInput) {
            $questionParts = explode("-", $question);
            $questionId = $questionParts[1];
            $questionType = $questionParts[2];

            $response = Repo::demographicResponse()->newDataObject();
            $response->setDemographicQuestionId($questionId);
            $response->setExternalId($externalId);
            $response->setExternalType($externalType);

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
            $externalAuthorResponses[$response->getDemographicQuestionId()] = $response;
        }

        return $externalAuthorResponses;
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

    public function authorAlreadyAnsweredQuestionnaire($author, $authorOrcid = null): bool
    {
        $externalId = $author->getData('email');
        $externalType = 'email';

        if (!is_null($authorOrcid)) {
            $externalId = $authorOrcid;
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
