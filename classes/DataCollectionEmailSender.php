<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\core\Application;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataDAO;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataService;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\mail\mailables\RequestCollectionContributorData;
use Illuminate\Support\Facades\Mail;
use PKP\plugins\PluginRegistry;

class DataCollectionEmailSender
{
    public function sendRequestDataCollectionEmails(int $submissionId)
    {
        $submission = \Services::get('submission')->get($submissionId);
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
        $request = \Application::get()->getRequest();
        $context = $request->getContext();
        $publication = \Services::get('publication')->get($author->getData('publicationId'));

        import('lib.pkp.classes.mail.MailTemplate');
        $emailTemplate = new \MailTemplate('REQUEST_COLLECTION_CONTRIBUTOR_DATA', null, $context, false);

        $authorName = $author->getFullName();
        $authorEmail = $author->getData('email');

        $emailQuestionnaireUrls = $this->getQuestionnaireUrls($request, $author);

        $emailTemplate->setFrom($context->getData('contactEmail'), $context->getData('contactName'));
        $emailTemplate->setRecipients([['name' => $authorName, 'email' => $authorEmail]]);
        $emailTemplate->sendWithParams(array_merge($emailQuestionnaireUrls, [
            'submissionTitle' => htmlspecialchars($publication->getLocalizedTitle()),
            'contactName' => $context->getData('contactName')
        ]));
    }

    private function getQuestionnaireUrls($request, $author): array
    {
        $authorToken = md5(microtime() . $author->getData('email'));

        $author = \Services::get('author')->edit($author, ['demographicToken' => $authorToken], $request);

        $questionnaireUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_PAGE,
            null,
            'demographicQuestionnaire',
            null,
            null,
            ['authorId' => $author->getId(), 'authorToken' => $authorToken]
        );

        $plugin = \PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        $contextId = $request->getContext()->getId();
        $orcidClient = new OrcidClient($plugin, $contextId);

        $orcidQuestionnaireUrl = $orcidClient->buildOAuthUrl([
            'authorId' => $author->getId(),
            'authorToken' => $authorToken
        ]);

        return ['questionnaireUrl' => $questionnaireUrl, 'orcidQuestionnaireUrl' => $orcidQuestionnaireUrl];
    }
}
