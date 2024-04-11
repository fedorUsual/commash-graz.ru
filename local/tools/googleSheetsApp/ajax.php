<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once __DIR__ . '/classes/GoogleSheetsConnector.php';
require_once __DIR__ . '/classes/FormCreator.php';
require_once __DIR__ . '/classes/SignParamsActions.php';
require_once __DIR__ . '/classes/FieldsRefactor.php';
require_once __DIR__ . '/classes/DataHandler.php';

use GoogleSheetsApp\FormCreator;
use GoogleSheetsApp\SignParamsActions;
use GoogleSheetsApp\GoogleSheetsConnector;

if (isset($_POST['action']) && !empty($_POST['action'])) {
    CModule::IncludeModule('crm');
    $actionClass = new SignParamsActions();
    $result = $actionClass::action($_POST['action']);
    echo json_encode($result);
}