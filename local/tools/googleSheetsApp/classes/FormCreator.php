<?php
namespace GoogleSheetsApp;

use GoogleSheetsApp\GoogleSheetsConnector;

/**
 * класс для формирования html форм в зависимости от результата ajax запроса
 */
class FormCreator
{
    /**
     * @var $fields array|\string[][] дополнительные (дефолтные поля CRM) для добавления в таблицу/**
     * @var $availibleEntities array|string[] доступные сущности CRM
     * @var $entityType string тип сущности по умолчанию
     * @var $spreadsheetId string id google таблицы
     * @var $listName string имя листа google таблицы
     * @var $configFilepath string путь к файлу конфигураций, должен быть в одной директории с файлом класса
     * @var $headers array заголовки таблицы
     * @var $selectedFields array уже выбранные по соответствию поля сущности CRM
     */
    private array $fields = [
        [
            'field_code' => 'ID',
            'field_title' => 'ID сущности',
        ],
        [
            'field_code' => 'TITLE',
            'field_title' => 'Название',
        ],
        [
            'field_code' => 'ASSIGNED_BY_ID',
            'field_title' => 'Ответственный',
        ],
        [
            'field_code' => 'ITERATOR',
            'field_title' => 'Счетчик',
        ],
        [
            'field_code' => 'STAGE_ID',
            'field_title' => 'Стадия',
        ],
    ];
    private static array $availibleEntities = [
        'LEAD' => 'Лид',
        'DEAL' => 'Сделка',
        'COMPANY' => 'Компания',
        'CONTACT' => 'Контакт'
    ];
    private static string $entityType = 'DEAL';
    private static string $spreadsheetId = '';
    private static string $listName = '';
    private static string $configFilepath = __DIR__ . '/configs_';
    private static array $headers;
    private static $selectedFields;

    public function __construct()
    {
        self::setEntityType();
    }

    /**
     * метод устанавливает тип сущности CRM, id таблицы и имя листа таблицы
     */
    private static function setEntityType()
    {
        $res = json_decode(file_get_contents(self::$configFilepath . mb_strtolower(self::$entityType) . '.json'), true);

        if ($res['entity_type']) {
            self::$entityType = $res['entity_type'];
            self::$spreadsheetId = $res['spreadsheet_id'];
            self::$listName = $res['list_name'];
        }

    }

    /**
     * метод создает массив имен и кодов полей сущности CRM
     */
    private function setFields()
    {
        \CModule::IncludeModule('crm');
        $entityId = \CCrmOwnerType::ResolveID(self::$entityType);
        $resUserFields = \Bitrix\Crm\UserField\UserFieldManager::getUserFieldEntity($entityId)->GetFields();
        foreach ($resUserFields as $userFieldCode => $item) {
            $this->fields[] = [
                'field_code' => $userFieldCode,
                'field_title' => $item['EDIT_FORM_LABEL']
            ];
        }
    }

    /**
     * метод возвращает форму настройки соответствия столюцов таблицы и полей CRM
     * @param $entity_type string тип сущности CRM
     * @return string
     */
    public function getFieldsForm($entity_type)
    {
        self::$entityType = $entity_type;
        self::setEntityType();
        $this->setFields();
        $this->setHeaders();
        self::setSelectedFields();
        return $this->htmlFieldsFormCreate();
    }

    /**
     * метод устанавливает заголовки таблицы по типу сущности CRM
     */
    private function setHeaders()
    {
        $googleSheets = new GoogleSheetsConnector(self::$entityType);
        self::$headers = $googleSheets::$headers;
    }

    /**
     * метод указывает уже выбранные соответствия колонок таблицы и полей сущности CRM
     */
    private static function setSelectedFields()
    {
        $res = json_decode(file_get_contents(self::$configFilepath . mb_strtolower(self::$entityType) . '.json'), true);
        if (is_array($res) && !empty($res['entity_table_fields'])) {
            self::$selectedFields = $res['entity_table_fields'];
        } else {
            self::$selectedFields = [];
        }
    }

    /**
     * html формы выбора сущности, указания id таблицы и имя листа для подключения
     */
    public function htmlEntityFormCreate()
    {?><div class="block main_container">
            <div class="box entity_form_container has-background-warning-light">
                <form class="entity_form_container" method="post">
                    <div class="inputs_area">
                        <div class="field is-horizontal input_container">
                            <div class="field-label is-normal">
                                <label class="label">Введите ID таблицы Google: </label>
                            </div>
                            <div class="field-body">
                                <div class="field has-addons">
                                    <div class="control" style="width: 203px">
                                        <input class="input input_field" id="spreadsheet_id" name="spreadsheet_id" type="text" value="<?=self::$spreadsheetId?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="field is-horizontal input_container">
                            <div class="field-label is-normal">
                                <label class="label">Введите название листа таблицы: </label>
                            </div>
                            <div class="field-body">
                                <div class="field has-addons">
                                    <div class="control" style="width: 203px">
                                        <input class="input input_field" id="list_name" name="list_name" type="text" value="<?=self::$listName?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="input_container field is-horizontal is-bordered">
                        <div class="field-label is-normal">
                            <label class="label">Выберите тип сущности: </label>
                        </div>
                        <div class="field-body">
                            <div class="field has-addons">
                                <div class="control" style="width: 203px">
                                    <div class="select">
                                        <select id="entity_select" name="entity_type">
                                            <option value="">Не выбрано</option><?php
                                            foreach (self::$availibleEntities as $key => $typeName) {
                                                $selected = '';
                                                if (!empty(self::$entityType) && self::$entityType == $key) {
                                                    $selected = 'selected';
                                                }
                                                ?><option value="<?=$key?>" <?=$selected?>><?=$typeName?></option><?php
                                            }
                                            ?></select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                    <button class="button is-success entity_select_buttom">Сохранить</button>
                </form>
            </div>
        </div>
        <script>
            $('select[name=entity_type]').on('change', function () {
                let entityType = $(this).val();
                if (entityType === 'DEAL') {
                    $.ajax({
                        url: "/local/tools/googleSheetsApp/ajax.php",
                        type:     "POST",
                        dataType: "json",
                        data: {
                            action: 'getDealCategories',
                            entity_type: entityType,
                        },
                        success: function(result) {
                            $('div.inputs_area').append(result);
                        },
                        error: function (result) {
                            console.log(result);
                        }
                    });
                } else {
                    $('div.inputs_area div.stage').remove();
                }
            });
            $(function () {
                $('select[name=entity_type]').on('change', function () {
                    if ($('div.form_container').length > 0) {
                        $('div.form_container').remove();
                    }
                    $.ajax({
                        url: "/local/tools/googleSheetsApp/ajax.php",
                        type:     "POST",
                        dataType: "json",
                        data: {
                            action: 'getTableParams',
                            entity_type: $(this).val(),
                        },
                        success: function(result) {
                            console.log(result);
                            $('#spreadsheet_id').val(result['spreadsheet_id']);
                            $('#list_name').val(result['list_name']);
                        },
                        error: function (result) {
                            console.log(result);
                        }
                    });
                });
                $('.entity_select_buttom').on('click', function (e) {
                    e.preventDefault();
                    let buttonText = $('.entity_select_buttom').text();
                    if (buttonText.indexOf('Изменить') > -1) {
                        $('div.form_container').remove();
                    }
                    let category;
                    if ($('#category_select') !== undefined) {
                        category = $('#category_select').val();
                    } else {
                        category = '';
                    }
                    $.ajax({
                        url: "/local/tools/googleSheetsApp/ajax.php",
                        type:     "POST",
                        dataType: "json",
                        data: {
                            action: 'signEntityType',
                            spreadsheet_id: $('#spreadsheet_id').val(),
                            list_name: $('#list_name').val(),
                            entity_type: $('#entity_select').val(),
                            category_id: category
                        },
                        success: function(result) {
                            console.log(result);
                            $('.entity_select_buttom').text('Изменить');
                            $('.main_container').append(result);
                        },
                        error: function (res) {
                            console.log(res);
                            alert("Проверьте заполненность всех полей!")
                        }
                    });
                })
            })
        </script>
        <?php
    }

    /**
     * html код формы выбора соответствия полей сущности CRM и заголовков таблицы
     */
    private function htmlFieldsFormCreate()
    {
        $html = '
        <div class="box form_container has-background-warning-light">
            <form class="google_form" action="SignParamsAction.php" method="post">';
        foreach (self::$headers as $key => $header) {
            if (count(self::$selectedFields) > 0) {
                $neededField = array_filter(self::$selectedFields, function ($v) use ($header){
                    return $v['field_name'] === $header;
                });
                $neededField = current($neededField);
            }

            $html .= '<div class="input_container field is-horizontal">
                    <div class="field-label is-normal">
                        <label class="label" style="width: 90%">' . $header . '</label>
                    </div>
                    <div class="field-body">
                        <div class="field is-expanded">
                            <div class="control">
                                <div class="is-fullwidth" style="padding-top: 0.375em">
                                    <select class="js-example-basic-single" name="field_name_' . $key . '">
                                        <option value="">Не выбрано</option>';
                                            foreach ($this->fields as $field) {
                                                $selected = '';
                                                if ($neededField['columnName'] == $field['field_code']) {
                                                    $selected = 'selected';
                                                }
                                                $html .= '<option value="'. $field['field_code'] . '" ' . $selected . '>' . $field['field_title'] . '</option>';
                                            }
                                $html .='</select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><hr align="center" color="e9e9e9" size="1px">';
        }
        $html .= '<button class="google_fields_buttom button is-success" type="submit">Сохранить</button>
            </form>
        </div>
        <script>
            $(".js-example-basic-single").select2();
            $(".google_fields_buttom").on("click", function (e){
                e.preventDefault();
                let columnsName =' . json_encode(self::$headers) .';
                let formData = $("form.google_form").serializeArray();
                let result = [];
                $.each(columnsName, function (i, v) {
                    result.push({"field_name": v, "columnName": formData[i].value});
                });
                $.ajax({
                    url: "/local/tools/googleSheetsApp/ajax.php",
                    type:     "POST",
                    dataType: "json",
                    data: {
                        action: "signEntityAndTableFields",
                        fields: result,
                        entity_type: $("select[name=entity_type]").val()
                    },
                    success: function(formData) {
                        if (formData.result == "error") {
                            alert(formData.text);
                        } else {
                            $("div.main_container").html("<span>Настройки успешно сохранены.</span>");
                            console.log(formData);   
                        }
                    },
                    error: function (formData) {
                        console.log(formData);
                    }
                });
            });
        </script>';
        return $html;
    }
}