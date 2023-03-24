<?php

namespace Dudoroff\Colorizer\Controller;

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Crm\Service;
Loc::loadMessages(__FILE__);

class Colorizer extends \Bitrix\Main\Engine\Controller
{
    private $ids;
    private $ans;
    public function applyAction($ids = [], $type = 'DEAL')
    {
        if(!\CModule::IncludeModuleEx('dudoroff.colorizer')
            || \CModule::IncludeModuleEx('dudoroff.colorizer') == 3
        ){
            $ans['errors'] = \CModule::IncludeModuleEx('dudoroff.colorizer') == 3 ? Loc::getMessage("MODULE_EXPIRED") : Loc::getMessage("MODULE_NOT_FOUND");
            $this->ans = $ans;
            return $this->ans = $ans;
        }
        if(empty($ids)) return ['response' => 'empty data'];
        $this->ids = $ids;
        $this->getColorize($type);
        return ['response' => $this->ans];
    }

    private function getColorize(int $type = 2){
            $success = false;
            if(!\CModule::IncludeModule('crm')){
                $ans['errors'] = Loc::getMessage("MODULE_CRM_NOT_FOUND");
                $this->ans = $ans;
                return $this->ans = $ans;
            }
            global $USER_FIELD_MANAGER;
            $ans = [];
            $ids = [];
            $colors = null;
            $colorsName = $type === 2 ? 'colors' : ($type === 1 ? 'colors_lead' : "colors_b_crm_dynamic_items_$type");
            $colorsJSON = \Bitrix\Main\Config\Option::get('dudoroff.colorizer', $colorsName);
            if($colorsJSON)
                $colors = json_decode($colorsJSON, 1);

            
            $fieldId = 0;
            $fieldCode = $type === 2 ? 'main_field' : ($type === 1 ? 'main_field_lead' : "main_field_b_crm_dynamic_items_$type");
            $fieldId = (int)\Bitrix\Main\Config\Option::get('dudoroff.colorizer', $fieldCode);

            $factory = Service\Container::getInstance()->getFactory($type);
            $fCode = $type === 2 ? 'CRM_DEAL' : ($type===1?'CRM_LEAD':$factory->GetUserFieldEntityId());
            if($type <= 4){
                $CCrmField = new \CCrmFields($USER_FIELD_MANAGER, $fCode);
                $CCrmFieldRes = $CCrmField->GetById($fieldId);
                $uf_field = $CCrmFieldRes['FIELD_NAME'];
            } else {
                $uFields = $factory->getFieldsCollection();
                foreach($uFields as $uField){
                    if((int)$uField->getUserField()['ID'] === $fieldId){
                        $uf_field = $uField->getName();
                        break;
                    }
                }
            }
            //$className = $type === 2 ?'CCrmDeal' : 'CCrmLead';
            
            $items = $factory->getItems([
                'filter' => [
                    "ID" => $this->ids,
                    "!$uf_field" => false
                ],
                'select' => ['ID', $uf_field]
            ]);
            
            if($colors){
                foreach($items as $item){
                    $ids[] = [
                        'ID' => $item['ID'],
                        'COLOR' => $colors[(int)$item->getCompatibleData()[$uf_field]],
                    ];
                    $success = true;
                }
            }
            if($success){
                $ans = [
                    'status' => 'success',
                    'ids' => $ids,
                    'uField' => $uField,
                    'fCode' => $fCode
                ];
            } else {
                $ans['errors'] = 'Что-то пошло не так';
                $ans['some'] = $_GET['deals'];
            }
            $this->ans = $ans;
    }

    public function fieldvalueAction($fieldId = 0, $name = 'main_field'){
        if($fieldId === 0 || !$fieldId) return ['error' => Loc::getMessage("NO_FIELD_ID")];
        $colors = null;
        if($name === 'main_field'){
            $colorsJSON = \Bitrix\Main\Config\Option::get('dudoroff.colorizer', 'colors');
            if($colorsJSON)
                $colors = json_decode($colorsJSON, 1);
        }
        else{
            $name = str_replace('main_field_', '', $name);
            $colorsJSON = \Bitrix\Main\Config\Option::get('dudoroff.colorizer', 'colors_'.$name);
            if($colorsJSON)
                $colors = json_decode($colorsJSON, 1);
        }

        $enumEntity = new \CUserFieldEnum();
        $enumResult = $enumEntity->GetList([], ['USER_FIELD_ID' => $fieldId]);
        $result = [];
        foreach($enumResult->arResult as $res){
            $result[] = [
                'ID' => $res['ID'],
                'VALUE' => $res['VALUE'],
                'HEX' => $colors[$res['ID']],
                'NAME' => $name
            ];
        }

        return $result;
    }
}