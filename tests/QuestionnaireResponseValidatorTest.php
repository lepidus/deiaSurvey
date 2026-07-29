<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\QuestionnaireResponseValidator;
use PHPUnit\Framework\TestCase;

class QuestionnaireResponseValidatorTest extends TestCase
{
    /** @dataProvider invalidPayloads */
    public function testRejectsQuestionnaireDataOutsideServerContract(array $responses, array $inputs): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new QuestionnaireResponseValidator())->validate($this->questions(), $responses, $inputs, false);
    }

    public static function invalidPayloads(): array
    {
        return [
            'question from another context' => [['question-99-text' => 'x', 'question-2-radio' => ['20']], []],
            'tampered type' => [['question-1-textarea' => 'x', 'question-2-radio' => ['20']], []],
            'option from another question' => [['question-1-text' => 'x', 'question-2-radio' => ['30']], []],
            'repeated question id' => [['question-1-text' => 'x', 'question-1-textarea' => 'y'], []],
            'malformed options array' => [['question-1-text' => 'x', 'question-2-radio' => '20'], []],
            'input for unselected option' => [
                ['question-1-text' => 'x', 'question-2-radio' => ['20']],
                ['responseOptionInput-21' => 'x'],
            ],
        ];
    }

    public function testAcceptsOnlyActiveServerQuestionsAndTheirOptions(): void
    {
        [$responses, $inputs] = (new QuestionnaireResponseValidator())->validate(
            $this->questions(),
            ['question-1-text' => 'answer', 'question-2-radio' => ['20']],
            ['responseOptionInput-20' => 'detail'],
            false
        );

        self::assertSame('answer', $responses['question-1-text']);
        self::assertSame([20], $responses['question-2-radio']);
        self::assertSame(['responseOptionInput-20' => 'detail'], $inputs);
    }

    public function testIgnoresEmptyInputForAnUnselectedDefinedOption(): void
    {
        $questions = $this->questions();
        $questions[1]['responseOptions'][] = new class () {
            public function getId(): int
            {
                return 21;
            }

            public function hasInputField(): bool
            {
                return true;
            }
        };

        [, $inputs] = (new QuestionnaireResponseValidator())->validate(
            $questions,
            ['question-1-text' => 'answer', 'question-2-radio' => ['20']],
            ['responseOptionInput-20' => 'detail', 'responseOptionInput-21' => ''],
            false
        );

        self::assertSame(['responseOptionInput-20' => 'detail'], $inputs);
    }

    private function questions(): array
    {
        $option = new class () {
            public function getId(): int
            {
                return 20;
            }

            public function hasInputField(): bool
            {
                return true;
            }
        };

        return [
            [
                'questionId' => 1,
                'type' => DeiaQuestion::TYPE_TEXT_FIELD,
                'inputType' => 'text',
                'responseOptions' => [],
            ],
            [
                'questionId' => 2,
                'type' => DeiaQuestion::TYPE_RADIO_BUTTONS,
                'inputType' => 'radio',
                'responseOptions' => [20 => $option],
            ],
        ];
    }
}
