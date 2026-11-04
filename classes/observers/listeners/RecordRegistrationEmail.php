<?php

namespace APP\plugins\generic\deiaSurvey\classes\observers\listeners;

use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use Illuminate\Events\Dispatcher;
use PKP\observers\events\UserRegisteredContext;

class RecordRegistrationEmail
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(UserRegisteredContext::class, self::class);
    }

    public function handle(UserRegisteredContext $event): void
    {
        (new DeiaDataService())->recordRegistrationEmail($event->recipient);
    }
}
