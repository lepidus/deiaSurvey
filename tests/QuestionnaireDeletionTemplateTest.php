<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use PHPUnit\Framework\TestCase;

class QuestionnaireDeletionTemplateTest extends TestCase
{
    public function testConfirmationUsesPostAndCsrfWithoutGetDeleteFlag(): void
    {
        $template = file_get_contents(dirname(__DIR__) . '/templates/questionnairePage/deleteData.tpl');

        self::assertStringContainsString('<form method="post"', $template);
        self::assertStringContainsString('{csrf}', $template);
        self::assertStringContainsString('name="confirm" value="1"', $template);
        self::assertStringNotContainsString('save=true', $template);
    }
}
