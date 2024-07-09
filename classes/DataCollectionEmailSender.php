<?php

namespace APP\plugins\generic\demographicData\classes;

use APP\core\Application;
use Illuminate\Support\Facades\Mail;
use APP\plugins\generic\demographicData\classes\DemographicDataDAO;
use APP\plugins\generic\demographicData\classes\DemographicDataService;
use APP\plugins\generic\demographicData\classes\facades\Repo;
use APP\plugins\generic\demographicData\classes\mail\mailables\RequestCollectionContributorData;

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

            if (!$demographicDataDao->thereIsUserRegistered($authorEmail)) {
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

        $questionnaireUrl = $this->getQuestionnairePageUrl($request, $author);
        $emailBodyParams = [
            'orcidQuestionnaireUrl' => $questionnaireUrl,   //Will be changed in next commits
            'questionnaireUrl' => $questionnaireUrl
        ];

        $email = new RequestCollectionContributorData($context, $submission, $emailBodyParams);
        $email->from($context->getData('contactEmail'), $context->getData('contactName'));
        $email->to([['name' => $authorName, 'email' => $authorEmail]]);
        $email->subject($emailTemplate->getLocalizedData('subject'));
        $email->body($emailTemplate->getLocalizedData('body'));

        Mail::send($email);
    }

    private function getQuestionnairePageUrl($request, $author): string
    {
        $authorToken = md5(microtime() . $author->getData('email'));

        Repo::author()->edit($author, ['demographicToken' => $authorToken]);

        return $request->getDispatcher()->url(
            $request,
            Application::ROUTE_PAGE,
            null,
            'demographicQuestionnaire',
            null,
            null,
            ['authorId' => $author->getId(), 'authorToken' => $authorToken]
        );
    }
}
