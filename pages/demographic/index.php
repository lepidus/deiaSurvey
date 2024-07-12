<?php

switch ($op) {
    case 'index':
    case 'saveQuestionnaire':
    case 'orcidVerify':
        define('HANDLER_CLASS', 'APP\plugins\generic\demographicData\pages\demographic\QuestionnaireHandler');
        break;
}
