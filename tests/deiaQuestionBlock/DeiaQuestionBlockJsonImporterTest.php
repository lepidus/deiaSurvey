<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaQuestionBlock;

require_once(dirname(__DIR__, 2) . '/autoload.php');
require_once(dirname(__DIR__) . '/helpers/TestHelperTrait.php');

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\importExport\DeiaQuestionBlockJsonImporter;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use InvalidArgumentException;

import('lib.pkp.tests.DatabaseTestCase');

class DeiaQuestionBlockJsonImporterTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $contextId;

    protected function getAffectedTables(): array
    {
        return $this->affectedTables;
    }

    protected function setUp(): void
    {
        $this->setAffectedTables([
            'deia_question_blocks',
            'deia_question_block_settings',
            'deia_questions',
            'deia_question_settings',
            'deia_response_options',
            'deia_response_option_settings',
        ]);

        parent::setUp();

        $this->contextId = $this->createJournalMock();
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponseOption');
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
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

        $options = array_values(Repo::deiaResponseOption()->getCollector()
            ->filterByQuestionIds([$question->getId()])
            ->getMany()
            ->toArray());

        self::assertCount(2, $options);
        self::assertSame(['en' => 'Grant funding'], $options[0]->getData('optionText'));
        self::assertFalse($options[0]->hasInputField());
        self::assertSame(['en' => 'Other'], $options[1]->getData('optionText'));
        self::assertTrue($options[1]->hasInputField());
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
