<?php

namespace APP\plugins\generic\demographicData\classes\mail\mailables;

use PKP\mail\Mailable;
use APP\journal\Journal;
use APP\submission\Submission;
use PKP\mail\traits\Configurable;

class RequestCollectionContributorData extends Mailable
{
    use Configurable;

    protected static ?string $name = 'emails.requestCollectionContributorData.name';
    protected static ?string $description = 'emails.requestCollectionContributorData.description';
    protected static ?string $emailTemplateKey = 'REQUEST_COLLECTION_CONTRIBUTOR_DATA';

    public function __construct(Journal $context, Submission $submission, array $variables)
    {
        parent::__construct([$context, $submission]);
        $this->addData($variables);
    }
}
