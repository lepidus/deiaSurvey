<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaQuestionBlock;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\importExport\DeiaQuestionBlockJsonImporter;
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
                'schemaVersion' => '1.0',
                'plugin' => 'otherPlugin',
                'blocks' => [],
            ]),
            $this->contextId
        );
    }

    public function testImportsBlockAsInactive(): void
    {
        $importedIds = (new DeiaQuestionBlockJsonImporter())->import(
            json_encode([
                'schemaVersion' => '1.0',
                'plugin' => 'deiaSurvey',
                'blocks' => [
                    [
                        'id' => 999,
                        'contextId' => 999,
                        'title' => ['en' => 'Funding DEIA questions'],
                        'description' => ['en' => 'Questions about funding access.'],
                        'active' => true,
                        'sequence' => 1,
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
                'schemaVersion' => '1.0',
                'plugin' => 'deiaSurvey',
                'blocks' => [
                    [
                        'title' => ['en' => 'Funding DEIA questions'],
                        'description' => ['en' => 'Questions about funding access.'],
                        'sequence' => 1,
                        'questions' => [
                            [
                                'id' => 777,
                                'questionType' => DeiaQuestion::TYPE_CHECKBOXES,
                                'questionText' => ['en' => 'Which funding sources apply?'],
                                'questionDescription' => ['en' => 'Select all that apply.'],
                                'sequence' => 1,
                                'responseOptions' => [
                                    [
                                        'id' => 888,
                                        'optionText' => ['en' => 'Grant funding'],
                                        'hasInputField' => false,
                                        'sequence' => 1,
                                    ],
                                    [
                                        'id' => 889,
                                        'optionText' => ['en' => 'Other'],
                                        'hasInputField' => true,
                                        'sequence' => 2,
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
                'schemaVersion' => '1.0',
                'plugin' => 'deiaSurvey',
                'blocks' => [
                    [
                        'title' => ['en' => 'Funding DEIA questions'],
                        'sequence' => 1,
                        'questions' => [
                            [
                                'questionType' => DeiaQuestion::TYPE_TEXT_FIELD,
                                'questionText' => 'plugins.generic.deiaSurvey.defaultQuestion',
                                'questionDescription' => '',
                                'sequence' => 1,
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

    public function testRejectsUnknownQuestionType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DeiaQuestionBlockJsonImporter())->import(
            json_encode([
                'schemaVersion' => '1.0',
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
