<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use PHPUnit\Framework\TestCase;

class QuestionnaireStyleTest extends TestCase
{
    public function testQuestionBlockDescriptionsHaveOneRemBottomMargin(): void
    {
        $styles = file_get_contents(dirname(__DIR__) . '/styles/questionsInProfile.css');

        self::assertMatchesRegularExpression(
            '/#deiaSurveyForm \.pkp_formArea > \.section > \.description\s*\{[^}]*margin-bottom: 1rem;/s',
            $styles
        );
    }

    public function testFirstQuestionInBlockHasExpectedMargins(): void
    {
        $styles = file_get_contents(dirname(__DIR__) . '/styles/questionsInProfile.css');

        self::assertMatchesRegularExpression(
            '/#deiaSurveyForm \.pkp_formArea > \.section > \.description \+ \.section\s*\{'
                . '[^}]*margin-top: 0;[^}]*margin-bottom: 1rem;/s',
            $styles
        );
    }

    public function testPreviewBlockDescriptionHasOneRemBottomMargin(): void
    {
        $styles = file_get_contents(dirname(__DIR__) . '/styles/questionsInProfile.css');

        self::assertMatchesRegularExpression(
            '/#deiaQuestionBlockPreview > \.questionBlockDescription\s*\{[^}]*margin-bottom: 1rem;/s',
            $styles
        );
    }
}
