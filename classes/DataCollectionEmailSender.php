<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Capsule\Manager as DB;
use Context;

class DataCollectionEmailSender
{
    public function renewQuestionnaireLink(Context $context, int $authorId, string $expiredToken): bool
    {
        return DB::connection()->transaction(function () use ($context, $authorId, $expiredToken): bool {
            if (!DB::table('authors')->where('author_id', $authorId)->lockForUpdate()->first()) {
                return false;
            }
            $author = \Services::get('author')->get($authorId);
            if (!(new QuestionnaireTokenService())->canRenew($author, $expiredToken)) {
                return false;
            }
            $publication = \Services::get('publication')->get($author->getData('publicationId'));
            $submission = $publication ? \Services::get('submission')->get($publication->getData('submissionId')) : null;
            if (!$submission || (int) $submission->getData('contextId') !== $context->getId()) {
                return false;
            }
            // Sending inside this transaction preserves the expired secret if delivery fails.
            return $this->sendEmailToAuthor($submission, $author);
        });
    }

    public function sendEmailValidation(\Context $context, \User $user): bool
    {
        return Capsule::connection()->transaction(function () use ($context, $user): bool {
            $token = (new EmailAssociationService())->create($context, $user->getId());
            if ($token === null) {
                return false;
            }
            $user = \DAORegistry::getDAO('UserDAO')->getById($user->getId());
            $request = \Application::get()->getRequest();
            $locale = \AppLocale::getLocale();
            foreach (array_unique([MASTER_LOCALE, $locale]) as $emailLocale) {
                $file = dirname(__DIR__) . '/locale/' . $emailLocale . '/emails.po';
                if (is_file($file)) {
                    \AppLocale::registerLocaleFile($emailLocale, $file);
                }
            }
            import('lib.pkp.classes.mail.MailTemplate');
            $email = new \MailTemplate('CONFIRM_DEIA_EMAIL', $locale, $context, false);
            $template = \Services::get('emailTemplate')->getByKey($context->getId(), 'CONFIRM_DEIA_EMAIL');
            $subject = ($template ? $template->getData('subject', $locale) : null) ?: __('emails.confirmDeiaEmail.subject');
            $email->setSubject('[' . $context->getLocalizedAcronym() . '] ' . $subject);
            $email->setBody(($template ? $template->getData('body', $locale) : null) ?: __('emails.confirmDeiaEmail.body'));
            $email->setFrom($context->getData('contactEmail'), $context->getData('contactName'));
            $email->setRecipients([['email' => $user->getEmail(), 'name' => $user->getFullName()]]);
            $sent = $email->sendWithParams([
                'associationUrl' => htmlspecialchars($request->getDispatcher()->url(
                    $request,
                    ROUTE_PAGE,
                    $context->getPath(),
                    'deiaAssociation',
                    'index',
                    null,
                    ['userId' => $user->getId(), 'token' => $token]
                ), ENT_QUOTES, 'UTF-8'),
                'recipientName' => htmlspecialchars($user->getFullName(), ENT_QUOTES, 'UTF-8'),
                'contactName' => htmlspecialchars($context->getData('contactName'), ENT_QUOTES, 'UTF-8'),
                'contextName' => htmlspecialchars($context->getLocalizedName(), ENT_QUOTES, 'UTF-8'),
                'contextUrl' => $request->getDispatcher()->url($request, ROUTE_PAGE, $context->getPath()),
            ]);
            if (!$sent) {
                throw new \RuntimeException('DEIA association confirmation could not be sent.');
            }
            return true;
        });
    }

    public function sendRequestDataCollectionEmails(int $submissionId)
    {
        $submission = \Services::get('submission')->get($submissionId);
        $nonRegisteredAuthors = $this->getNonRegisteredAuthors($submission);

        if (!empty($nonRegisteredAuthors)) {
            $deiaDataService  = new DeiaDataService();

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
        return DB::connection()->transaction(function () use ($submission, $author): bool {
            if (!DB::table('authors')->where('author_id', $author->getId())->lockForUpdate()->first()) {
                return false;
            }
            $author = \Services::get('author')->get($author->getId());
            $request = \Application::get()->getRequest();
            $context = $request->getContext();
            $publication = \Services::get('publication')->get($author->getData('publicationId'));
            if (!$context || !$publication || (int) $publication->getData('submissionId') !== (int) $submission->getId()
                || (int) $submission->getContextId() !== (int) $context->getId()) {
                return false;
            }
            import('lib.pkp.classes.mail.MailTemplate');
            $email = new \MailTemplate('REQUEST_COLLECTION_CONTRIBUTOR_DATA', null, $context, false);
            $urls = $this->getQuestionnaireUrls($request, $author);
            $email->setFrom($context->getData('contactEmail'), $context->getData('contactName'));
            $email->setRecipients([['name' => $author->getFullName(), 'email' => $author->getEmail()]]);
            if (!$email->sendWithParams(array_merge($urls, [
                'submissionTitle' => htmlspecialchars((string) $publication->getLocalizedTitle(), ENT_QUOTES, 'UTF-8'),
                'contactName' => htmlspecialchars((string) $context->getData('contactName'), ENT_QUOTES, 'UTF-8'),
            ]))) {
                throw new \RuntimeException('DEIA questionnaire invitation could not be sent.');
            }
            return true;
        });
    }

    private function getQuestionnaireUrls($request, $author): array
    {
        $authorToken = (new QuestionnaireTokenService())->create($author);

        $questionnaireUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_PAGE,
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
                ROUTE_PAGE,
                null,
                'deiaQuestionnaire',
                'orcidAuthorize',
                null,
                ['authorId' => $author->getId(), 'authorToken' => $authorToken]
            ) : '';

        return ['questionnaireUrl' => $questionnaireUrl, 'orcidQuestionnaireUrl' => $orcidQuestionnaireUrl];
    }
}
