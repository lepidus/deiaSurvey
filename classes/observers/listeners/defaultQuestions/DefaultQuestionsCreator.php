<?php

namespace APP\plugins\generic\deiaSurvey\classes\observers\listeners\defaultQuestions;

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

        if ($demographicQuestionsCount == 0) {
            error_log('Creating default demographic questions for context ID: ' . $contextId);
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
                    'en' => 'Gender',
                    'es' => 'Género',
                    'pt_BR' => 'Gênero'
                ],
                'questionDescription' => [
                    'en' => 'With which gender do you most identify? Please select one option:',
                    'es' => '¿Con qué género te identificas más? Por favor, selecciona una opción:',
                    'pt_BR' => 'Com qual gênero você mais se identifica? Por favor, selecione uma opção:'
                ],
                'responseOptions' => [
                    [
                        'optionText' => [
                            'en' => 'Woman',
                            'es' => 'Mujer',
                            'pt_BR' => 'Mulher'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Man',
                            'es' => 'Hombre',
                            'pt_BR' => 'Homem'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Non-binary or gender diverse',
                            'es' => 'No binario o género diverso',
                            'pt_BR' => 'Não binário ou gênero diverso'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Prefer not to inform',
                            'es' => 'Prefiero no informar',
                            'pt_BR' => 'Prefiro não informar'
                        ],
                        'hasInputField' => false
                    ]
                ]
            ],
            [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_CHECKBOXES,
                'questionText' => [
                    'en' => 'Race',
                    'es' => 'Raza',
                    'pt_BR' => 'Raça'
                ],
                'questionDescription' => [
                    'en' => 'How would you identify yourself in terms of race? Please select ALL the groups that apply to you:',
                    'es' => '¿Cómo te identificas en términos de raza? Por favor, selecciona TODOS los grupos que correspondan:',
                    'pt_BR' => 'Como você se identifica em termos de raça? Por favor, selecione TODOS os grupos que se aplicam a você:'
                ],
                'responseOptions' => [
                    [
                        'optionText' => [
                            'en' => 'Asian or Pacific Islander',
                            'es' => 'Asiático o Isleño del Pacífico',
                            'pt_BR' => 'Asiático ou Ilhéu do Pacífico'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Black',
                            'es' => 'Negro/a',
                            'pt_BR' => 'Negro(a)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Hispanic or Latino/a/x',
                            'es' => 'Hispano/a o Latino/a',
                            'pt_BR' => 'Hispano(a) ou Latino(a)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => "Indigenous (e.g. North American Indian Navajo,
                                South American Indian Quechua, Aboriginal or Torres Strait Islander)",
                            'es' => "Indígena (e.g. Indio Navajo de América del Norte,
                                Indio Quechua de América del Sur, Aborigen o Isleño del Estrecho de Torres)",
                            'pt_BR' => "Indígena (e.g. Indígena Navajo da América do Norte,
                                Indígena Quechua da América do Sul, Aborígene ou Ilhéu do Estreito de Torres)"
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Middle Eastern or North African',
                            'es' => 'Medio Oriente o Norte de África',
                            'pt_BR' => 'Oriente Médio ou Norte da África'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'White',
                            'es' => 'Blanco/a',
                            'pt_BR' => 'Branco(a)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Prefer not to inform',
                            'es' => 'Prefiero no informar',
                            'pt_BR' => 'Prefiro não informar'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Self describe',
                            'es' => 'Autodescripción',
                            'pt_BR' => 'Auto-descrição'
                        ],
                        'hasInputField' => true
                    ]
                ]
            ],
            [
                'contextId' => $contextId,
                'questionType' => DemographicQuestion::TYPE_CHECKBOXES,
                'questionText' => [
                    'en' => 'Ethnicity',
                    'es' => 'Etnicidad',
                    'pt_BR' => 'Etnia'
                ],
                'questionDescription' => [
                    'en' => "What are your ethnic origins or ancestry? Please select ALL the
                        geographic areas from which your family's ancestors first originated:",
                    'es' => "¿Cuáles son tus orígenes étnicos o ascendencia? Por favor, selecciona TODAS las
                        áreas geográficas de donde provienen los antepasados de tu familia:",
                    'pt_BR' => "Quais são suas origens étnicas ou ancestrais? Por favor, selecione TODAS as
                        áreas geográficas de onde os antepassados de sua família vieram:"
                ],
                'responseOptions' => [
                    [
                        'optionText' => [
                            'en' => 'Western Europe (e.g. Greece, Sweden, United Kingdom)',
                            'es' => 'Europa Occidental (e.g. Grecia, Suecia, Reino Unido)',
                            'pt_BR' => 'Europa Ocidental (e.g. Grécia, Suécia, Reino Unido)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Eastern Europe (e.g. Hungary, Poland, Russia)',
                            'es' => 'Europa Oriental (e.g. Hungría, Polonia, Rusia)',
                            'pt_BR' => 'Europa Oriental (e.g. Hungria, Polônia, Rússia)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'North Africa (e.g. Egypt, Morocco, Sudan)',
                            'es' => 'Norte de África (e.g. Egipto, Marruecos, Sudán)',
                            'pt_BR' => 'Norte da África (e.g. Egito, Marrocos, Sudão)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Sub-Saharan Africa (e.g. Kenya, Nigeria, South Africa)',
                            'es' => 'África Subsahariana (e.g. Kenia, Nigeria, Sudáfrica)',
                            'pt_BR' => 'África Subsaariana (e.g. Quênia, Nigéria, África do Sul)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'West Asia / Middle East (e.g. Iran, Israel, Saudi Arabia)',
                            'es' => 'Asia Occidental / Medio Oriente (e.g. Irán, Israel, Arabia Saudita)',
                            'pt_BR' => 'Ásia Ocidental / Oriente Médio (e.g. Irã, Israel, Arábia Saudita)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'South and Southeast Asia (e.g. India, Indonesia, Singapore)',
                            'es' => 'Asia del Sur y Sudeste (e.g. India, Indonesia, Singapur)',
                            'pt_BR' => 'Sul e Sudeste da Ásia (e.g. Índia, Indonésia, Singapura)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'East and Central Asia (e.g. China, Japan, Uzbekistan)',
                            'es' => 'Asia Oriental y Central (e.g. China, Japón, Uzbekistán)',
                            'pt_BR' => 'Ásia Oriental e Central (e.g. China, Japão, Uzbequistão)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Pacific / Oceania (e.g. Australia, Papua New Guinea, Fiji)',
                            'es' => 'Pacífico / Oceanía (e.g. Australia, Papúa Nueva Guinea, Fiyi)',
                            'pt_BR' => 'Pacífico / Oceania (e.g. Austrália, Papua Nova Guiné, Fiji)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'North America (Canada, United States)',
                            'es' => 'América del Norte (Canadá, Estados Unidos)',
                            'pt_BR' => 'América do Norte (Canadá, Estados Unidos)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Central America and Caribbean (e.g. Jamaica, Mexico, Panama)',
                            'es' => 'América Central y el Caribe (e.g. Jamaica, México, Panamá)',
                            'pt_BR' => 'América Central e Caribe (e.g. Jamaica, México, Panamá)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'South America (e.g. Brazil, Chile, Colombia)',
                            'es' => 'América del Sur (e.g. Brasil, Chile, Colombia)',
                            'pt_BR' => 'América do Sul (e.g. Brasil, Chile, Colômbia)'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Prefer not to inform',
                            'es' => 'Prefiero no informar',
                            'pt_BR' => 'Prefiro não informar'
                        ],
                        'hasInputField' => false
                    ],
                    [
                        'optionText' => [
                            'en' => 'Self describe',
                            'es' => 'Autodescripción',
                            'pt_BR' => 'Auto-descrição'
                        ],
                        'hasInputField' => true
                    ]
                ]
            ]
        ];
    }
}
