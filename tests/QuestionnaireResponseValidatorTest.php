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

    public function testSkipsAndDiscardsAnswersToAnInactiveQuestion(): void
    {
        $questions = array_reverse($this->questions());
        $questions[1]['showWhen'] = [2, 21];

        [$responses, $inputs, $hiddenQuestions] = (new QuestionnaireResponseValidator())->validate(
            $questions,
            ['question-1-text' => 'old answer', 'question-2-radio' => ['20']],
            [],
            false
        );

        self::assertSame(['question-2-radio' => [20]], $responses);
        self::assertSame([], $inputs);
        self::assertSame([1], $hiddenQuestions);
    }

    public function testRequiresVisibleConditionalQuestion(): void
    {
        $questions = array_reverse($this->questions());
        $questions[1]['showWhen'] = [2, 20];

        $this->expectException(\InvalidArgumentException::class);
        (new QuestionnaireResponseValidator())->validate(
            $questions,
            ['question-2-radio' => ['20']],
            [],
            false
        );
    }

    public function testAcceptsConditionalAnswerWhenDropdownMatches(): void
    {
        $questions = array_reverse($this->questions());
        $questions[0]['type'] = DeiaQuestion::TYPE_DROP_DOWN_BOX;
        $questions[0]['inputType'] = 'select';
        $questions[0]['responseOptions'] = [20 => 'Yes', 21 => 'No'];
        $questions[1]['showWhen'] = [2, 20];

        [$responses, , $hiddenQuestions] = (new QuestionnaireResponseValidator())->validate(
            $questions,
            ['question-1-text' => 'Amazonas', 'question-2-select' => '20'],
            [],
            false
        );
        self::assertSame(['question-2-select' => 20, 'question-1-text' => 'Amazonas'], $responses);
        self::assertSame([], $hiddenQuestions);
    }

    public function testHiddenQuestionCannotActivateAnotherCondition(): void
    {
        $questions = $this->questions();
        $questions[1]['showWhen'] = [3, 30];
        array_unshift($questions, [
            'questionId' => 3,
            'type' => DeiaQuestion::TYPE_DROP_DOWN_BOX,
            'inputType' => 'select',
            'responseOptions' => [30 => 'Yes', 31 => 'No'],
        ]);
        $questions[] = [
            'questionId' => 4,
            'type' => DeiaQuestion::TYPE_TEXT_FIELD,
            'inputType' => 'text',
            'responseOptions' => [],
            'showWhen' => [2, 20],
        ];

        [$responses, $inputs, $hiddenQuestions] = (new QuestionnaireResponseValidator())->validate(
            $questions,
            ['question-3-select' => '31', 'question-1-text' => 'answer', 'question-2-radio' => ['20']],
            ['responseOptionInput-20' => 'discard this'],
            false
        );
        self::assertSame(['question-3-select' => 31, 'question-1-text' => 'answer'], $responses);
        self::assertSame([], $inputs);
        self::assertSame([2, 4], $hiddenQuestions);
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
