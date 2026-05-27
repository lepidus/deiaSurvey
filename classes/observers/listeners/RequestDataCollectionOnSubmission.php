<?php

namespace APP\plugins\generic\deiaSurvey\classes\observers\listeners;

use APP\core\Application;
use APP\plugins\generic\deiaSurvey\classes\DataCollectionEmailSender;
use Illuminate\Events\Dispatcher;
use PKP\observers\events\SubmissionSubmitted;

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
