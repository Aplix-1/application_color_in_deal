<? 
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Service;
global $USER_FIELD_MANAGER;


Loc::loadMessages(__FILE__);

$request = HttpApplication::getInstance()->getContext()->getRequest();

$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);

if (!Loader::includeModule($module_id))
{
	ShowError(GetMessage('MODULE_NOT_INSTALLED'));
	return;
}
if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}
$options = Option::getForModule($module_id);
$fieldsList = [];
$fieldsList['null'] = Loc::getMessage("dudoroff.colorizer_CHOOSE_FIELD");
$CCrmFields = new CCrmFields($USER_FIELD_MANAGER, 'CRM_DEAL');
$CCrmFRes = $CCrmFields->GetFields();

$fieldsListLead = [];
$fieldsListLead['null'] = Loc::getMessage("dudoroff.colorizer_CHOOSE_FIELD");
$CCrmFieldsLead = new CCrmFields($USER_FIELD_MANAGER, 'CRM_LEAD');
$CCrmFResLead = $CCrmFieldsLead->GetFields();

$typesDB = Bitrix\Crm\Model\Dynamic\TypeTable::getList();
$types = $typesDB->fetchAll();

foreach($CCrmFRes as $ccrmfield)
{
	if($ccrmfield['USER_TYPE_ID'] !== 'enumeration' || $ccrmfield['MULTIPLE'] === 'Y') continue;
    $fieldsList[$ccrmfield['ID']] = $ccrmfield['LIST_COLUMN_LABEL'];
}
$isEmpty = empty($fieldsList) || count($fieldsList) === 1;

if($isEmpty) $fieldsList['null'] = Loc::getMessage("dudoroff.colorizer_NO_ENUM_FIELDS");

foreach($CCrmFResLead as $ccrmfield)
{
	if($ccrmfield['USER_TYPE_ID'] !== 'enumeration') continue;
    $fieldsListLead[$ccrmfield['ID']] = $ccrmfield['LIST_COLUMN_LABEL'];
}
$isEmptyLead = empty($fieldsListLead) || count($fieldsListLead) === 1;
if($isEmptyLead) $fieldsListLead['null'] = Loc::getMessage("dudoroff.colorizer_NO_ENUM_FIELDS");

$savedField = null;
if($options['main_field'] > 0){
	$savedField = $options['main_field'];
}
if($options['main_field_lead'] > 0){
	$savedFieldLead = $options['main_field_lead'];
}
	
$aTabs = array(
    array(
    	"DIV"       => "edit_deal",
    	"TAB"       => Loc::getMessage("dudoroff.colorizer_OPTIONS_TAB_NAME_DEAL"),
     	"TITLE"   => Loc::getMessage("dudoroff.colorizer_OPTIONS_TAB_NAME_DEAL"),
       	"OPTIONS" => array(
			Loc::getMessage("dudoroff.colorizer_OPTIONS_TAB_COMMON"),
			array(
				"main_field",
				Loc::getMessage("dudoroff.colorizer_OPTIONS_MAIN_FIELD"),
					$savedField,
				array("selectbox", $fieldsList),
				$isEmpty ? 'Y' : ''
			),
			Loc::getMessage("dudoroff.colorizer_OPTIONS_COLORS"),
			array(
				"color",
				Loc::getMessage("dudoroff.colorizer_OPTIONS_TAB_HEIGHT"),
				"50",
				array("color", 5)
			),
		)
	),
	array(
    	"DIV"       => "edit_lead",
    	"TAB"       => Loc::getMessage("dudoroff.colorizer_OPTIONS_TAB_NAME_LEAD"),
     	"TITLE"   => Loc::getMessage("dudoroff.colorizer_OPTIONS_TAB_NAME_LEAD"),
		"OPTIONS" => array(
			Loc::getMessage("dudoroff.colorizer_OPTIONS_TAB_COMMON"),
			array(
				"main_field_lead",
				Loc::getMessage("dudoroff.colorizer_OPTIONS_MAIN_FIELD"),
					$savedFieldLead,
				array("selectbox", $fieldsListLead, 'onChange="alert(\'changed\')"'),
				$isEmptyLead ? 'Y' : ''
			),
			Loc::getMessage("dudoroff.colorizer_OPTIONS_COLORS"),
			array(
				"color",
				Loc::getMessage("dudoroff.colorizer_OPTIONS_TAB_HEIGHT"),
				"50",
				array("color", 5)
			),
		)
   )
);

foreach ($types as $type) {
	$factory = Service\Container::getInstance()->getFactory($type['ENTITY_TYPE_ID']);
	$fieldList = [];
	$fieldList['null'] = Loc::getMessage("dudoroff.colorizer_CHOOSE_FIELD");
	$fields = $factory->getFieldsCollection();
	
	foreach($fields as $field){
		if($field->getType() !== 'enumeration' || $field->isMultiple()) continue;
		$fieldList[$field->getUserField()['ID']] = $field->getTitle();
	}

	$isEmptyType = empty($fieldList) || count($fieldList) === 1;
	if($isEmptyType) $fieldList['null'] = Loc::getMessage("dudoroff.colorizer_NO_ENUM_FIELDS");

	$aTabs[] = [
		"DIV"       => "edit_{$type['TABLE_NAME']}",
    	"TAB"       => normolizeTitle($type['TITLE']),
     	"TITLE"   => Loc::getMessage("dudoroff.colorizer_OPTIONS_TAB_NAME_SMART").normolizeTitle($type['TITLE']),
		"OPTIONS" => array(
			Loc::getMessage("dudoroff.colorizer_OPTIONS_TAB_COMMON"),
			array(
				"main_field_{$type['TABLE_NAME']}",
				Loc::getMessage("dudoroff.colorizer_OPTIONS_MAIN_FIELD"),
					$savedFieldLead,
				array("selectbox", $fieldList, 'onChange="alert(\'changed\')"'),
				$isEmptyType ? 'Y' : ''
			),
			Loc::getMessage("dudoroff.colorizer_OPTIONS_COLORS"),
			array(
				"color",
				Loc::getMessage("dudoroff.colorizer_OPTIONS_TAB_HEIGHT"),
				"50",
				array("color", 5)
			),
		)
	];
}

$tabControl = new CAdminTabControl(
	"tabControl",
   $aTabs
  );
  
  $tabControl->Begin();
?>
<form action="<? echo($APPLICATION->GetCurPage()); ?>?mid=<? echo($module_id); ?>&lang=<? echo(LANG); ?>" method="post">

<?
 foreach($aTabs as $aTab){

	if($aTab["OPTIONS"]){

	   $tabControl->BeginNextTab();

	   //__AdmSettingsDrawList($module_id, $aTab["OPTIONS"]);
	   foreach ($aTab["OPTIONS"] as $arOption) {
		   	if($arOption[0] !== 'color'){
				__AdmSettingsDrawRow($module_id, $arOption);
		   	}
			else {
				if($aTab['DIV'] === 'edit_deal' && $isEmpty)
					echo '<p class="hint">'.Loc::getMessage("dudoroff.colorizer_HINT").'</p>';
					
				if($aTab['DIV'] === 'edit_lead' && $isEmptyLead)
                    echo '<p class="hint">'.Loc::getMessage("dudoroff.colorizer_HINT").'</p>';
				?>
				<tr style="display: none" class="color-start"><td></td><td></td></tr>
				<?	
			}
		}
	}
 }

 $tabControl->Buttons();
?>

 <input type="submit" name="apply" value="<? echo(Loc::GetMessage("dudoroff.colorizer_OPTIONS_INPUT_APPLY")); ?>" class="adm-btn-save" />
  <input type="submit" name="default" value="<? echo(Loc::GetMessage("dudoroff.colorizer_OPTIONS_INPUT_DEFAULT")); ?>" />

 <?
 echo(bitrix_sessid_post());
?>

</form>
<?
$tabControl->End();

if($request->isPost() && check_bitrix_sessid()){

    foreach($aTabs as $aTab){

       foreach($aTab["OPTIONS"] as $arOption){

           if(!is_array($arOption)){

               continue;
           }

           	if($arOption["note"]){

                continue;
           	}

           	if($request["apply"]){

                $optionValue = $request->getPost($arOption[0]);

               	Option::set($module_id, $arOption[0], is_array($optionValue) ? implode(",", $optionValue) : $optionValue);
            }elseif($request["default"]){
             	Option::set($module_id, $arOption[0], $arOption[2]);
            }
	   }
	   foreach ($request->getPostList() as $key => $value) {
		   if(!preg_match('/colors_/', $key)) continue;
		   Option::set($module_id, str_replace('_deal', '', $key), json_encode($request->getPost($key)));
	   }
   }

   LocalRedirect($APPLICATION->GetCurPage()."?mid=".$module_id."&lang=".LANG);
}
?>
<script>
	BX.ready(()=>{
		BX.DudoroffColorizer.initAdmin();
		<? foreach ($options as $key => $value):
			if(!preg_match('/main_field/',  $key) || empty($options[$key])) continue;
			?>
			if(document.querySelector('[name="<?=$key?>"]')){
				BX.DudoroffColorizer.getVals('<?=$options[$key]?>', '<?=$key?>', document.querySelector('[name="<?=$key?>"]').closest('.adm-detail-content').id);
			}
		<? endforeach;?>
	});
</script>
<style>
    .hint{
        color: red;
    }
</style>
<?php
function normolizeTitle(String $title)
{
	if($title === 'Smart Invoice'){
		$title = Loc::getMessage("dudoroff.colorizer_SMART_INVOICE");
	}

	return $title;
}