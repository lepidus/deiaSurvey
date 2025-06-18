<?php

namespace APP\plugins\generic\deiaSurvey\pages\user;

use APP\plugins\generic\deiaSurvey\classes\DemographicDataService;

import('lib.pkp.pages.user.RegistrationHandler');

class CustomRegistrationHandler extends \RegistrationHandler
{
    public function register($args, $request)
    {
        parent::register($args, $request);

        $sessionManager = \SessionManager::getManager();
        $session = $sessionManager->getUserSession();
        $username = $session->getSessionVar('username');

        $userDao = \DAORegistry::getDAO('UserDAO');
        $user = $userDao->getByUsername($username);
        if (!$user) {
            return;
        }

        $context = $request->getContext();
        if (!$context) {
            return;
        }

        $demographicDataService = new DemographicDataService();
        $demographicDataService->migrateResponsesByUserIdentifier($context, $user, 'email');
    }
}
