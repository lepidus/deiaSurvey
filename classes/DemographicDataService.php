<?php

namespace APP\plugins\generic\demographicData\classes;

use APP\plugins\generic\demographicData\classes\facades\Repo;
use APP\core\Application;

class DemographicDataService
{
    public static function retrieveQuestions()
    {
        $request = Application::get()->getRequest();
        $contextId = $request->getContext()->getId();
        $questions = array();
        $demographicQuestions = Repo::demographicQuestion()
            ->getCollector()
            ->filterByContextIds([$contextId])
            ->getMany();

        foreach ($demographicQuestions as $demographicQuestion) {
            $user = $request->getUser();
            $demographicResponse = Repo::demographicResponse()
                ->getCollector()
                ->filterByQuestionIds([$demographicQuestion->getId()])
                ->filterByUserIds([$user->getId()])
                ->getMany();
            $responseResultInArray = $demographicResponse->toArray();
            $firstResponse = array_shift($responseResultInArray);
            $response = empty($demographicResponse->toArray()) ? null : $firstResponse->getText();
            $questions[] = [
                'title' => $demographicQuestion->getLocalizedQuestionText(),
                'description' => $demographicQuestion->getLocalizedQuestionDescription(),
                'response' => $response,
                'questionId' => $demographicQuestion->getId()
            ];
        }
        return $questions;
    }

    public static function registerResponse($userId, $args)
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
