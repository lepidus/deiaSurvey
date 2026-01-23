<?php

namespace APP\plugins\generic\deiaSurvey\report\classes;

class ContextStatistics
{
    private int $usersConsentCount;
    private int $usersNotConsentCount;

    public function __construct()
    {
        $this->usersConsentCount = 0;
        $this->usersNotConsentCount = 0;
    }

    public function incrementUsersConsentCount(): void
    {
        $this->usersConsentCount++;
    }

    public function getUsersConsentCount(): int
    {
        return $this->usersConsentCount;
    }

    public function incrementUsersNoConsentCount(): void
    {
        $this->usersNotConsentCount++;
    }

    public function getUsersNoConsentCount(): int
    {
        return $this->usersNotConsentCount;
    }
}
