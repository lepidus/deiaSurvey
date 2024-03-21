<?php

namespace APP\plugins\generic\demographicData\classes\facades;

use APP\plugins\generic\demographicData\classes\demographicQuestion\Repository as DemographicQuestionRepository;

class Repo extends \APP\facades\Repo
{
    public static function demographicQuestion(): DemographicQuestionRepository
    {
        return app(DemographicQuestionRepository::class);
    }
}
