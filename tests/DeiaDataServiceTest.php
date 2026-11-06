<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once(__DIR__ . '/../autoload.php');
require_once(__DIR__ . '/helpers/TestHelperTrait.php');
require_once(__DIR__ . '/../classes/DeiaDataService.php');

use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use Illuminate\Database\Capsule\Manager as DB;

import('lib.pkp.tests.DatabaseTestCase');

class DeiaDataServiceTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    protected function getAffectedTables(): array
    {
        return $this->affectedTables;
    }

    protected function setUp(): void
    {
        $this->setAffectedTables([
            'deia_question_block_settings',
            'deia_question_blocks',
            'deia_questions',
            'deia_question_settings',
            'deia_response_options',
            'deia_response_option_settings',
            'deia_responses',
            'deia_response_settings',
        ]);
        parent::setUp();
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponseOption');
        $this->addSchemaFile('deiaResponse');
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);
        parent::tearDown();
    }

    public function testRejectsClientSuppliedQuestionType(): void
    {
        $questionId = $this->createDeiaQuestion();
        $service = new DeiaDataService();

        $this->expectException(\InvalidArgumentException::class);
        $service->normalizeResponses(
            1,
            ['question-' . $questionId . '-checkbox' => ['1']],
            [],
            true
        );
    }

    public function testResubmittingExternalResponsesReplacesPreviousAnswers(): void
    {
        $questionId = $this->createDeiaQuestion();
        $service = new DeiaDataService();
        $field = 'question-' . $questionId . '-textarea';

        $service->replaceExternalAuthorResponses(1, 'author@example.org', 'email', [$field => 'first'], []);
        $service->replaceExternalAuthorResponses(1, 'author@example.org', 'email', [$field => 'second'], []);

        $responses = $service->getExternalAuthorResponses(1, 'author@example.org', 'email');
        self::assertSame(1, $this->countExternalResponses('author@example.org'));
        self::assertSame('second', $responses[$questionId]);
    }

    public function testInvalidResubmissionKeepsPreviousAnswers(): void
    {
        $questionId = $this->createDeiaQuestion();
        $service = new DeiaDataService();
        $service->replaceExternalAuthorResponses(
            1,
            'author@example.org',
            'email',
            ['question-' . $questionId . '-textarea' => 'first'],
            []
        );

        try {
            $service->replaceExternalAuthorResponses(
                1,
                'author@example.org',
                'email',
                ['question-' . $questionId . '-checkbox' => ['1']],
                []
            );
            self::fail('Malformed resubmission must be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame(1, $this->countExternalResponses('author@example.org'));
        }
    }

    private function countExternalResponses(string $externalId): int
    {
        return DB::table('deia_responses')
            ->where('external_id', $externalId)
            ->where('external_type', 'email')
            ->count();
    }
}
