<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\core\Application;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\mail\mailables\ConfirmDeiaEmail;
use APP\plugins\generic\deiaSurvey\classes\mail\mailables\RequestCollectionContributorData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PKP\context\Context;
use PKP\user\User;

class DataCollectionEmailSender
{
    public function sendEmailValidation(Context $context, User $user): bool
    {
        return DB::transaction(function () use ($context, $user): bool {
            $token = (new EmailAssociationService())->create($context, $user->getId());
            if ($token === null) {
                return false;
            }
            $user = Repo::user()->get($user->getId(), true);

            $request = Application::get()->getRequest();
            $email = new ConfirmDeiaEmail($context, [
                'associationUrl' => htmlspecialchars($request->getDispatcher()->url(
                    $request,
                    Application::ROUTE_PAGE,
                    $context->getPath(),
                    'deiaAssociation',
                    'index',
                    null,
                    ['userId' => $user->getId(), 'token' => $token]
                ), ENT_QUOTES, 'UTF-8'),
            ]);

            $template = Repo::emailTemplate()->getByKey($context->getId(), $email::getEmailTemplateKey());
            $email->from($context->getData('contactEmail'), $context->getData('contactName'));
            $email->recipients([$user]);
            $email->subject($template?->getLocalizedData('subject') ?: __('emails.confirmDeiaEmail.subject'));
            $email->body($template?->getLocalizedData('body') ?: __('emails.confirmDeiaEmail.body'));
            Mail::send($email);

            return true;
        });
    }

    public function sendRequestDataCollectionEmails(int $submissionId)
    {
        $submission = Repo::submission()->get($submissionId);
        $nonRegisteredAuthors = $this->getNonRegisteredAuthors($submission);

        if (!empty($nonRegisteredAuthors)) {
            $deiaDataService = new DeiaDataService();

            foreach ($nonRegisteredAuthors as $author) {
                if (!$deiaDataService->authorAlreadyAnsweredQuestionnaire($author)) {
                    $this->sendEmailToAuthor($submission, $author);
                }
            }
        }
    }

    private function getNonRegisteredAuthors($submission): array
    {
        $publication = $submission->getCurrentPublication();
        $nonRegisteredAuthors = [];
        $deiaDataDao = new DeiaDataDAO();

        foreach ($publication->getData('authors') as $author) {
            $authorEmail = $author->getData('email');

            if (!$deiaDataDao->thereIsUserWithSetting($authorEmail, 'email')) {
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
        Repo::author()->edit($author, ['deiaToken' => $authorToken]);

        $questionnaireUrl = $request->getDispatcher()->url(
            $request,
            Application::ROUTE_PAGE,
            null,
            'deiaQuestionnaire',
            null,
            null,
            ['authorId' => $author->getId(), 'authorToken' => $authorToken]
        );

        $contextId = $request->getContext()->getId();
        // Start OAuth in the contributor's browser so state belongs to their session.
        $orcidQuestionnaireUrl = (new OrcidConfiguration())->getOrcidConfiguration($contextId)
            ? $request->getDispatcher()->url(
                $request,
                Application::ROUTE_PAGE,
                null,
                'deiaQuestionnaire',
                'orcidAuthorize',
                null,
                ['authorId' => $author->getId(), 'authorToken' => $authorToken]
            ) : '';

        return ['questionnaireUrl' => $questionnaireUrl, 'orcidQuestionnaireUrl' => $orcidQuestionnaireUrl];
    }
}
