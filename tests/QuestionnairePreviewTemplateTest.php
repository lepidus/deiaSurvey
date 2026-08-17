<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use PHPUnit\Framework\TestCase;

class QuestionnairePreviewTemplateTest extends TestCase
{
    public function testProfileAndPreviewShareQuestionBlocksMarkup(): void
    {
        $templatesDirectory = dirname(__DIR__) . '/templates';
        $profile = file_get_contents($templatesDirectory . '/questionsInProfile.tpl');
        $preview = file_get_contents($templatesDirectory . '/deiaQuestionBlocks/previewQuestionnaire.tpl');
        $questionBlocks = file_get_contents($templatesDirectory . '/questionBlocks.tpl');

        self::assertStringContainsString('class="pkp_form deiaQuestionnaire"', $profile);
        self::assertStringContainsString('templates/questionBlocks.tpl', $profile);
        self::assertStringContainsString('id="deiaQuestionnairePreview"', $preview);
        self::assertStringContainsString('class="pkp_form deiaQuestionnaire"', $preview);
        self::assertStringContainsString('styles/questionsInProfile.css', $preview);
        self::assertStringContainsString('templates/questionBlocks.tpl', $preview);
        self::assertStringContainsString('{foreach $questionBlocks as $questionBlock}', $questionBlocks);
        self::assertMatchesRegularExpression(
            '/templates\/question\.tpl"\s+question=\$question\s+questionTypeConsts=\$questionTypeConsts'
                . '\s+formLocales=\$formLocales\s+formLocale=\$formLocale/s',
            $questionBlocks
        );
    }
}
