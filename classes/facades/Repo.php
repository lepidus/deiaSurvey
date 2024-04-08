<?php

namespace APP\plugins\generic\demographicData\classes\facades;

use APP\plugins\generic\demographicData\classes\demographicQuestion\Repository as DemographicQuestionRepository;
use APP\plugins\generic\demographicData\classes\demographicResponse\Repository as DemographicResponseRepository;

class Repo extends \APP\facades\Repo
{
    public static function demographicQuestion(): DemographicQuestionRepository
    {
        return app(DemographicQuestionRepository::class);
    }

    public static function demographicResponse(): DemographicResponseRepository
    {
        return app(DemographicResponseRepository::class);
    }
}
