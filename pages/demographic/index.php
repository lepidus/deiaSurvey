<?php

switch ($op) {
    case 'index':
    case 'saveQuestionnaire':
    case 'deleteData':
    case 'orcidVerify':
        define('HANDLER_CLASS', 'APP\plugins\generic\deiaSurvey\pages\demographic\QuestionnaireHandler');
        break;
}
