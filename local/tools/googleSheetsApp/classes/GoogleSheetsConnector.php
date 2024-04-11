<?php
namespace GoogleSheetsApp;

use Google_Client;
use Google\Service\Sheets as Google_Service_Sheets;
use Google\Service\Sheets\ValueRange as Google_Service_Sheets_ValueRange;
use GoogleSheetsApp\DataHandler;

/**
 * класс для работы с подключением и работой с гугл-таблицей
 */
class GoogleSheetsConnector
{
    /**
     * @var string $accountKeyPath - путь до файла ключа доступа от аккаунта разработчика в googlecloudpanel
     * @var string $configPath - путь до файла конфигураций листа таблицы
     * @var string $spreadsheetId - id гугл таблицы
     * @var string $listName - имя листа для заполнения, должно быть обязательно на латинице
     * @var object $service - объект для работы с гугл таблицей, добавлоение, чтение, редактирование, удаление
     * @var string $entityType - тип сущности с которой будет работать конкретный лист
     * @var array $rows - массив существубщих строк таблицы
     * @var array $configs - массив настроек заголовков и данных таблицы и листа для работы
     * @var array $headers - заголоки таблицы (1 строчка в таблице)
     */
    private static string $accountKeyPath = '/local/tools/googleSheets/service_key.json';
    private static string $configPath = '/local/tools/googleSheetsApp/classes/configs_';
    private static string $spreadsheetId;
    private static string $listName;
    private static $service;
    private static $entityType;
    public static $rows;
    public static array $configs;
    public static array $headers;


    public function __construct($entityType)
    {
        self::$entityType = $entityType;
        $configs = file_get_contents($_SERVER['DOCUMENT_ROOT'] . self::$configPath . mb_strtolower($entityType) . '.json');
        $configs = json_decode($configs, true);
        if (is_array($configs)) {

            self::$configs = $configs;

            if (!empty($configs['spreadsheet_id'])) {
                self::$spreadsheetId = $configs['spreadsheet_id'];
            }
            if (!empty($configs['list_name'])) {
                self::$listName = $configs['list_name'];
            }
        }
        $accountKeyPath = $_SERVER['DOCUMENT_ROOT'] . self::$accountKeyPath;
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $accountKeyPath);

        // Создаем новый клиент
        $client = new Google_Client();
        // Устанавливаем полномочия
        $client->useApplicationDefaultCredentials();

        // Добавляем область доступа к чтению, редактированию, созданию и удалению таблиц
        $client->addScope('https://www.googleapis.com/auth/spreadsheets');
        self::$service = new Google_Service_Sheets($client);
        // полчаем все строки из таблицы для дальнейшей установки заголовков

        self::setRows();
        // устанавливаем заголовки
        $this->setHeaders();
    }

    /**
     * @return void устанавливает заголовки из листа таблицы
     */
    private function setHeaders()
    {
        self::$headers = self::$rows[0];
    }

    /**
     * @return array получает заголовки из листа таблицы
     */
    public function getHeaders()
    {
        return self::$headers;
    }

    /**
     * @return array устанавливает все строки из листа таблицы
     */
    private static function setRows()
    {
        $response = self::$service->spreadsheets_values->get(self::$spreadsheetId, self::$listName);
        self::$rows = $response->values;
    }

    /**
     * @return string возвращает диапазон ячеек для добавления новых значений
     */
    public function getAppendRange()
    {
        $rangeLetters = $this->getRangeLetters();
        $range = self::$listName . '!' . $rangeLetters['first_column_letter'] . '1:' . $rangeLetters['last_column_letter'];
        return $range;
    }

    /**
     * @param void $rowNum номер строки для формирования диапазона
     * @return string возвращает диапазон ячеек в котром редактируется строка
     */
    public function getUpdateRange($rowNum)
    {
        $rangeLetters = $this->getRangeLetters();
        $range = self::$listName . '!' . $rangeLetters['first_column_letter'] . $rowNum . ':' . $rangeLetters['last_column_letter'] . $rowNum;
        return $range;
    }

    /**
     * @return int|string|null возвращает ключ колонки-итератора таблицы типа № п/п
     */
    public function getIteratorColumnKey()
    {
        $headers = self::$headers;
        $fieldsSync = self::$configs['entity_table_fields'];
        $neededFieldName = array_filter($fieldsSync, function ($v) {
            return $v['columnName'] === 'ITERATOR';
        });
        $neededFieldName = current($neededFieldName);

        $headerColKey = array_filter($headers, function ($v) use ($neededFieldName) {
            return $v === $neededFieldName['field_name'];
        });
        $headerColKey = key($headerColKey);

        return $headerColKey;
    }

    /**
     * @param int|string $headerColKey ключ колонки-итератора таблицы типа № п/п
     * @return mixed возвращает значение из последней строки колонки итератора
     */
    public function getAddedRowNum($headerColKey)
    {
        $lastNum = end(self::$rows)[$headerColKey];
        return $lastNum;
    }

    /**
     * @param $entityId string|int|null ID сущности CRM
     * @return array возвращает строку таблицы со всеми колонками
     */
    public function searchEntityRow($entityId = '')
    {
        $headers = self::$headers;
        $fieldsSync = self::$configs['entity_table_fields'];
        $neededFieldName = array_filter($fieldsSync, function ($v) {
            return $v['columnName'] === 'ID';
        });
        $neededFieldName = current($neededFieldName);

        $headerColKey = array_filter($headers, function ($v) use ($neededFieldName) {
            return $v === $neededFieldName['field_name'];
        });

        $headerColKey = key($headerColKey);

        $neededEntity = array_filter(self::$rows, function ($v) use ($entityId, $headerColKey) {
            return $v[$headerColKey] == $entityId;
        });

        return $neededEntity;
    }

    /**
     * @param $values array значения для таблицы
     * @return Google_Service_Sheets_ValueRange
     */
    public function getValueRange($values)
    {
        $valueRange = new Google_Service_Sheets_ValueRange([
            'values' => ['values' => $values]
        ]);
        return $valueRange;
    }

    /**
     * метод добавляет данные в таблицу
     * @param $values array значения для добавления в таблицу
     * @return void
     */
    public function appendData($values)
    {
        $options = ['valueInputOption' => 'USER_ENTERED'];
        $range = $this->getAppendRange();
        $ValueRange = $this->getValueRange($values);
        self::$service->spreadsheets_values->append(self::$spreadsheetId, $range, $ValueRange, $options);
    }

    /**
     * метод редактирует данные в таблице
     * @param $values array значения для добавления в таблицу
     * @param $rowNum string|int номер строки для редактирования
     * @return void
     */
    public function updateData($values, $rowNum)
    {
        $range = $this->getUpdateRange($rowNum);
        $ValueRange = $this->getValueRange($values);
        $options = ['valueInputOption' => 'USER_ENTERED'];
        self::$service->spreadsheets_values->update(self::$spreadsheetId, $range, $ValueRange, $options);
    }

    /**
     * @return array возвращает буквенные обозначения первой и последней заполненной колонки в таблице
     */
    public function getRangeLetters()
    {
        $response = self::$service->spreadsheets_values->get(self::$spreadsheetId, self::$listName);
        $allRangeInfo = $response->getRange();
        list($listName, $range) = explode('!', $allRangeInfo);
        list($rangeBegin, $rangeEnd) = explode(':', $range);
        $firstLetterRange = preg_replace('/[0-9]/', '', $rangeBegin);
        $lastLetterRange = preg_replace('/[0-9]/', '', $rangeEnd);
        return ['first_column_letter' => $firstLetterRange, 'last_column_letter' => $lastLetterRange];
    }
}