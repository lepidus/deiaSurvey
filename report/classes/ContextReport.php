<?php

namespace APP\plugins\generic\deiaSurvey\report\classes;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock\DeiaQuestionBlock;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;

class ContextReport
{
    private array $questionBlocks;
    private array $questions;

    public function __construct()
    {
        $this->questionBlocks = $this->questions = [];
    }

    public function addQuestionBlock(DeiaQuestionBlock $block)
    {
        $this->questionBlocks[$block->getId()] = $block;
    }

    public function addQuestion(DeiaQuestion $question)
    {
        $questionBlockId = $question->getQuestionBlockId();

        $blockQuestions = $this->questions[$questionBlockId] ?? [];
        $blockQuestions[] = $question;
        $this->questions[$questionBlockId] = $blockQuestions;
    }

    public function sequenceOrderFunction($left, $right)
    {
        return $left->getSequence() <=> $right->getSequence();
    }

    public function getHeaders(): array
    {
        $this->getQuestionsPrintingGuide();

        $questionBlockHeaders = [];
        $questionHeaders = [];
        foreach ($this->questionBlocks as $questionBlock) {
            $questionBlockHeaders[] = $questionBlock->getLocalizedTitle();

            $blockQuestions = $this->questions[$questionBlock->getId()];
            foreach ($blockQuestions as $question) {
                $questionHeaders[] = $question->getLocalizedQuestionText();
            }
        }

        return [
            $questionBlockHeaders,
            $questionHeaders
        ];
    }

    public function getQuestionsPrintingGuide(): array
    {
        $printingGuide = [];
        usort($this->questionBlocks, [$this, 'sequenceOrderFunction']);

        foreach ($this->questionBlocks as $questionBlock) {
            usort($this->questions[$questionBlock->getId()], [$this, 'sequenceOrderFunction']);

            foreach ($this->questions[$questionBlock->getId()] as $question) {
                $printingGuide[] = $question->getId();
            }
        }

        return $printingGuide;
    }
}
