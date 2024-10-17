<?php

namespace APP\plugins\generic\demographicData\classes\migrations;

use APP\core\Application;
use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\demographicData\classes\facades\Repo;

class DefaultQuestionsCreator
{
    public function createDefaultQuestions()
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
                'questionType' => DemographicQuestion::TYPE_RADIO_BUTTONS,
                'questionText' => [
                    'en' => 'Gender'
                ],
                'questionDescription' => [
                    'en' => 'With which gender do you most identify? Please select one option:'
                ],
                'responseOptions' => [
                    [
                        'optionText' => [
                            'en' => 'Woman'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Man'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Non-binary or gender diverse'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Prefer not to disclose'
                        ],
                        'hasInputField' => false
                    ]
                ]
            ],
            [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_CHECKBOXES,
                'questionText' => [
                    'en' => 'Race'
                ],
                'questionDescription' => [
                    'en' => 'How would you identify yourself in terms of race? Please select ALL the groups that apply to you:'
                ],
                'responseOptions' => [
                    [
                        'optionText' => [
                            'en' => 'Asian or Pacific Islander'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Black'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Hispanic or Latino/a/x'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => "Indigenous (e.g. North American Indian Navajo, \
                            South American Indian Quechua, Aboriginal or Torres Strait Islander)"
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Middle Eastern or North African'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'White'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Prefer not to disclose'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Self describe'
                        ],
                        'hasInputField' => true
                    ]
                ]
            ],
            [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_CHECKBOXES,
                'questionText' => [
                    'en' => 'Ethnicity'
                ],
                'questionDescription' => [
                    'en' => "What are your ethnic origins or ancestry? \
                    Please select ALL the geographic areas from which your family's ancestors first originated:"
                ],
                'responseOptions' => [
                    [
                        'optionText' => [
                            'en' => 'Western Europe (e.g. Greece, Sweden, United Kingdom)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Eastern Europe (e.g. Hungary, Poland, Russia)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'North Africa (e.g. Egypt, Morocco, Sudan)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Sub-Saharan Africa (e.g. Kenya, Nigeria, South Africa)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'West Asia / Middle East (e.g. Iran, Israel, Saudi Arabia)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'South and Southeast Asia (e.g. India, Indonesia, Singapore)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Prefer not to disclose'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Self describe'
                        ],
                        'hasInputField' => true
                    ]
                ]
            ]
        ];
    }
}
