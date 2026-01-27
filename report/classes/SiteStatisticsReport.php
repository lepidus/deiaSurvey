<?php

namespace APP\plugins\generic\deiaSurvey\report\classes;

use PKP\context\Context;
use APP\plugins\generic\deiaSurvey\report\classes\ContextStatistics;

class SiteStatisticsReport
{
    private string $locale;
    private array $contextsStatistics;

    public function __construct(string $locale)
    {
        $this->locale = $locale;
        $this->contextsStatistics = [];
    }

    public function addContextStatistics(Context $context, ContextStatistics $statistics): void
    {
        $this->contextsStatistics[] = [
            'context' => $context,
            'statistics' => $statistics
        ];
    }

    public function getContextsStatistics(): array
    {
        return $this->contextsStatistics;
    }
}
