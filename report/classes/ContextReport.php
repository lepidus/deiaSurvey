<?php

namespace APP\plugins\generic\deiaSurvey\report\classes;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock\DeiaQuestionBlock;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\deiaResponse\DeiaResponse;
use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\DeiaResponseOption;

class ContextReport
{
    private array $questionBlocks;
    private array $questions;
    private array $responses;
    private array $responseOptions;

    public function __construct()
    {
        $this->questionBlocks = $this->questions = [];
        $this->responses = $this->responseOptions = [];
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

    public function addResponse(DeiaResponse $response)
    {
        $userId = $response->getUserId();
        $questionId = $response->getDeiaQuestionId();

        $userResponses = $this->responses[$userId] ?? [];
        $userResponses[$questionId] = $response;
        $this->responses[$userId] = $userResponses;
    }

    public function addResponseOption(DeiaResponseOption $responseOption)
    {
        $this->responseOptions[$responseOption->getId()] = $responseOption;
    }

    public function sequenceOrderFunction($left, $right)
    {
        return $left->getSequence() <=> $right->getSequence();
    }

    public function getHeaders(): array
    {
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

        foreach ($this->questionBlocks as $questionBlock) {
            foreach ($this->questions[$questionBlock->getId()] as $question) {
                $printingGuide[] = $question->getId();
            }
        }

        return $printingGuide;
    }
}
