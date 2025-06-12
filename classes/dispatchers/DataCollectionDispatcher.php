<?php

namespace APP\plugins\generic\demographicData\classes\dispatchers;

use PKP\plugins\Hook;
use APP\core\Application;
use APP\decision\Decision;
use Illuminate\Support\Facades\Event;
use APP\plugins\generic\demographicData\classes\dispatchers\DemographicDataDispatcher;
use APP\plugins\generic\demographicData\classes\DataCollectionEmailSender;
use APP\plugins\generic\demographicData\classes\observers\listeners\RequestDataCollectionOnSubmission;

class DataCollectionDispatcher extends DemographicDataDispatcher
{
    protected function registerHooks(): void
    {
        Hook::add('Decision::add', [$this, 'requestDataCollectionOnAccept']);
        Hook::add('Publication::publish', [$this, 'requestDataCollectionOnPosting']);

        Event::subscribe(new RequestDataCollectionOnSubmission());
    }

    public function requestDataCollectionOnAccept(string $hookName, array $params)
    {
        $decision = $params[0];
        $applicationName = Application::get()->getName();

        if ($applicationName != 'ojs2'
            || ($decision->getData('decision') != Decision::ACCEPT and $decision->getData('decision') != Decision::SKIP_EXTERNAL_REVIEW)
        ) {
            return;
        }

        $submissionId = $decision->getData('submissionId');

        $dataCollectionEmailSender = new DataCollectionEmailSender();
        $dataCollectionEmailSender->sendRequestDataCollectionEmails($submissionId);
    }

    public function requestDataCollectionOnPosting(string $hookName, array $params)
    {
        $publication = $params[0];
        $submission = $params[2];
        $applicationName = Application::get()->getName();

        if ($applicationName != 'ops' || $publication->getData('version') > 1) {
            return;
        }

        $dataCollectionEmailSender = new DataCollectionEmailSender();
        $dataCollectionEmailSender->sendRequestDataCollectionEmails($submission->getId());
    }
}
