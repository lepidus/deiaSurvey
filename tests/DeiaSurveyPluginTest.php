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

        foreach (['deiaToken', 'deiaTokenExpiresAt', 'deiaTokenEmail'] as $field) {
            self::assertTrue($schema->properties->{$field}->writeOnly);
            self::assertFalse($schema->properties->{$field}->apiSummary);

            $errors = [];
            $plugin->preventDeiaTokenApiWrite('Author::validate', [&$errors, null, [$field => 'attacker']]);
            self::assertArrayHasKey($field, $errors);
        }
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

    public function testExternalDeletionConfirmationUsesPostAndCsrf(): void
    {
        $template = file_get_contents(__DIR__ . '/../templates/questionnairePage/deleteData.tpl');

        self::assertStringContainsString('<form', $template);
        self::assertStringContainsString('method="post"', $template);
        self::assertStringContainsString('{csrf}', $template);
        self::assertStringNotContainsString('save=true', $template);
    }

    public function testPublicQuestionnaireEscapesPlainTextContent(): void
    {
        $question = file_get_contents(__DIR__ . '/../templates/questionnairePage/question.tpl');
        $responses = file_get_contents(__DIR__ . '/../templates/questionnairePage/responses.tpl');
        $index = file_get_contents(__DIR__ . '/../templates/questionnairePage/index.tpl');

        self::assertStringContainsString("{\$question['title']|escape}", $question);
        self::assertStringContainsString("{\$question['description']|escape}", $question);
        self::assertStringContainsString('{$responseOption->getLocalizedOptionText()|escape}', $question);
        self::assertStringContainsString("{\$responses[\$question['id']]|escape}", $responses);
        self::assertStringContainsString("{\$questionBlock['description']|escape}", $index);
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
