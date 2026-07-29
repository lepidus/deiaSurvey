<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once(__DIR__ . '/../autoload.php');
require_once(__DIR__ . '/helpers/TestHelperTrait.php');

use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.plugins.GenericPlugin');
require_once(__DIR__ . '/../DeiaSurveyPlugin.php');

class DeiaSurveyPluginTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    protected function getAffectedTables(): array
    {
        return $this->affectedTables;
    }

    protected function setUp(): void
    {
        $this->setAffectedTables([
            'deia_question_blocks',
            'deia_question_block_settings',
            'user_settings',
        ]);

        parent::setUp();

        $this->addSchemaFile('deiaQuestionBlock');
        $this->deleteQuestionBlocksAndConsent();
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
    }

    public function testDoesNotRedirectUserWhenThereAreNoActiveQuestionBlocks(): void
    {
        $plugin = new \DeiaSurveyPlugin();

        self::assertFalse($plugin->userShouldBeRedirected($this->createRequestStub()));
    }

    public function testAuthorTokenSchemaIsNeverReadableOrWritableThroughApi(): void
    {
        $schema = (object) ['properties' => (object) []];
        $plugin = new \DeiaSurveyPlugin();

        $plugin->editAuthorSchema('Schema::get::author', [&$schema]);

        self::assertTrue($schema->properties->deiaToken->writeOnly);
        self::assertFalse(property_exists($schema->properties->deiaToken, 'readOnly'));
        self::assertObjectNotHasAttribute('apiSummary', $schema->properties->deiaToken);

        $errors = [];
        $plugin->preventDeiaTokenApiWrite('Author::validate', [&$errors, null, ['deiaToken' => 'attacker']]);
        self::assertArrayHasKey('deiaToken', $errors);
    }

    public function testQuestionsFormPreservesParentValidationContract(): void
    {
        $source = file_get_contents(__DIR__ . '/../classes/form/QuestionsForm.php');

        self::assertStringContainsString('parent::validate($callHooks)', $source);
        self::assertMatchesRegularExpression(
            '/if \(!parent::validate\(\$callHooks\)\) \{\s+return false;\s+\}/',
            $source
        );
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

                    public function getRoles($contextId): array
                    {
                        return [
                            new class () {
                                public function getRoleId(): int
                                {
                                    return ROLE_ID_MANAGER;
                                }
                            },
                        ];
                    }
                };
            }
        };
    }

    private function deleteQuestionBlocksAndConsent(): void
    {
        $dao = new \DAO();
        $dao->update('DELETE FROM deia_question_block_settings', [], true, false);
        $dao->update('DELETE FROM deia_question_blocks', [], true, false);
        $dao->update("DELETE FROM user_settings WHERE setting_name = 'deiaDataConsent'", [], true, false);
    }
}
