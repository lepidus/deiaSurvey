<?php

namespace APP\plugins\generic\deiaSurvey\tests\security;

use APP\plugins\generic\deiaSurvey\classes\QuestionnaireResponseValidator;
use DomainException;
use PHPUnit\Framework\TestCase;

class QuestionnaireResponseValidatorTest extends TestCase
{
    /**
     * @dataProvider invalidPayloadProvider
     */
    public function testRejectsInputOutsideTheActiveContext(array $responses, array $inputs): void
    {
        $this->expectException(DomainException::class);

        (new QuestionnaireResponseValidator())->normalize($this->questions(), $responses, $inputs);
    }

    public function invalidPayloadProvider(): array
    {
        return [
            'question from another context' => [['question-99-text' => 'x'], []],
            'inactive question' => [['question-30-text' => 'x'], []],
            'tampered type' => [['question-10-select' => 'x'], []],
            'duplicate canonical id' => [[
                'question-10-text' => 'x',
                'question-010-text' => 'y',
            ], []],
            'malformed checkbox array' => [['question-20-checkbox' => '201'], []],
            'duplicate options' => [['question-20-checkbox' => ['201', '201']], []],
            'option from another question' => [['question-20-checkbox' => ['999']], []],
            'input for unselected option' => [
                ['question-20-checkbox' => ['201']],
                ['responseOptionInput-202' => 'other'],
            ],
            'malformed input key' => [
                ['question-20-checkbox' => ['201']],
                ['responseOptionInput-x' => 'other'],
            ],
        ];
    }

    public function testCanonicalizesAValidPayloadUsingServerTypes(): void
    {
        $normalized = (new QuestionnaireResponseValidator())->normalize(
            $this->questions(),
            [
                'question-10-text' => 'answer',
                'question-20-checkbox' => ['202'],
            ],
            ['responseOptionInput-202' => 'detail']
        );

        self::assertSame([
            'responses' => [
                'question-10-text' => 'answer',
                'question-20-checkbox' => [202],
            ],
            'responseOptionsInputs' => ['responseOptionInput-202' => 'detail'],
            'hiddenQuestions' => [],
        ], $normalized);
    }

    public function testIgnoresEmptyInputForAnUnselectedDefinedOption(): void
    {
        $normalized = (new QuestionnaireResponseValidator())->normalize(
            $this->questions(),
            [
                'question-10-text' => 'answer',
                'question-20-checkbox' => ['201'],
            ],
            ['responseOptionInput-202' => '']
        );

        self::assertSame([], $normalized['responseOptionsInputs']);
    }

    public function testDiscardsAnswersIfTheConditionIsFalse(): void
    {
        $normalized = (new QuestionnaireResponseValidator())->normalize(
            $this->conditionalQuestions(),
            ['question-10-text' => 'Old answer', 'question-20-radio' => ['201']],
            []
        );

        self::assertSame(['question-20-radio' => [201]], $normalized['responses']);
        self::assertSame([10], $normalized['hiddenQuestions']);
    }

    public function testRequiresTheConditionalResponseIfTheConditionIsTrue(): void
    {
        $this->expectException(DomainException::class);

        (new QuestionnaireResponseValidator())->normalize(
            $this->conditionalQuestions(),
            ['question-20-radio' => ['202']],
            []
        );
    }

    /** @dataProvider invalidConditionalText */
    public function testRejectsInvalidConditionalText($answer, ?string $locale): void
    {
        $this->expectException(DomainException::class);

        (new QuestionnaireResponseValidator())->normalize(
            $this->conditionalQuestions(),
            ['question-20-radio' => ['202'], 'question-10-text' => $answer],
            [],
            $locale
        );
    }

    public static function invalidConditionalText(): array
    {
        return [
            'blank invitation text' => ['  ', null],
            'localized map in an invitation' => [['en' => 'Amazonas'], null],
            'profile text blank in its locale' => [['en' => '  ', 'pt_BR' => 'Amazonas'], 'en'],
        ];
    }

    public function testAcceptsConditionalTextInTheProfileLocale(): void
    {
        $normalized = (new QuestionnaireResponseValidator())->normalize(
            $this->conditionalQuestions(),
            ['question-20-radio' => ['202'], 'question-10-text' => ['en' => 'Amazonas']],
            [],
            'en'
        );

        self::assertSame(['en' => 'Amazonas'], $normalized['responses']['question-10-text']);
    }

    public function testAcceptsConditionalResponseWhenDropdownMatches(): void
    {
        $questions = $this->conditionalQuestions();
        $questions[0]['type'] = 6;
        $questions[0]['inputType'] = 'select';
        $questions[0]['responseOptions'] = [201 => 'No', 202 => 'Yes'];

        $normalized = (new QuestionnaireResponseValidator())->normalize(
            $questions,
            ['question-10-text' => 'Amazonas', 'question-20-select' => '202'],
            []
        );

        self::assertSame(['question-20-select' => 202, 'question-10-text' => 'Amazonas'], $normalized['responses']);
        self::assertSame([], $normalized['hiddenQuestions']);
    }

    private function conditionalQuestions(): array
    {
        $questions = array_reverse($this->questions());
        $questions[0]['type'] = 5;
        $questions[0]['inputType'] = 'radio';
        $questions[1]['showWhen'] = [20, 202];
        return $questions;
    }

    private function questions(): array
    {
        return [
            [
                'questionId' => 10,
                'type' => 2,
                'inputType' => 'text',
                'responseOptions' => [],
            ],
            [
                'questionId' => 20,
                'type' => 4,
                'inputType' => 'checkbox',
                'responseOptions' => [
                    $this->option(201, false),
                    $this->option(202, true),
                ],
            ],
        ];
    }

    private function option(int $id, bool $hasInput): object
    {
        return new class ($id, $hasInput) {
            private int $id;
            private bool $hasInput;

            public function __construct(int $id, bool $hasInput)
            {
                $this->id = $id;
                $this->hasInput = $hasInput;
            }

            public function getId(): int
            {
                return $this->id;
            }

            public function hasInputField(): bool
            {
                return $this->hasInput;
            }
        };
    }
}
