<?php

namespace APP\plugins\generic\deiaSurvey\tests\helpers;

use Illuminate\Support\Facades\DB;

class FixtureIsolationTest extends DatabaseTestCase
{
    use TestHelperTrait;

    public function testDatabaseFixturesDoNotReuseExistingContextsOrUsers(): void
    {
        $contextIds = DB::table('journals')->pluck('journal_id')->map(fn ($id) => (int) $id)->all();
        $userIds = DB::table('users')->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        self::assertNotContains($this->createTestContext(), $contextIds);
        self::assertNotContains($this->createTestUser(), $userIds);
    }

    public function testMigrationTablesDoNotExposeExistingQuestionnaireData(): void
    {
        $blockId = DB::table('deia_question_blocks')->insertGetId([
            'context_id' => $this->createTestContext(),
            'is_active' => 1,
        ]);
        $questionId = DB::table('deia_questions')->insertGetId([
            'context_id' => $this->createTestContext(),
            'deia_question_block_id' => $blockId,
            'question_type' => 1,
        ]);
        DB::table('deia_responses')->insert([
            'deia_question_id' => $questionId,
            'external_id' => 'control@example.org',
            'external_type' => 'email',
        ]);

        $this->isolateDeiaTables();

        self::assertSame(0, DB::table('deia_question_blocks')->count());
        self::assertSame(0, DB::table('deia_questions')->count());
        self::assertSame(0, DB::table('deia_responses')->count());
    }

    public function testCleanupAllowsAnAlreadyRemovedTemporaryTable(): void
    {
        $this->isolateDeiaTables();
        DB::statement('DROP TEMPORARY TABLE deia_responses');

        self::assertSame(0, DB::table('deia_questions')->count());
    }
}
