<?php

namespace APP\plugins\generic\demographicData\tests;

use APP\core\Application;
use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\demographicData\classes\facades\Repo;

class DefaultTestQuestionsCreator
{
    /*
     * The following questions are for test purposes, and should be
     * replaced by the real default questions when they be ready.
     */
    public function createDefaultTestQuestions()
    {
        $request = Application::get()->getRequest();
        $contextId = $request->getContext()->getId();

        $demographicQuestionsCount = Repo::demographicQuestion()
            ->getCollector()
            ->filterByContextIds([$contextId])
            ->getCount();

        if ($demographicQuestionsCount == 0) {
            $defaultTestQuestions = $this->getDefaultTestQuestionsData($contextId);

            foreach ($defaultTestQuestions as $questionData) {
                $questionObject = Repo::demographicQuestion()->newDataObject($questionData);
                Repo::demographicQuestion()->add($questionObject);
            }
        }
    }

    private function getDefaultTestQuestionsData(int $contextId): array
    {
        return [
            [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_TEXT_FIELD,
                'questionText' => ['en' => 'Gender'],
                'questionDescription' => ['en' => 'With which gender do you most identify?']
            ],
            [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_TEXT_FIELD,
                'questionText' => ['en' => 'Ethnicity'],
                'questionDescription' => ['en' => 'How would you identify yourself in terms of ethnicity?']
            ]
        ];
    }
}
