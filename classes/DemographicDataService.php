<?php

namespace APP\plugins\generic\demographicData\classes;

use APP\core\Application;
use PKP\facades\Locale;
use APP\plugins\generic\demographicData\classes\DemographicDataDAO;
use APP\plugins\generic\demographicData\classes\facades\Repo;

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
                'title' => $demographicQuestion->getLocalizedQuestionText(),
                'description' => $demographicQuestion->getLocalizedQuestionDescription(),
                'questionId' => $demographicQuestion->getId()
            ];

            if ($shouldRetrieveResponses) {
                $user = $request->getUser();
                $response = $this->getUserResponse($user->getId(), $demographicQuestion->getId());
                $questionData['response'] = $response;
            }

            $questions[] = $questionData;
        }
        return $questions;
    }

    private function getUserResponse(int $userId, int $questionId)
    {
        $demographicResponses = Repo::demographicResponse()
            ->getCollector()
            ->filterByQuestionIds([$questionId])
            ->filterByUserIds([$userId])
            ->getMany()
            ->toArray();

        if (empty($demographicResponses)) {
            return null;
        }

        $firstResponse = array_shift($demographicResponses);
        return $firstResponse->getText();
    }

    public function registerUserResponses(int $userId, array $responses)
    {
        foreach ($responses as $question => $responseInput) {
            $questionId = explode("-", $question)[1];
            $demographicResponseCollector = Repo::demographicResponse()
                ->getCollector()
                ->filterByQuestionIds([$questionId])
                ->filterByUserIds([$userId])
                ->getMany();
            $demographicResponse = array_shift($demographicResponseCollector->toArray());
            if ($demographicResponse) {
                Repo::demographicResponse()->edit($demographicResponse, ['responseText' => $responseInput]);
            } else {
                $response = Repo::demographicResponse()->newDataObject();
                $response->setUserId($userId);
                $response->setDemographicQuestionId($questionId);
                $response->setData('responseText', $responseInput);
                Repo::demographicResponse()->add($response);
            }
        }
    }

    public function registerExternalAuthorResponses(string $externalId, string $externalType, array $responses)
    {
        $locale = Locale::getLocale();

        foreach ($responses as $question => $responseInput) {
            $questionId = explode("-", $question)[1];

            $response = Repo::demographicResponse()->newDataObject();
            $response->setDemographicQuestionId($questionId);
            $response->setData('responseText', $responseInput, $locale);
            $response->setExternalId($externalId);
            $response->setExternalType($externalType);

            Repo::demographicResponse()->add($response);
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
