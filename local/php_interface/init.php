<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
\CJSCore::Init(['jquery2']);

$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addCss('/local/css/mystyle.css');

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/handlers.php"))
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/handlers.php");

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/events.php"))
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/events.php");

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/local/vendor/autoload.php"))
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/vendor/autoload.php");

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/local/tools/googleSheetsApp/classes/GoogleSheetsConnector.php"))
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/tools/googleSheetsApp/classes/GoogleSheetsConnector.php");

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/local/tools/googleSheetsApp/classes/FormCreator.php"))
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/tools/googleSheetsApp/classes/FormCreator.php");

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/local/tools/googleSheetsApp/classes/SignParamsActions.php"))
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/tools/googleSheetsApp/classes/SignParamsActions.php");

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/local/tools/googleSheetsApp/classes/FieldsRefactor.php"))
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/tools/googleSheetsApp/classes/FieldsRefactor.php");

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/local/tools/googleSheetsApp/classes/DataHandler.php"))
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/tools/googleSheetsApp/classes/DataHandler.php");