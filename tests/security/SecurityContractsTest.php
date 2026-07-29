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
}
