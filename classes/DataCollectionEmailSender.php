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
    public function renewQuestionnaireLink(Context $context, int $authorId, string $expiredToken): bool
    {
        return DB::transaction(function () use ($context, $authorId, $expiredToken): bool {
            if (!DB::table('authors')->where('author_id', $authorId)->lockForUpdate()->first()) {
                return false;
            }
            $author = Repo::author()->get($authorId);
            if (!(new QuestionnaireTokenService())->canRenew($author, $expiredToken)) {
                return false;
            }
            $publication = Repo::publication()->get($author->getData('publicationId'));
            $submission = $publication ? Repo::submission()->get($publication->getData('submissionId')) : null;
            if (!$submission || (int) $submission->getData('contextId') !== $context->getId()) {
                return false;
            }
            // Sending inside this transaction preserves the expired secret if delivery fails.
            return $this->sendEmailToAuthor($submission, $author);
        });
    }

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
                'contextName' => htmlspecialchars($context->getLocalizedName(), ENT_QUOTES, 'UTF-8'),
                'contextUrl' => $request->getDispatcher()->url($request, Application::ROUTE_PAGE, $context->getPath()),
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

    private function sendEmailToAuthor($submission, $author): bool
    {
        return DB::transaction(function () use ($submission, $author): bool {
            if (!DB::table('authors')->where('author_id', $author->getId())->lockForUpdate()->first()) {
                return false;
            }
            $author = Repo::author()->get($author->getId());
            $request = Application::get()->getRequest();
            $context = $request->getContext();
            $publication = Repo::publication()->get($author->getData('publicationId'));
            if (!$publication || (int) $publication->getData('submissionId') !== $submission->getId()
                || (int) $submission->getData('contextId') !== $context->getId()) {
                return false;
            }

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
            return true;
        });
    }

    private function getQuestionnaireUrls($request, $author): array
    {
        $authorToken = (new QuestionnaireTokenService())->create($author);

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
