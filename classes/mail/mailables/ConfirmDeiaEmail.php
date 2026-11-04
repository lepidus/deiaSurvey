<?php

namespace APP\plugins\generic\deiaSurvey\classes\mail\mailables;

use PKP\context\Context;
use PKP\mail\Mailable;
use PKP\mail\traits\Configurable;
use PKP\mail\traits\Recipient;

class ConfirmDeiaEmail extends Mailable
{
    use Configurable;
    use Recipient;

    protected static ?string $name = 'emails.confirmDeiaEmail.name';
    protected static ?string $description = 'emails.confirmDeiaEmail.description';
    protected static ?string $emailTemplateKey = 'CONFIRM_DEIA_EMAIL';

    public function __construct(Context $context, array $variables)
    {
        parent::__construct([$context]);
        $this->addData($variables);
    }

    public static function getDataDescriptions(): array
    {
        return array_merge(parent::getDataDescriptions(), [
            'contextName' => __('emailTemplate.variable.context.contextName'),
            'contextUrl' => __('emailTemplate.variable.context.contextUrl'),
            'associationUrl' => __('plugins.generic.deiaSurvey.emailValidation.urlDescription'),
        ]);
    }
}
