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
    private $UTF8_BOM;

    public function __construct()
    {
        $this->questionBlocks = $this->questions = [];
        $this->responses = $this->responseOptions = [];
        $this->UTF8_BOM = chr(0xEF) . chr(0xBB) . chr(0xBF);
    }

    public function addQuestionBlock(DeiaQuestionBlock $block)
    {
        $this->questionBlocks[$block->getId()] = ['block' => $block, 'questionIds' => []];
    }

    public function addQuestion(DeiaQuestion $question)
    {
        $questionBlockId = $question->getQuestionBlockId();
        $questionId = $question->getId();

        $this->questions[$questionId] = $question;

        $blockQuestionIds = $this->questionBlocks[$questionBlockId]['questionIds'];
        $blockQuestionIds[] = $questionId;
        $this->questionBlocks[$questionBlockId]['questionIds'] = $blockQuestionIds;
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

        foreach ($this->questionBlocks as $questionBlockData) {
            $questionBlock = $questionBlockData['block'];
            $questionBlockHeaders[] = $questionBlock->getLocalizedTitle();

            $blockQuestionIds = $questionBlockData['questionIds'];
            foreach ($blockQuestionIds as $questionId) {
                $question = $this->questions[$questionId];
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

        foreach ($this->questionBlocks as $questionBlockData) {
            $blockQuestionIds = $questionBlockData['questionIds'];
            $printingGuide = array_merge($printingGuide, $blockQuestionIds);
        }

        return $printingGuide;
    }

    public function writeReport(string $filePath, array $printingGuide)
    {
        $csvFile = fopen($filePath, 'wt');
        fprintf($csvFile, $this->UTF8_BOM);

        $headers = $this->getHeaders();
        fputcsv($csvFile, $headers[0]);
        fputcsv($csvFile, $headers[1]);

        foreach ($this->responses as $responseSet) {
            $responsesLine = [];
            foreach ($printingGuide as $questionId) {
                $responseForQuestion = $responseSet[$questionId];
            }

            fputcsv($csvFile, $responsesLine);
        }

        fclose($csvFile);
    }
}
