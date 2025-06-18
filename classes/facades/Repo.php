<?php

namespace APP\plugins\generic\deiaSurvey\classes\facades;

use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\Repository as DemographicQuestionRepository;
use APP\plugins\generic\deiaSurvey\classes\demographicResponse\Repository as DemographicResponseRepository;
use APP\plugins\generic\deiaSurvey\classes\demographicResponseOption\Repository as DemographicResponseOptionRepository;

class Repo
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
