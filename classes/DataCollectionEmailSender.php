<?php

namespace APP\plugins\generic\deiaSurvey\classes;

use APP\core\Application;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\mail\mailables\RequestCollectionContributorData;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Mail;
use PKP\plugins\PluginRegistry;

class DataCollectionEmailSender
{
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
                'associationUrl' => $request->getDispatcher()->url(
                    $request,
                    ROUTE_PAGE,
                    $context->getPath(),
                    'deiaAssociation',
                    'index',
                    null,
                    ['userId' => $user->getId(), 'token' => $token]
                ),
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

        $author = \Services::get('author')->edit($author, ['deiaToken' => $authorToken], $request);

        $questionnaireUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_PAGE,
            null,
            'deiaQuestionnaire',
            null,
            null,
            ['authorId' => $author->getId(), 'authorToken' => $authorToken]
        );

        $plugin = \PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $contextId = $request->getContext()->getId();
        $orcidClient = new OrcidClient($plugin, $contextId);

        $orcidQuestionnaireUrl = $orcidClient->buildOAuthUrl([
            'authorId' => $author->getId(),
            'authorToken' => $authorToken
        ]);

        return ['questionnaireUrl' => $questionnaireUrl, 'orcidQuestionnaireUrl' => $orcidQuestionnaireUrl];
    }
}
