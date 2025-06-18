<?php

namespace APP\plugins\generic\deiaSurvey\classes\dispatchers;

use APP\plugins\generic\deiaSurvey\classes\dispatchers\DemographicDataDispatcher;
use APP\plugins\generic\deiaSurvey\classes\DataCollectionEmailSender;

class DataCollectionDispatcher extends DemographicDataDispatcher
{
    protected function registerHooks(): void
    {
        \HookRegistry::register('EditorAction::recordDecision', [$this, 'requestDataCollectionOnAccept']);
        \HookRegistry::register('SubmissionHandler::saveSubmit', [$this, 'requestDataCollectionOnSubmission']);
        \HookRegistry::register('Publication::publish', [$this, 'requestDataCollectionOnPosting']);
    }

    public function requestDataCollectionOnAccept(string $hookName, array $params)
    {
        $submission = $params[0];
        $decision = $params[1];
        $applicationName = \Application::get()->getName();

        if ($applicationName != 'ojs2' || $decision['decision'] != SUBMISSION_EDITOR_DECISION_ACCEPT) {
            return;
        }

        $dataCollectionEmailSender = new DataCollectionEmailSender();
        $dataCollectionEmailSender->sendRequestDataCollectionEmails($submission->getId());
    }

    public function requestDataCollectionOnSubmission(string $hookName, array $params)
    {
        $step = $params[0];
        $submission = $params[1];
        $stepForm = $params[2];
        $applicationName = \Application::get()->getName();

        if ($applicationName != 'ops' || $step !== 4 || !$stepForm->validate()) {
            return;
        }

        $dataCollectionEmailSender = new DataCollectionEmailSender();
        $dataCollectionEmailSender->sendRequestDataCollectionEmails($submission->getId());
    }

    public function requestDataCollectionOnPosting(string $hookName, array $params)
    {
        $publication = $params[0];
        $submission = $params[2];
        $applicationName = \Application::get()->getName();

        if ($applicationName != 'ops' || $publication->getData('version') > 1) {
            return;
        }

        $dataCollectionEmailSender = new DataCollectionEmailSender();
        $dataCollectionEmailSender->sendRequestDataCollectionEmails($submission->getId());
    }
}
