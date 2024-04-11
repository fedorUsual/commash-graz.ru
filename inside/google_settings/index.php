<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/tools/googleSheetsApp/classes/GoogleSheetsConnector.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/tools/googleSheetsApp/classes/FormCreator.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/tools/googleSheetsApp/classes/SignParamsActions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/tools/googleSheetsApp/classes/FieldsRefactor.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/tools/googleSheetsApp/classes/DataHandler.php';

use GoogleSheetsApp\FieldsRefactor;
use GoogleSheetsApp\GoogleSheetsConnector;
use GoogleSheetsApp\FormCreator;
use GoogleSheetsApp\SignParamsActions;
use GoogleSheetsApp\DataHandler;

global $USER;
if ($USER->IsAuthorized()) {
    global $APPLICATION;
    $APPLICATION->SetTitle('Настройка Google таблиц');
    ?>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <?php
    $newGoogle = new FormCreator();
    $newGoogle->htmlEntityFormCreate();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';