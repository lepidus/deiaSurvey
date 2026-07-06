<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use Illuminate\Support\Facades\DB;
use PKP\tests\DatabaseTestCase;

class DeiaSurveyPluginTest extends DatabaseTestCase
{
    use TestHelperTrait;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'deia_question_blocks',
            'deia_question_block_settings',
            'user_settings',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->addSchemaFile('deiaQuestionBlock');
        DB::table('deia_question_block_settings')->delete();
        DB::table('deia_question_blocks')->delete();
        DB::table('user_settings')->delete();
    }

    public function testDoesNotRedirectUserWhenThereAreNoActiveQuestionBlocks(): void
    {
        $plugin = new DeiaSurveyPlugin();

        self::assertFalse($plugin->userShouldBeRedirected($this->createRequestStub()));
    }

    private function createRequestStub(): object
    {
        return new class () {
            public function getContext(): object
            {
                return new class () {
                    public function getId(): int
                    {
                        return 1;
                    }
                };
            }

            public function getUser(): object
            {
                return new class () {
                    public function getId(): int
                    {
                        return 1;
                    }
                };
            }
        };
    }
}
