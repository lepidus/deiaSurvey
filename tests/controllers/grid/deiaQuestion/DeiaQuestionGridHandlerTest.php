<?php

namespace APP\plugins\generic\deiaSurvey\tests\controllers\grid\deiaQuestion;

use PKP\tests\PKPTestCase;

class DeiaQuestionGridHandlerTest extends PKPTestCase
{
    public function testQuestionBlockElementsTemplateShowsEditRestrictionWarningAboveGrid(): void
    {
        $template = file_get_contents(
            dirname(__DIR__, 4) . '/templates/deiaQuestionBlocks/deiaQuestionBlockElements.tpl'
        );

        self::assertStringContainsString(
            'plugins.generic.deiaSurvey.questionBlocks.questions.editWarning',
            $template
        );
        self::assertStringContainsString('<p class="deiaQuestionBlockEditWarning"', $template);
        self::assertLessThan(
            strpos($template, 'id="deiaQuestionGridContainer"'),
            strpos($template, 'deiaQuestionBlockEditWarning')
        );
    }
}
