<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaQuestionBlock;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock\DeiaQuestionBlock;
use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\DeiaResponseOption;
use APP\plugins\generic\deiaSurvey\classes\importExport\DeiaQuestionBlockJsonSerializer;
use PKP\facades\Locale;
use PKP\tests\PKPTestCase;

class DeiaQuestionBlockJsonSerializerTest extends PKPTestCase
{
    public function testSerializeBlockWithoutQuestions(): void
    {
        $block = new DeiaQuestionBlock();
        $block->setData('title', ['en' => 'Funding DEIA questions']);
        $block->setData('description', ['en' => 'Questions about funding access.']);
        $block->setActive(1);
        $block->setSequence(2);

        $serializer = new DeiaQuestionBlockJsonSerializer();

        self::assertEquals(
            [
                'schemaVersion' => '1.0',
                'plugin' => 'deiaSurvey',
                'blocks' => [
                    [
                        'title' => ['en' => 'Funding DEIA questions'],
                        'description' => ['en' => 'Questions about funding access.'],
                        'active' => true,
                        'sequence' => 2,
                        'questions' => [],
                    ],
                ],
            ],
            $serializer->serializeBlocks([$block])
        );
    }

    public function testSerializeBlockWithQuestions(): void
    {
        $question = new DeiaQuestion();
        $question->setQuestionType(DeiaQuestion::TYPE_TEXTAREA);
        $question->setData('questionText', ['en' => 'Describe your funding access']);
        $question->setData('questionDescription', ['en' => 'Use as much detail as needed.']);
        $question->setSequence(1);

        $block = new DeiaQuestionBlock();
        $block->setData('title', ['en' => 'Funding DEIA questions']);
        $block->setData('description', ['en' => 'Questions about funding access.']);
        $block->setActive(0);
        $block->setSequence(1);
        $block->setData('questions', [$question]);

        $serializer = new DeiaQuestionBlockJsonSerializer();
        $serialized = $serializer->serializeBlocks([$block]);

        self::assertEquals(
            [
                [
                    'questionType' => DeiaQuestion::TYPE_TEXTAREA,
                    'questionText' => ['en' => 'Describe your funding access'],
                    'questionDescription' => ['en' => 'Use as much detail as needed.'],
                    'sequence' => 1,
                    'responseOptions' => [],
                ],
            ],
            $serialized['blocks'][0]['questions']
        );
    }

    public function testSerializeQuestionResponseOptions(): void
    {
        $firstOption = new DeiaResponseOption();
        $firstOption->setData('optionText', ['en' => 'Grant funding']);
        $firstOption->setHasInputField(false);
        $firstOption->setData('sequence', 1);

        $secondOption = new DeiaResponseOption();
        $secondOption->setData('optionText', ['en' => 'Other']);
        $secondOption->setHasInputField(true);
        $secondOption->setData('sequence', 2);

        $question = new DeiaQuestion();
        $question->setQuestionType(DeiaQuestion::TYPE_CHECKBOXES);
        $question->setData('questionText', ['en' => 'Which funding sources apply?']);
        $question->setData('questionDescription', ['en' => 'Select all that apply.']);
        $question->setSequence(1);
        $question->setData('responseOptions', [$firstOption, $secondOption]);

        $block = new DeiaQuestionBlock();
        $block->setData('title', ['en' => 'Funding DEIA questions']);
        $block->setData('description', ['en' => 'Questions about funding access.']);
        $block->setActive(0);
        $block->setSequence(1);
        $block->setData('questions', [$question]);

        $serializer = new DeiaQuestionBlockJsonSerializer();
        $serialized = $serializer->serializeBlocks([$block]);

        self::assertEquals(
            [
                [
                    'optionText' => ['en' => 'Grant funding'],
                    'hasInputField' => false,
                    'sequence' => 1,
                ],
                [
                    'optionText' => ['en' => 'Other'],
                    'hasInputField' => true,
                    'sequence' => 2,
                ],
            ],
            $serialized['blocks'][0]['questions'][0]['responseOptions']
        );
    }

    public function testSerializeQuestionTranslationKeysAsText(): void
    {
        $question = new DeiaQuestion();
        $question->setQuestionType(DeiaQuestion::TYPE_TEXT_FIELD);
        $question->setData('questionText', 'common.cancel');
        $question->setData('questionDescription', '');
        $question->setSequence(1);

        $block = new DeiaQuestionBlock();
        $block->setData('title', ['en' => 'Funding DEIA questions']);
        $block->setData('description', []);
        $block->setActive(0);
        $block->setSequence(1);
        $block->setData('questions', [$question]);

        $serializer = new DeiaQuestionBlockJsonSerializer();
        $serialized = $serializer->serializeBlocks([$block]);

        self::assertSame(
            __('common.cancel'),
            $serialized['blocks'][0]['questions'][0]['questionText'][Locale::getLocale()]
        );
        self::assertArrayNotHasKey('isTranslated', $serialized['blocks'][0]['questions'][0]);
    }
}
