<?php

switch ($op) {
    case 'index':
    case 'saveQuestionnaire':
    case 'deleteData':
    case 'orcidVerify':
    case 'orcidAuthorize':
        return new \APP\plugins\generic\deiaSurvey\pages\deia\QuestionnaireHandler();
        break;
}
