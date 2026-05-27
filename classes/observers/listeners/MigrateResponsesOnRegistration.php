<?php

namespace APP\plugins\generic\deiaSurvey\classes\observers\listeners;

use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use Illuminate\Events\Dispatcher;
use PKP\observers\events\UserRegisteredContext;

class MigrateResponsesOnRegistration
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            UserRegisteredContext::class,
            MigrateResponsesOnRegistration::class
        );
    }

    public function handle(UserRegisteredContext $event): void
    {
        $user = $event->recipient;
        $context = $event->context;

        $deiaDataService = new DeiaDataService();
        $deiaDataService->migrateResponsesByUserIdentifier($context, $user, 'email');
    }
}
