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
                'questionType' => DemographicQuestion::TYPE_SMALL_TEXT_FIELD,
                'questionText' => ['en' => 'Gender'],
                'questionDescription' => ['en' => 'With which gender do you most identify?']
            ],
            [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_TEXT_FIELD,
                'questionText' => ['en' => 'Ethnicity'],
                'questionDescription' => ['en' => 'How would you identify yourself in terms of ethnicity?']
            ],
            [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_TEXTAREA,
                'questionText' => ['en' => 'Academic background'],
                'questionDescription' => ['en' => 'Please tell us which academic institutions you have been involved with']
            ],
            [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_CHECKBOXES,
                'questionText' => ['en' => 'Languages'],
                'questionDescription' => ['en' => 'Which of these languages do you speak?'],
                'possibleResponses' => [
                    'en' => ['English', 'French', 'Hindi', 'Mandarin', 'Portuguese', 'Spanish'],
                    'fr_CA' => ['Anglais', 'Français', 'Hindi', 'Mandarin', 'Portugais', 'Espagnol']
                ]
            ],
            [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_RADIO_BUTTONS,
                'questionText' => ['en' => 'Nacionality'],
                'questionDescription' => ['en' => 'Which continent are you from?'],
                'possibleResponses' => [
                    'en' => ['Africa', 'America', 'Asia', 'Europe', 'Oceania'],
                    'fr_CA' => ['Afrique', 'Amérique', 'Asie', 'Europe', 'Océanie']
                ]
            ],
            [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_DROP_DOWN_BOX,
                'questionText' => ['en' => 'Salary'],
                'questionDescription' => ['en' => 'What range is your current salary in?'],
                'possibleResponses' => [
                    'en' => [
                        'Less than a minimum wage',
                        'One to three minimum wages',
                        'Three to five minimum wages',
                        'More than five minimum wages'
                    ],
                    'fr_CA' => [
                        "Moins qu'un salaire minimum",
                        'Un à trois salaires minimums',
                        'Trois à cinq salaires minimums',
                        'Plus de cinq salaires minimums'
                    ]
                ]
            ]
        ];
    }
}
