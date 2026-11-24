<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaQuestionBlock;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\importExport\DeiaQuestionBlockJsonImporter;
use APP\plugins\generic\deiaSurvey\classes\importExport\DeiaQuestionBlockJsonSerializer;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use InvalidArgumentException;
use PKP\facades\Locale;
use PKP\tests\DatabaseTestCase;

class DeiaQuestionBlockJsonImporterTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private int $contextId;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'deia_question_blocks',
            'deia_question_block_settings',
            'deia_questions',
            'deia_question_settings',
            'deia_response_options',
            'deia_response_option_settings',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->contextId = $this->createJournalMock();
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponseOption');
    }

    public function testRejectsInvalidJson(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DeiaQuestionBlockJsonImporter())->import('{', $this->contextId);
    }

    public function testRejectsFileFromDifferentPlugin(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DeiaQuestionBlockJsonImporter())->import(
            json_encode([
                'plugin' => 'otherPlugin',
                'blocks' => [],
            ]),
            $this->contextId
        );
    }

    public function testRejectsSchemaVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DeiaQuestionBlockJsonImporter())->import(
            json_encode([
                'schemaVersion' => '1.0',
                'plugin' => 'deiaSurvey',
                'blocks' => [],
            ]),
            $this->contextId
        );
    }

    public function testImportsBlockAsInactive(): void
    {
        $importedIds = (new DeiaQuestionBlockJsonImporter())->import(
            json_encode([
                'plugin' => 'deiaSurvey',
                'blocks' => [
                    [
                        'title' => ['en' => 'Funding DEIA questions'],
                        'description' => ['en' => 'Questions about funding access.'],
                        'active' => true,
                        'questions' => [],
                    ],
                ],
            ]),
            $this->contextId
        );

        $block = Repo::deiaQuestionBlock()->get($importedIds[0], $this->contextId);

        self::assertNotNull($block);
        self::assertSame(['en' => 'Funding DEIA questions'], $block->getData('title'));
        self::assertSame(['en' => 'Questions about funding access.'], $block->getData('description'));
        self::assertSame(0, $block->getActive());
        self::assertSame($this->contextId, $block->getContextId());
    }

    public function testImportsQuestionsAndResponseOptions(): void
    {
        $importedIds = (new DeiaQuestionBlockJsonImporter())->import(
            json_encode([
                'plugin' => 'deiaSurvey',
                'blocks' => [
                    [
                        'title' => ['en' => 'Funding DEIA questions'],
                        'description' => ['en' => 'Questions about funding access.'],
                        'questions' => [
                            [
                                'questionType' => 'TYPE_CHECKBOXES',
                                'questionText' => ['en' => 'Which funding sources apply?'],
                                'questionDescription' => ['en' => 'Select all that apply.'],
                                'responseOptions' => [
                                    [
                                        'optionText' => ['en' => 'Grant funding'],
                                        'hasInputField' => false,
                                    ],
                                    [
                                        'optionText' => ['en' => 'Other'],
                                        'hasInputField' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            $this->contextId
        );

        $questions = Repo::deiaQuestion()->getCollector()
            ->filterByContextIds([$this->contextId])
            ->filterByQuestionBlockIds([$importedIds[0]])
            ->getMany()
            ->toArray();
        $question = array_shift($questions);

        self::assertSame(DeiaQuestion::TYPE_CHECKBOXES, $question->getQuestionType());
        self::assertSame(['en' => 'Which funding sources apply?'], $question->getData('questionText'));
        self::assertSame(['en' => 'Select all that apply.'], $question->getData('questionDescription'));

        $options = Repo::deiaResponseOption()->getCollector()
            ->filterByQuestionIds([$question->getId()])
            ->getMany()
            ->values()
            ->toArray();

        self::assertCount(2, $options);
        self::assertSame(['en' => 'Grant funding'], $options[0]->getData('optionText'));
        self::assertFalse($options[0]->hasInputField());
        self::assertSame(['en' => 'Other'], $options[1]->getData('optionText'));
        self::assertTrue($options[1]->hasInputField());
    }

    public function testImportsQuestionTextAsString(): void
    {
        $importedIds = (new DeiaQuestionBlockJsonImporter())->import(
            json_encode([
                'plugin' => 'deiaSurvey',
                'blocks' => [
                    [
                        'title' => ['en' => 'Funding DEIA questions'],
                        'questions' => [
                            [
                                'questionType' => 'TYPE_TEXT_FIELD',
                                'questionText' => 'plugins.generic.deiaSurvey.defaultQuestion',
                                'questionDescription' => '',
                            ],
                        ],
                    ],
                ],
            ]),
            $this->contextId
        );

        $questions = Repo::deiaQuestion()->getCollector()
            ->filterByContextIds([$this->contextId])
            ->filterByQuestionBlockIds([$importedIds[0]])
            ->getMany()
            ->toArray();
        $question = array_shift($questions);

        self::assertSame(
            [Locale::getLocale() => 'plugins.generic.deiaSurvey.defaultQuestion'],
            $question->getData('questionText')
        );
        self::assertTrue($question->isTranslated());
    }

    /** @dataProvider conditionSourceTypes */
    public function testRemapsConditionalQuestionsWhenRoundTrippingBlocks(string $sourceType): void
    {
        $payload = [
            'plugin' => 'deiaSurvey',
            'blocks' => [[
                'title' => ['en' => 'Residence'],
                'questions' => [
                    [
                        'questionType' => $sourceType,
                        'questionText' => ['en' => 'Do you live in Brazil?'],
                        'responseOptions' => [
                            ['optionText' => ['en' => 'No']],
                            ['optionText' => ['en' => 'Yes']],
                        ],
                    ],
                    [
                        'questionType' => 'TYPE_TEXT_FIELD',
                        'questionText' => ['en' => 'Which state?'],
                        'showWhen' => [0, 1],
                    ],
                ],
            ]],
        ];
        $importer = new DeiaQuestionBlockJsonImporter();
        [$blockId] = $importer->import(json_encode($payload), $this->contextId);
        $block = Repo::deiaQuestionBlock()->get($blockId, $this->contextId);
        $questions = Repo::deiaQuestion()->getBlockQuestions($this->contextId, $blockId);
        $block->setData('questions', $questions);
        [$source] = array_values($questions);
        $source->setData('responseOptions', $source->getResponseOptions());

        $exported = (new DeiaQuestionBlockJsonSerializer())->serializeBlocks([$block]);
        self::assertSame([0, 1], $exported['blocks'][0]['questions'][1]['showWhen']);

        [$newBlockId] = $importer->import(json_encode($exported), $this->contextId);
        [$newSource, $newDependent] = array_values(
            Repo::deiaQuestion()->getBlockQuestions($this->contextId, $newBlockId)
        );
        [, $newOption] = array_values($newSource->getResponseOptions());

        self::assertNotSame($source->getId(), $newSource->getId());
        self::assertSame([$newSource->getId(), $newOption->getId()], $newDependent->getData('showWhen'));
    }

    public static function conditionSourceTypes(): array
    {
        return [
            'radio buttons' => ['TYPE_RADIO_BUTTONS'],
            'checkboxes' => ['TYPE_CHECKBOXES'],
        ];
    }

    public function testRejectsConditionThatPointsToLaterQuestion(): void
    {
        $blockCount = Repo::deiaQuestionBlock()->getCollector()->filterByContextIds([$this->contextId])->getCount();
        try {
            (new DeiaQuestionBlockJsonImporter())->import(json_encode([
                'plugin' => 'deiaSurvey',
                'blocks' => [[
                    'title' => ['en' => 'Residence'],
                    'questions' => [[
                        'questionType' => 'TYPE_TEXT_FIELD',
                        'questionText' => ['en' => 'Which state?'],
                        'showWhen' => [1, 0],
                    ], [
                        'questionType' => 'TYPE_RADIO_BUTTONS',
                        'questionText' => ['en' => 'Do you live in Brazil?'],
                        'responseOptions' => [['optionText' => ['en' => 'Yes']]],
                    ]],
                ]],
            ]), $this->contextId);
            self::fail('A later question cannot be a condition source.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Invalid question display condition.', $exception->getMessage());
        }
        self::assertSame(
            $blockCount,
            Repo::deiaQuestionBlock()->getCollector()->filterByContextIds([$this->contextId])->getCount()
        );
    }

    public function testRejectsUnknownQuestionType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DeiaQuestionBlockJsonImporter())->import(
            json_encode([
                'plugin' => 'deiaSurvey',
                'blocks' => [
                    [
                        'title' => ['en' => 'Funding DEIA questions'],
                        'description' => ['en' => 'Questions about funding access.'],
                        'questions' => [
                            [
                                'questionType' => 999,
                                'questionText' => ['en' => 'Unknown question'],
                                'questionDescription' => ['en' => 'Unknown description'],
                            ],
                        ],
                    ],
                ],
            ]),
            $this->contextId
        );
    }

}
