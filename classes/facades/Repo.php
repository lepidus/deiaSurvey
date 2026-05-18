<?php

namespace APP\plugins\generic\deiaSurvey\classes\facades;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\Repository as DeiaQuestionRepository;
use APP\plugins\generic\deiaSurvey\classes\deiaResponse\Repository as DeiaResponseRepository;
use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\Repository as DeiaResponseOptionRepository;

class Repo extends \APP\facades\Repo
{
    public static function deiaQuestion(): DeiaQuestionRepository
    {
        return app(DeiaQuestionRepository::class);
    }

    public static function deiaResponse(): DeiaResponseRepository
    {
        return app(DeiaResponseRepository::class);
    }

    public static function deiaResponseOption(): DeiaResponseOptionRepository
    {
        return app(DeiaResponseOptionRepository::class);
    }
}
