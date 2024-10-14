<?php

namespace APP\plugins\generic\demographicData\classes\facades;

use APP\plugins\generic\demographicData\classes\demographicQuestion\Repository as DemographicQuestionRepository;
use APP\plugins\generic\demographicData\classes\demographicResponse\Repository as DemographicResponseRepository;
use APP\plugins\generic\demographicData\classes\demographicResponseOption\Repository as DemographicResponseOptionRepository;

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

    public static function demographicResponseOption(): DemographicResponseOptionRepository
    {
        return app(DemographicResponseOptionRepository::class);
    }
}
