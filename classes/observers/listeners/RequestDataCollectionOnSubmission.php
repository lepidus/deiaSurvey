<?php

namespace APP\plugins\generic\deiaSurvey\classes\observers\listeners;

use Illuminate\Events\Dispatcher;
use APP\core\Application;
use PKP\observers\events\SubmissionSubmitted;
use APP\plugins\generic\deiaSurvey\classes\DataCollectionEmailSender;

class RequestDataCollectionOnSubmission
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            SubmissionSubmitted::class,
            RequestDataCollectionOnSubmission::class
        );
    }

    public function handle(SubmissionSubmitted $event): void
    {
        $submission = $event->submission;
        $context = $event->context;
        $applicationName = Application::get()->getName();

        if ($applicationName != 'ops') {
            return;
        }

        $dataCollectionEmailSender = new DataCollectionEmailSender();
        $dataCollectionEmailSender->sendRequestDataCollectionEmails($submission->getId());
    }
}
