<?php

namespace APP\plugins\generic\deiaSurvey\tests\controllers\grid\deiaQuestion;

require_once(dirname(__DIR__, 4) . '/autoload.php');

import('lib.pkp.tests.PKPTestCase');

class DeiaQuestionGridHandlerTest extends \PKPTestCase
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
        self::assertStringContainsString('class="deiaQuestionBlockEditWarning"', $template);
        self::assertStringContainsString(
            'margin: 0 0 1rem; font-size: .875rem; line-height: 1.5rem; font-weight: 400; color: rgba(0, 0, 0, 0.54);',
            $template
        );
        self::assertLessThan(
            strpos($template, 'id="deiaQuestionGridContainer"'),
            strpos($template, 'deiaQuestionBlockEditWarning')
        );
    }
}
