<?php

namespace APP\plugins\generic\deiaSurvey\report\classes;

use APP\core\Application;
use PKP\context\Context;
use APP\plugins\generic\deiaSurvey\classes\DefaultQuestionsCreator;
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

    public function getReportHeader(): array
    {
        $defaultQuestionsData = DefaultQuestionsCreator::getDefaultQuestionsData(1);
        $applicationName = Application::get()->getName();

        $firstRow = [__("plugins.generic.deiaSurvey.report.contextName.$applicationName", [], $this->locale)];
        $secondRow = [''];

        foreach ($defaultQuestionsData as $questionData) {
            $firstRow[] = __($questionData['questionText'], [], $this->locale);

            foreach ($questionData['responseOptions'] as $index => $optionData) {
                if ($index > 0) {
                    $firstRow[] = '';
                }
                $secondRow[] = __($optionData['optionText'], [], $this->locale);
            }
        }

        $firstRow[] = __('plugins.generic.deiaSurvey.report.usersWhoConsented', [], $this->locale);
        $secondRow[] = '';
        $firstRow[] = __('plugins.generic.deiaSurvey.report.usersWhoDidNotConsent', [], $this->locale);
        $secondRow[] = '';

        return [$firstRow, $secondRow];
    }
}
