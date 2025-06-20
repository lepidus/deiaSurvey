<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\core\Application;
use Illuminate\Support\Facades\Mail;
use PKP\plugins\PluginRegistry;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataDAO;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataService;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\mail\mailables\RequestCollectionContributorData;

class DataCollectionEmailSender
{
    public function sendRequestDataCollectionEmails(int $submissionId)
    {
        $submission = Repo::submission()->get($submissionId);
        $nonRegisteredAuthors = $this->getNonRegisteredAuthors($submission);

        if (!empty($nonRegisteredAuthors)) {
            $demographicDataService  = new DemographicDataService();

            foreach ($nonRegisteredAuthors as $author) {
                if (!$demographicDataService->authorAlreadyAnsweredQuestionnaire($author)) {
                    $this->sendEmailToAuthor($submission, $author);
                }
            }
        }
    }

    private function getNonRegisteredAuthors($submission): array
    {
        $publication = $submission->getCurrentPublication();
        $nonRegisteredAuthors = [];
        $demographicDataDao = new DemographicDataDAO();

        foreach ($publication->getData('authors') as $author) {
            $authorEmail = $author->getData('email');

            if (!$demographicDataDao->thereIsUserWithSetting($authorEmail, 'email')) {
                $nonRegisteredAuthors[] = $author;
            }
        }

        return $nonRegisteredAuthors;
    }

    private function sendEmailToAuthor($submission, $author)
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        $emailTemplate = Repo::emailTemplate()->getByKey(
            $context->getId(),
            'REQUEST_COLLECTION_CONTRIBUTOR_DATA'
        );
        $authorName = $author->getFullName();
        $authorEmail = $author->getData('email');

        $emailQuestionnaireUrls = $this->getQuestionnaireUrls($request, $author);

        $email = new RequestCollectionContributorData($context, $submission, $emailQuestionnaireUrls);
        $email->from($context->getData('contactEmail'), $context->getData('contactName'));
        $email->to([['name' => $authorName, 'email' => $authorEmail]]);
        $email->subject($emailTemplate->getLocalizedData('subject'));
        $email->body($emailTemplate->getLocalizedData('body'));

        Mail::send($email);
    }

    private function getQuestionnaireUrls($request, $author): array
    {
        $authorToken = md5(microtime() . $author->getData('email'));
        Repo::author()->edit($author, ['demographicToken' => $authorToken]);

        $questionnaireUrl = $request->getDispatcher()->url(
            $request,
            Application::ROUTE_PAGE,
            null,
            'demographicQuestionnaire',
            null,
            null,
            ['authorId' => $author->getId(), 'authorToken' => $authorToken]
        );

        $plugin = PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        $contextId = $request->getContext()->getId();
        $orcidClient = new OrcidClient($plugin, $contextId);

        $orcidQuestionnaireUrl = $orcidClient->buildOAuthUrl([
            'authorId' => $author->getId(),
            'authorToken' => $authorToken
        ]);

        return ['questionnaireUrl' => $questionnaireUrl, 'orcidQuestionnaireUrl' => $orcidQuestionnaireUrl];
    }
}
