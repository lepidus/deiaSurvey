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
            $reponse = empty($demographicResponse->toArray()) ? null : $firstResponse->getText();
            $questions[] = [
                'title' => $demographicQuestion->getLocalizedQuestionText(),
                'description' => $demographicQuestion->getLocalizedQuestionDescription(),
                'response' => $reponse,
                'questionId' => $demographicQuestion->getId()
            ];
        }
        return $questions;
    }

    public static function registerResponse($userId, $args)
    {
        foreach ($args as $question => $response) {
            $questionId = explode("-", $question)[1];
            $demographicResponseCollector = Repo::demographicResponse()
                    ->getCollector()
                    ->filterByQuestionIds([$questionId])
                    ->filterByUserIds([$userId])
                    ->getMany();
            $responseResultInArray = $demographicResponseCollector->toArray();
            $demographicResponse = array_shift($responseResultInArray);
            if (!empty($response)) {
                $params = [
                    'demographicQuestionId',
                    'userId',
                    'responseText' => $response
                ];
                Repo::demographicResponse()->edit($demographicResponse, $params);
            } else {
                foreach ($response as $locale => $responseText) {
                    if (!is_null($responseText)) {
                        $response = Repo::demographicResponse()->newDataObject();
                        $response->setUserId($userId);
                        $response->setDemographicQuestionId($questionId);
                        $response->setText($responseText, $locale);
                        Repo::demographicResponse()->add($response);
                    }
                }
            }
        }
    }
}
