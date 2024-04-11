<?php
use GoogleSheetsApp\FieldsRefactor;
use GoogleSheetsApp\GoogleSheetsConnector;
use GoogleSheetsApp\FormCreator;
use GoogleSheetsApp\SignParamsActions;
use GoogleSheetsApp\DataHandler;

AddEventHandler('crm', "OnBeforeCrmDealAdd", 'addObservers');
function addObservers(&$arFields) {
    global $USER;

    // воронка сделки
    $dealCategory = 'C4';
    $dealStage = 'C4:PREPAYMENT_INVOICE';
    if ($arFields['STAGE_ID'] == $dealStage) {
        if (is_array($arFields['UF_CRM_1667222830']) && count($arFields['UF_CRM_1667222830']) > 0) {
            $newObservers = $arFields['UF_CRM_1667222830'];

            if (in_array('24', $arFields['UF_CRM_1667222830'])) {
                $newObservers = array_merge($newObservers, ['45']);
            }
            if (in_array('6', $arFields['UF_CRM_1667222830'])) {
                $newObservers = array_merge($newObservers, ['8']);
            }
            $newObservers = array_unique($newObservers);

            if (is_array($arFields['OBSERVER_IDS'])) {
                $arFields['OBSERVER_IDS'] = array_merge($arFields['OBSERVER_IDS'], $newObservers);
            } else {
                $arFields['OBSERVER_IDS'] = $newObservers;
            }
        }
    }
}
AddEventHandler('crm', 'OnBeforeCrmDealUpdate', 'updateObservers');
function updateObservers(&$arFields) {
    global $USER;

    // получаем все данные сделки
    $deal = \CCrmDeal::GetList('', ['ID' => $arFields['ID'], 'CHECK_PERMISSIONS' => 'N'])->GetNext();
    $dealStage = 'C4:PREPAYMENT_INVOICE';

    // если сделка находится или переведена в стадию Первичный анализ
    if ((!isset($arFields['STAGE_ID']) && $deal['STAGE_ID'] == $dealStage) || (isset($arFields['STAGE_ID']) && $arFields['STAGE_ID'] == $dealStage)) {

        // поле менеджер берем или из полей события, если в них нет, то берем из поля сделки
        $managers = $arFields['UF_CRM_1667222830'] ?? $deal['UF_CRM_1667222830'];
        if (!is_array($managers)) {
            $managers = [];
        }

        if (count($managers) > 0) {

            // собираем всех текущих наблюдателей по сделке
            $observers = [];
            $obFilter = [
                'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
                'ENTITY_ID' => $arFields['ID']
            ];
            $resObservers = \Bitrix\Crm\Observer\Entity\ObserverTable::getList(['filter' => $obFilter]);
            while ($observer = $resObservers->fetch()) {
                $observers[] = $observer['USER_ID'];
            }

            // выбираем ответственного по сделке, либо только назначенного (если изменение сделки вызвано именно изменение ответственного)
            // либо который уже был
            $assignedBy = $arFields['ASSIGNED_BY_ID'] ?? $deal['ASSIGNED_BY_ID'];
            $assignedKey = array_search($assignedBy, $observers);

            // удаляем ответственного из наблюдателей
            if ($assignedKey && is_numeric($assignedKey)) {
                unset($observers[$assignedKey]);
            }

            // соединяем ответственных с пользователями из поля "Менеджер"
            $newObservers = array_merge($observers, $managers);

            // если поле Менеджер есть Остапович, то добавляем в наблюдатели еще одного пользователя
            if (in_array('24', $newObservers)) {
                $newObservers = array_merge($newObservers, ['45']);
            }
            if (in_array('6', $newObservers)) {
                $newObservers = array_merge($newObservers, ['8']);
            }
            $newObservers = array_unique($newObservers);

            // если изменение вызвано сменой наблюдателей, то объединяем новое значение наблюдателей
            // с нашим списком наблюдателей
            if (is_array($arFields['OBSERVER_IDS'])) {
                $arFields['OBSERVER_IDS'] = array_merge($arFields['OBSERVER_IDS'], $newObservers);
            } else {
                $arFields['OBSERVER_IDS'] = $newObservers;
            }
        }
    }
}

AddEventHandler('crm', 'OnAfterCrmDealAdd', 'addSignOnGoogleSheet');
function addSignOnGoogleSheet(&$arFields)
{
    global $USER;

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
}

AddEventHandler('crm', 'OnAfterCrmDealUpdate', 'updateSignOnGoogleSheet');
function updateSignOnGoogleSheet(&$arFields)
{
    global $USER;
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
    file_put_contents(
        $_SERVER["DOCUMENT_ROOT"] . '/local/test.json',
        json_encode($arFields)
    );
    file_put_contents(
        $_SERVER["DOCUMENT_ROOT"] . '/local/tools/googleSheetsApp/logs/'.date('d-m-Y').'_log.log',
        date('d.m.Y H:i:s') . ' сделка ' . $arFields['ID'] . ' не прошла проверку по логу тайминга в файле. Стадия: ' . $deal['STAGE_ID'] . PHP_EOL,
        FILE_APPEND
    );
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
        file_put_contents(
            $_SERVER["DOCUMENT_ROOT"] . '/local/tools/googleSheetsApp/logs/'.date('d-m-Y').'_log.log',
            date('d.m.Y H:i:s') . ' В обработку попала сделка ' . $arFields['ID'] . '. Данные по последней сделке: ' . print_r($lastDeal, true) . PHP_EOL,
            FILE_APPEND
        );
        if (!is_array($lastDeal) || $arFields['ID'] != $lastDeal['ID'] || (time() - (int)$lastDeal['time']) > 2) {
            $handler = new DataHandler('DEAL');
            $entityData = $handler->fillValues($arFields['ID'], 'update');
            file_put_contents(
                $_SERVER["DOCUMENT_ROOT"] . '/local/tools/googleSheetsApp/logs/'.date('d-m-Y').'_log.log',
                date('d.m.Y H:i:s') . ' ' . print_r($entityData, true),
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
}

