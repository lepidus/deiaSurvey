<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;

class DefaultQuestionsCreator
{
    public function createDefaultQuestions($contextId)
    {
        $deiaQuestionsCount = Repo::deiaQuestion()
            ->getCollector()
            ->filterByContextIds([$contextId])
            ->getCount();

        if ($deiaQuestionsCount === 0) {
            $questionBlock = Repo::deiaQuestionBlock()->newDataObject([
                'contextId' => $contextId,
                'title' => [
                    'en_US' => 'SciELO Questions',
                    'pt_BR' => 'Perguntas SciELO',
                    'es_ES' => 'Preguntas SciELO',
                ],
                'description' => [
                    'en_US' => 'Standard SciELO questions for collecting author demographic and identity data.',
                    'pt_BR' => 'Perguntas padrão SciELO para coletar dados demográficos e identitários de autores.',
                    'es_ES' => 'Preguntas estándar SciELO para recopilar datos demográficos e identitarios de autores.',
                ],
                'active' => 1,
                'sequence' => 1
            ]);
            $questionBlockId = Repo::deiaQuestionBlock()->add($questionBlock);

            $defaultTestQuestions = $this->getDefaultQuestionsData($contextId);
            $sequence = 0;

            foreach ($defaultTestQuestions as $questionData) {
                $questionData['questionBlockId'] = $questionBlockId;
                $questionData['sequence'] = ++$sequence;
                $questionObject = Repo::deiaQuestion()->newDataObject($questionData);
                $deiaQuestionId = Repo::deiaQuestion()->add($questionObject);

                if (isset($questionData['responseOptions'])) {
                    $optionSequence = 0;
                    foreach ($questionData['responseOptions'] as $optionData) {
                        $optionData['deiaQuestionId'] = $deiaQuestionId;
                        $optionData['sequence'] = ++$optionSequence;
                        $responseOptionObject = Repo::deiaResponseOption()->newDataObject($optionData);
                        Repo::deiaResponseOption()->add($responseOptionObject);
                    }
                }
            }
        }
    }

    public static function getDefaultQuestionsData(int $contextId): array
    {
        return [
            'gender' => [
                'contextId' => $contextId,
                'questionType' => DeiaQuestion::TYPE_RADIO_BUTTONS,
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
                'questionType' => DeiaQuestion::TYPE_CHECKBOXES,
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
                'questionType' => DeiaQuestion::TYPE_CHECKBOXES,
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
