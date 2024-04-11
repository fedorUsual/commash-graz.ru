<?php
use GoogleSheetsApp\FieldsRefactor;
use GoogleSheetsApp\GoogleSheetsConnector;
use GoogleSheetsApp\FormCreator;
use GoogleSheetsApp\SignParamsActions;
use GoogleSheetsApp\DataHandler;

AddEventHandler('crm', 'OnAfterCrmDealAdd', 'addSignOnGoogleSheet');
function addSignOnGoogleSheet(&$arFields)
{
    // воронка сделки
    $dealCategory = 'C4';

    // стадии на которых не смотрим изменения
    $dealStageNew = 'NEW';
    $dealStageBad = '16';
    $dealStageExport = '14';
    $dealStageAnalise = 'APOLOGY';
    $dealStageFirstAnalise = 'PREPAYMENT_INVOICE';

    if (strpos($arFields['STAGE_ID'], $dealCategory) !== false
        && strpos($arFields['STAGE_ID'], $dealStageNew) === false
        && strpos($arFields['STAGE_ID'], $dealStageBad) === false
        && strpos($arFields['STAGE_ID'], $dealStageExport) === false
        && strpos($arFields['STAGE_ID'], $dealStageAnalise) === false
        && strpos($arFields['STAGE_ID'], $dealStageFirstAnalise) === false)
    {
        $lastDeal = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/local/tools/deal.json'), true);
        if (empty($lastDeal['ID']) || $arFields['ID'] != $lastDeal['ID'] || (time() - (int)$lastDeal['time']) > 2) {
            $handler = new DataHandler('DEAL');
            $entityData = $handler->fillValues($arFields['ID'], 'add');
            file_put_contents(
                $_SERVER["DOCUMENT_ROOT"] . '/local/tools/googleSheetsApp/logs/'.date('d-m-Y').'_log.log',
                date('d.m.Y H:i:s') . ' ' . print_r($entityData, true),
                FILE_APPEND
            );
        }
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/local/tools/deal.json', json_encode(['ID' => $arFields['ID'],'time' => time()]));
    }
    if (!empty($arFields['UF_CRM_1667222830']) && !empty($arFields['OBSERVER_IDS']) && is_array($arFields['OBSERVER_IDS'])) {
        $arFields['OBSERVER_IDS'] = array_merge($arFields['OBSERVER_IDS'], $arFields['UF_CRM_1667222830']);
    }
}

AddEventHandler('crm', 'OnAfterCrmDealUpdate', 'updateSignOnGoogleSheet');
function updateSignOnGoogleSheet(&$arFields)
{
    // воронка сделки
    $dealCategory = 'C4';
    // id сделки
    $dealId = $arFields['ID'];
    // стадии на которых не смотрим изменения
    $dealStage = 'NEW';
    $dealStageBad = '16';
    $dealStageExport = '14';
    $dealStageAnalise = 'APOLOGY';
    $dealStageFirstAnalise = 'PREPAYMENT_INVOICE';

    // получаем все данные сделки
    $deal = \CCrmDeal::GetList('', ['ID' => $dealId, 'CHECK_PERMISSIONS' => 'N'])->GetNext();
    // смотрим только сделки в воронке Работа с тендерами и НЕ в статусе Новый тендер

    if (strpos($deal['STAGE_ID'], $dealCategory) !== false
        && strpos($deal['STAGE_ID'], $dealStage) === false
        && strpos($deal['STAGE_ID'], $dealStageBad) === false
        && strpos($deal['STAGE_ID'], $dealStageExport) === false
        && strpos($deal['STAGE_ID'], $dealStageAnalise) === false
        && strpos($deal['STAGE_ID'], $dealStageFirstAnalise) === false)
    {
        file_put_contents(
            $_SERVER["DOCUMENT_ROOT"] . '/local/tools/googleSheetsApp/logs/'.date('d-m-Y').'_log.log',
            date('d.m.Y H:i:s') . ' В обработку попала сделка ' . $arFields['ID'] . PHP_EOL,
            FILE_APPEND
        );

        $lastDeal = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/local/tools/deal.json'), true);
        if (!is_array($lastDeal) || $arFields['ID'] != $lastDeal['ID'] || (time() - (int)$lastDeal['time']) > 2) {
            $handler = new DataHandler('DEAL');
            $entityData = $handler->fillValues($arFields['ID'], 'update');
            file_put_contents(
                $_SERVER["DOCUMENT_ROOT"] . '/local/tools/googleSheetsApp/logs/'.date('d-m-Y').'_log.log',
                date('d.m.Y H:i:s') . ' ' . print_r(['fields' => $arFields, 'data' => $entityData], true),
                FILE_APPEND
            );
        } else {
            file_put_contents(
                $_SERVER["DOCUMENT_ROOT"] . '/local/tools/googleSheetsApp/logs/'.date('d-m-Y').'_log.log',
                date('d.m.Y H:i:s') . ' сделка ' . $arFields['ID'] . ' не прошла проверку по логу тайминга в файле. Стадия: ' . $deal['STAGE_ID'] . PHP_EOL,
                FILE_APPEND
            );
        }
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/local/tools/deal.json', json_encode(['ID' => $arFields['ID'],'time' => time()]));
    }

    if (isset($arFields['UF_CRM_1667222830']) && count($arFields['UF_CRM_1667222830'])) {
        if (count($arFields['UF_CRM_1667222830']) > 0) {
            \CCrmDeal::AddObserverIDs($dealId, $arFields['UF_CRM_1667222830']);
        } else {
            $arFields['OBSERVER_IDS'] = [];
        }
    }
}
