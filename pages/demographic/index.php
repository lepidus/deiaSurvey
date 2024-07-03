<?php

switch ($op) {
    case 'index':
    case 'saveQuestionnaire':
        define('HANDLER_CLASS', 'APP\plugins\generic\demographicData\pages\demographic\QuestionnaireHandler');
        break;
}
