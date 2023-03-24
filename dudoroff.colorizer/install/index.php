<?
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

Class dudoroff_colorizer extends CModule
	{
	    var $MODULE_ID = "dudoroff.colorizer";
	    var $MODULE_NAME;
        var $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arModuleVersion = array();

        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path."/version.php");

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }

        $this->PARTNER_NAME = "OffGroup"; 
        $this->PARTNER_URI = "https://off-group.com";
        $this->MODULE_NAME = Loc::getMessage("dudoroff.colorizer_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("dudoroff.colorizer_MODULE_DESC");
    }

    public function DoInstall()
	    {
	        global $APPLICATION, $step;
            RegisterModule($this->MODULE_ID);
            $this->InstallFiles();
            $this->InstallEvents();
            $this->InstallDB();
	    }

	    public function DoUninstall()
	    {
	        global $APPLICATION, $step;
            UnRegisterModule($this->MODULE_ID);
            $this->UnInstallEvents();
            $this->UnInstallFiles();

	    }

        public function InstallEvents()
    {
        EventManager::getInstance()->registerEventHandler(
            "main",
            "OnBeforeEndBufferContent",
            $this->MODULE_ID,
            "OffGroup\Module",
            "addScripts"
      );
     
       return true;
    }

    public function UnInstallEvents()
    {
        EventManager::getInstance()->unRegisterEventHandler(
            "main",
            "OnBeforeEndBufferContent",
            $this->MODULE_ID,
            "OffGroup\Module",
            "addScripts"
        );
       
         return true;
    }

    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__."/assets/scripts",
             Application::getDocumentRoot()."/bitrix/js/".$this->MODULE_ID."/",
            true,
            true
         );
     
       return true;
    }

    public function UnInstallFiles()
    {
        Directory::deleteDirectory(
            Application::getDocumentRoot()."/bitrix/js/".$this->MODULE_ID
         );
       
         return true;
    }
    
    public function InstallDB(){
        return true;
    }
}
