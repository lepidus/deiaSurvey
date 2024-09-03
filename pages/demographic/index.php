<?php

switch ($op) {
    case 'index':
    case 'saveQuestionnaire':
    case 'deleteData':
    case 'orcidVerify':
        define('HANDLER_CLASS', 'APP\plugins\generic\demographicData\pages\demographic\QuestionnaireHandler');
        break;
}
