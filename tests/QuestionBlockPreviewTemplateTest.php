<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use PHPUnit\Framework\TestCase;

class QuestionBlockPreviewTemplateTest extends TestCase
{
    public function testEditDialogOffersQuestionBlockPreview(): void
    {
        $editTemplate = file_get_contents(
            dirname(__DIR__) . '/templates/deiaQuestionBlocks/editDeiaQuestionBlock.tpl'
        );
        $previewTemplate = dirname(__DIR__) . '/templates/deiaQuestionBlocks/previewDeiaQuestionBlock.tpl';

        self::assertStringContainsString('op="previewDeiaQuestionBlock"', $editTemplate);
        self::assertStringContainsString('{translate key="common.preview"}', $editTemplate);
        self::assertStringContainsString('selected: {if $preview}2{else}0{/if}', $editTemplate);
        self::assertFileExists($previewTemplate);

        $preview = file_get_contents($previewTemplate);
        self::assertStringContainsString('id="deiaQuestionBlockPreview"', $preview);
        self::assertStringContainsString('styles/questionsInProfile.css', $preview);
        self::assertStringContainsString("{\$questionBlock['title']|escape}", $preview);
        self::assertStringContainsString('class="questionBlockDescription"', $preview);
        self::assertStringContainsString("{\$questionBlock['description']|escape}", $preview);
        self::assertStringContainsString('templates/question.tpl" question=$question', $preview);
    }
}
