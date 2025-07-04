<?php

namespace APP\plugins\generic\deiaSurvey\classes\observers\listeners;

use Illuminate\Events\Dispatcher;
use PKP\observers\events\UserRegisteredContext;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataService;

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

        $demographicDataService = new DemographicDataService();
        $demographicDataService->migrateResponsesByUserIdentifier($context, $user, 'email');
    }
}
