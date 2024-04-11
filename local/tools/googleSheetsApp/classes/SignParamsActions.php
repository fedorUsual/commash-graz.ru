<?php
namespace GoogleSheetsApp;

use GoogleSheetsApp\FormCreator;

/**
 * класс для обработки аякс запросов
 */
class SignParamsActions
{
    /**
     * @var string путь до файла настроек, должен находится в одной директории с данным классом
     */
    private static $configFilepath = __DIR__ . '/configs_';

    public static function action($actionName)
    {
        return self::$actionName();
    }

    /**
     * @return string записывает данные о выбранном типе сущности и формирует относительно этого
     * форму для заполнения соответствия заголовков таблицы и полей сущности CRM
     */
    public static function signEntityType()
    {
        $entityType = $_POST['entity_type'];
        $resultArr = json_decode(file_get_contents(self::$configFilepath . mb_strtolower($entityType) . '.json'), true);
        $resultArr['spreadsheet_id'] = $_POST['spreadsheet_id'];
        $resultArr['list_name'] = $_POST['list_name'];
        $resultArr['entity_type'] = $entityType;
        file_put_contents(self::$configFilepath . mb_strtolower($entityType) . '.json', json_encode($resultArr));
        $fieldForm = new FormCreator();
        return $fieldForm->getFieldsForm($entityType);
    }

    /**
     * @return void записыввает массив соответствия заголовков таблицы и полей сущности CRM
     */
    private static function signEntityAndTableFields()
    {
        $entityType = $_POST['entity_type'];
        $entityTableFields = $_POST['fields'];
        $entityIdField = array_filter($_POST['fields'], function ($v) {
            return $v['columnName'] === 'ID';
        });
        if (count($entityIdField) <= 0) {
            return ['result' => 'error', 'text' => 'Поле ID сущности обязательно должно присутствовать в таблице, иначе соотнесение данных CRM и гугл таблицы будет невозможно!'];
        }

        $result = json_decode(file_get_contents(self::$configFilepath . mb_strtolower($entityType) . '.json'), true);
        $result['entity_table_fields'] = $entityTableFields;
        file_put_contents(self::$configFilepath . mb_strtolower($entityType) . '.json', json_encode($result));
        return ['result' => 'ok', 'text' => self::$configFilepath . mb_strtolower($entityType) . '.json'];
    }

    /**
     * @return mixed возвращает параметры гугл таблицы ее id и имя активного листа
     */
    public static function getTableParams()
    {
        $entityType = $_POST['entity_type'];
        $res = file_get_contents(self::$configFilepath . mb_strtolower($entityType) . '.json');
        $res = json_decode($res, true);
        $result = [
            'spreadsheet_id' => $res['spreadsheet_id'],
            'list_name' => $res['list_name']
        ];
        return $result;
    }

    public static function getDealCategories()
    {
        \CModule::IncludeModule('crm');
        $categories = [];
        $res = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal)->getCategories();

        foreach ($res as $obj) {
            $categories[$obj->getId()] = $obj->getName();
        }

        $html = '<div class="input_container field is-horizontal is-bordered stage">
        <div class="field-label is-normal">
            <label class="label">Выберите направление сделки: </label>
        </div>
        <div class="field-body">
            <div class="field has-addons">
                <div class="control" style="width: 203px">
                    <div class="select is-multiple">
                        <select id="category_select" name="category" multiple size="2">
                            <option value="">Не выбрано</option>';
        foreach ($categories as $key => $category) {
            $html .= '<option value="' . $key . '">' . $category . '</option>';
        }
        $html .= '</select>
                    </div>
                </div>
            </div>
        </div>
        </div>';

        return $html;
    }
}