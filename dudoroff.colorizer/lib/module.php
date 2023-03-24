<?
namespace OffGroup;

//use OffGroup\DataTable;
use \Bitrix\Main\Application,
    \Bitrix\Main\Web\Uri;
use \Bitrix\Main\Page\Asset;

class Module{

    public function __construct(){}

    public static function addScripts(){

        \CJSCore::RegisterExt(
            'dudoroff_colorizer',
            [
                'js' => '/bitrix/js/dudoroff.colorizer/script.js',
            ]
        );

        \CJSCore::Init('dudoroff_colorizer');

        $request = Application::getInstance()->getContext()->getRequest();
        $uriString = $request->getRequestUri();

        if(preg_match('#(/kanban/|/category/|/crm/type/(\d+)/|/crm/lead/list/)#', $uriString)){
            $isDeal = preg_match('#(/deal/)#', $uriString);
            $isLead = preg_match('#(/lead/)#', $uriString);
            $mathes = [];
            $isDT = preg_match('#/crm/type/(\d+)/#', $uriString, $mathes);
            $typeId = 0;
            if($isDT){
                $typeId = $mathes[1];
            }
            $type = $isDeal ? 2 : ($isLead ? 1 : $typeId);
            $asset = Asset::getInstance();
            $asset->addString("
                <script>
                BX.ready(()=>{
                    BX.DudoroffColorizer.init('$type'); 
                    BX.addCustomEvent('onAjaxSuccessFinish', ()=>{BX.DudoroffColorizer.init('$type')});
                });            
            </script>");
        }
    }

}