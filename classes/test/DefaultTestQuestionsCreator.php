<?php

namespace APP\plugins\generic\demographicData\classes\test;

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
                $demographicQuestionId = Repo::demographicQuestion()->add($questionObject);

                if (isset($questionData['responseOptions'])) {
                    foreach ($questionData['responseOptions'] as $optionData) {
                        $optionData['demographicQuestionId'] = $demographicQuestionId;
                        $responseOptionObject = Repo::demographicResponseOption()->newDataObject($optionData);
                        Repo::demographicResponseOption()->add($responseOptionObject);
                    }
                }
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
                'responseOptions' => [
                    [
                        'optionText' => ['en' => 'English', 'fr_CA' => 'Anglais'],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => ['en' => 'French', 'fr_CA' => 'Français'],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => ['en' => 'Hindi', 'fr_CA' => 'Hindi'],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => ['en' => 'Mandarin', 'fr_CA' => 'Mandarin'],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => ['en' => 'Portuguese', 'fr_CA' => 'Portugais'],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => ['en' => 'Spanish', 'fr_CA' => 'Espagnol'],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => ['en' => 'Other:', 'fr_CA' => 'Autre:'],
                        'hasInputField' => true
                    ]
                ]
            ],
            [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_RADIO_BUTTONS,
                'questionText' => ['en' => 'Nacionality'],
                'questionDescription' => ['en' => 'Which continent are you from?'],
                'responseOptions' => [
                    [
                        'optionText' => ['en' => 'Africa', 'fr_CA' => 'Afrique'],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => ['en' => 'America', 'fr_CA' => 'Amérique'],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => ['en' => 'Asia', 'fr_CA' => 'Asie'],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => ['en' => 'Europe', 'fr_CA' => 'Europe'],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => ['en' => 'Oceania', 'fr_CA' => 'Océanie'],
                        'hasInputField' => false
                    ]
                ]
            ],
            [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_DROP_DOWN_BOX,
                'questionText' => ['en' => 'Salary'],
                'questionDescription' => ['en' => 'What range is your current salary in?'],
                'responseOptions' => [
                    [
                        'optionText' => [
                            'en' => 'Less than a minimum wage',
                            'fr_CA' => "Moins qu'un salaire minimum"
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'One to three minimum wages',
                            'fr_CA' => 'Un à trois salaires minimums'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Three to five minimum wages',
                            'fr_CA' => 'Trois à cinq salaires minimums'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'More than five minimum wages',
                            'fr_CA' => 'Plus de cinq salaires minimums'
                        ],
                        'hasInputField' => false
                    ]
                ]
            ]
        ];
    }
}
