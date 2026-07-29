<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use PHPUnit\Framework\TestCase;

class QuestionnaireEscapingTest extends TestCase
{
    public function testPublicQuestionnaireEscapesTextAndAttributeSinksExactlyOnce(): void
    {
        $question = file_get_contents(dirname(__DIR__) . '/templates/questionnairePage/question.tpl');
        $responses = file_get_contents(dirname(__DIR__) . '/templates/questionnairePage/responses.tpl');

        foreach ([
            "{\$question['title']|escape}",
            "{\$question['description']|escape}",
            '{$responseOption->getLocalizedOptionText()|escape}',
            '{$optionInputName|escape}',
        ] as $escapedSink) {
            self::assertStringContainsString($escapedSink, $question);
        }

        self::assertStringContainsString("{\$responses[\$question['questionId']]|escape}", $responses);
        self::assertStringNotContainsString('|escape|escape', $question . $responses);
    }
}
