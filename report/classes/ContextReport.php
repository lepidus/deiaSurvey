<?php

namespace APP\plugins\generic\deiaSurvey\report\classes;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock\DeiaQuestionBlock;

class ContextReport
{
    private $questionBlocks;

    public function __construct()
    {
        $this->questionBlocks = [];
    }

    public function addQuestionBlock(DeiaQuestionBlock $block)
    {
        $seq = $block->getSequence() ?? 1;
        $this->questionBlocks[$seq - 1] = $block;
    }

    public function getHeaders(): array
    {
        $questionBlockHeaders = array_map(function ($questionBlock) {
            return $questionBlock->getLocalizedTitle();
        }, $this->questionBlocks);

        return [
            $questionBlockHeaders
        ];
    }
}
