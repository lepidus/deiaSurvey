<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\core\Application;
use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;

class DefaultQuestionsCreator
{
    public function createDefaultQuestions($contextId)
    {
        $demographicQuestionsCount = Repo::demographicQuestion()
            ->getCollector()
            ->filterByContextIds([$contextId])
            ->getCount();

        if ($demographicQuestionsCount === 0) {
            $defaultTestQuestions = $this->getDefaultQuestionsData($contextId);

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

    public function getDefaultQuestionsData(int $contextId): array
    {
        return [
            'gender' => [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_RADIO_BUTTONS,
                'isDefaultQuestion' => true,
                'isTranslated' => false,
                'questionText' => 'plugins.generic.deiaSurvey.defaultQuestion.gender.title',
                'questionDescription' => 'plugins.generic.deiaSurvey.defaultQuestion.gender.description',
                'responseOptions' => [
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.gender.responseOption.woman',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.gender.responseOption.man',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.gender.responseOption.nonBinary',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.gender.responseOption.preferNotToInform',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ]
                ]
            ],
            'race' => [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_CHECKBOXES,
                'isDefaultQuestion' => true,
                'isTranslated' => false,
                'questionText' => 'plugins.generic.deiaSurvey.defaultQuestion.race.title',
                'questionDescription' => 'plugins.generic.deiaSurvey.defaultQuestion.race.description',
                'responseOptions' => [
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.race.responseOption.asianPacificIslander',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.race.responseOption.black',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.race.responseOption.hispanicLatino',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.race.responseOption.indigenous',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.race.responseOption.middleEasternNorthAfrican',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.race.responseOption.white',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.race.responseOption.preferNotToInform',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.race.responseOption.selfDescribe',
                        'isTranslated' => false,
                        'hasInputField' => true
                    ]
                ]
            ],
            'ethnicity' => [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_CHECKBOXES,
                'isDefaultQuestion' => true,
                'isTranslated' => false,
                'questionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.title',
                'questionDescription' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.description',
                'responseOptions' => [
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.responseOption.westernEurope',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.responseOption.easternEurope',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.responseOption.northAfrica',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.responseOption.subSaharanAfrica',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.responseOption.westAsiaMiddleEast',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.responseOption.southSoutheastAsia',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.responseOption.eastCentralAsia',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.responseOption.pacificOceania',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.responseOption.northAmerica',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.responseOption.centralAmericaCaribbean',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.responseOption.southAmerica',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.responseOption.preferNotToInform',
                        'isTranslated' => false,
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => 'plugins.generic.deiaSurvey.defaultQuestion.ethnicity.responseOption.selfDescribe',
                        'isTranslated' => false,
                        'hasInputField' => true
                    ]
                ]
            ]
        ];
    }
}
