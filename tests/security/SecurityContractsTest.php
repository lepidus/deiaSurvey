<?php

namespace APP\plugins\generic\deiaSurvey\tests\security;

use PHPUnit\Framework\TestCase;

class SecurityContractsTest extends TestCase
{
    public function testProfileFormRespectsParentValidation(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/classes/form/QuestionsForm.php');

        self::assertStringContainsString('parent::validate($callHooks)', $source);
    }

    public function testExternalQuestionnaireRequiresPostAndCsrf(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/pages/deia/QuestionnaireHandler.php');

        self::assertStringContainsString('$request->isPost()', $source);
        self::assertStringContainsString('$request->checkCSRF()', $source);
    }

    public function testDeleteConfirmationPostsWithCsrf(): void
    {
        $template = file_get_contents(dirname(__DIR__, 2) . '/templates/questionnairePage/deleteData.tpl');

        self::assertStringContainsString('method="post"', $template);
        self::assertStringContainsString('{csrf}', $template);
        self::assertStringNotContainsString('save=true', $template);
    }

    public function testHandlerScopesAuthorsToTheCurrentContext(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/pages/deia/QuestionnaireHandler.php');

        self::assertStringContainsString('authorBelongsToContext', $source);
    }

    public function testPublicQuestionnaireEscapesTextAndAttributes(): void
    {
        $question = file_get_contents(dirname(__DIR__, 2) . '/templates/questionnairePage/question.tpl');
        $responses = file_get_contents(dirname(__DIR__, 2) . '/templates/questionnairePage/responses.tpl');

        self::assertStringContainsString('{$question[\'title\']|escape}', $question);
        self::assertStringContainsString('{$question[\'description\']|escape}', $question);
        self::assertStringContainsString('getLocalizedOptionText()|escape', $question);
        self::assertStringContainsString('{$responses[$question[\'questionId\']]|escape}', $responses);
    }
}
