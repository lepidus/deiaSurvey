<?php

namespace APP\plugins\generic\demographicData\classes;

use APP\plugins\generic\demographicData\classes\facades\Repo;
use APP\core\Application;

class DemographicDataService
{
    public function retrieveQuestions($shouldRetrieveResponses = false)
    {
        $request = Application::get()->getRequest();
        $contextId = $request->getContext()->getId();
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

    public function registerResponse(int $userId, array $args)
    {
        foreach ($args as $question => $responseInput) {
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
}
