<?php

namespace APP\plugins\generic\demographicData\classes\observers\listeners;

use Illuminate\Events\Dispatcher;
use PKP\observers\events\UserRegisteredContext;
use APP\plugins\generic\demographicData\classes\facades\Repo;
use APP\plugins\generic\demographicData\classes\DemographicDataDAO;

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

        $contextQuestions = Repo::demographicQuestion()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany()
            ->toArray();

        if (empty($contextQuestions)) {
            return;
        }

        $questionsIds = array_map(function ($question) {
            return $question->getId();
        }, $contextQuestions);

        $userResponses = Repo::demographicResponse()->getCollector()
            ->filterByExternalIds([$user->getEmail()])
            ->filterByExternalTypes(['email'])
            ->filterByQuestionIds($questionsIds)
            ->getMany();

        foreach ($userResponses as $response) {
            Repo::demographicResponse()->edit($response, [
                'userId' => $user->getId(),
                'externalId' => null,
                'externalType' => null
            ]);
        }

        $demographicDataDao = new DemographicDataDAO();
        $demographicDataDao->updateDemographicConsent($context->getId(), $user->getId(), true);
    }
}
