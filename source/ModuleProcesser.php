<?php
//
// 用于在page级别统一进行module的解析和相关copy操作，防止重复copy
// 既每一个page都有一个module processer
//
$thisFilePath = dirname(__FILE__);
include_once("{$thisFilePath}/ModuleDef.php");
include_once("{$thisFilePath}/ComponentProcesser.php");

class ModuleProcesser{
    //
    // static 
    //
    private $moduleRootPath, $componentRootPath;
    //
    // modules
    //
    private $fileHelper, $modulesLoaded, $moduleDefs, $requireList, $manual;
    //
    // component related
    //
    private $jsComponentProcesser, $cssComponentProcesser;

    public $fileList;

    public function ModuleProcesser($fileHelper, $moduleRootPath, $componentRootPath, $manual=false){
        $this->fileHelper = $fileHelper;
        $this->moduleRootPath = $moduleRootPath;
        $this->componentRootPath = $componentRootPath;
        $this->modulesLoaded = array(
                'tmpl'=> array(),
                'js'=> array(),
                'css'=> array()
            );

        $this->jsComponentProcesser = new ComponentProcesser($fileHelper, $componentRootPath);
        $this->cssComponentProcesser = new ComponentProcesser($fileHelper, $componentRootPath);
        $this->fileList = array();
        $this->moduleDefs = array();
        $this->requireList = array();
        $this->manual = $manual;
    }
    public function processModuleTempl($moduleName){
        if(in_array($moduleName, $this->requireList)){
            echo "there is a circular reference when dealing $moduleName module tmpl\n";
            print_r($this->requireList);
            die(1);
        }

        if(in_array($moduleName, $this->modulesLoaded['tmpl'])){
            return;
        }
        else{
            array_push($this->requireList, $moduleName);
        }
        $moduleDefObj = $this->getModuleDef($moduleName);
        //
        // 先处理依赖的module
        //
        if(isset($moduleDefObj->requireModules)){
            foreach ($moduleDefObj->requireModules as $requireModule) {
                $this->processModuleTempl($requireModule);
            }
        }
        //
        // 处理本模块template
        //
        $name = $this->processTemplate($moduleName);
        array_pop($this->requireList);
        //
        // 记录已完成模块
        //
        $this->modulesLoaded['tmpl'][] = $moduleName;
        return $name;
    }
    public function processModuleJs($moduleName, $packageName){
        if(in_array($moduleName, $this->requireList)){
            echo "there is a circular reference when dealing $moduleName module js\n";
            print_r($this->requireList);
            die(1);
        }

        if(in_array($moduleName, $this->modulesLoaded['js'])){
            return ;
        }
        else{
            array_push($this->requireList, $moduleName);
        }

        $moduleDefObj = $this->getModuleDef($moduleName);
        //
        // 先处理依赖的module
        //
        if(!isset($this->fileList[$packageName])){
            $this->fileList[$packageName] = array(
                    'js' => array()
                );
        }
        if(isset($moduleDefObj->requireModules)){
            foreach ($moduleDefObj->requireModules as $requireModule) {
                $this->processModuleJs($requireModule, $packageName);
            }
        }
        //
        // 然后处理本模块所引用的component
        //
        if(is_array($moduleDefObj->jsComponents)){
            foreach ($moduleDefObj->jsComponents as $jsComponent) {
                $this->jsComponentProcesser->processComponentJs($jsComponent, &$this->fileList[$packageName]['js']);
            }
        }
        //
        // 处理本模块js
        //
        $this->processJs($moduleName, $packageName);
        array_pop($this->requireList);
        //
        // 记录已完成模块
        //
        $this->modulesLoaded['js'][] = $moduleName;
    }
    public function processModuleCss($moduleName, $packageName){
        if(in_array($moduleName, $this->requireList)){
            echo "there is a circular reference when dealing $moduleName module css\n";
            print_r($this->requireList);
            die(1);
        }

        if(in_array($moduleName, $this->modulesLoaded['css'])){
            return ;
        }
        else{
            array_push($this->requireList, $moduleName);
        }
        
        $moduleDefObj = $this->getModuleDef($moduleName);
        //
        // 先处理依赖的module
        //
        if(!isset($this->fileList[$packageName])){
            $this->fileList[$packageName] = array(
                    'css' => array()
                );
        }
        if(isset($moduleDefObj->requireModules)){
            foreach ($moduleDefObj->requireModules as $requireModule) {
                $this->processModuleCss($requireModule, $packageName);
            }
        }
        //
        // 然后处理本模块所引用的component
        //
        if(is_array($moduleDefObj->cssComponents)){
            foreach ($moduleDefObj->cssComponents as $cssComponent) {
                $this->cssComponentProcesser->processComponentCss($cssComponent, &$this->fileList[$packageName]['css']);
            }
        }
        //
        // 处理本模块css
        //
        $this->processCss($moduleName, $packageName);
        //
        // 记录已完成模块
        //
       array_pop($this->requireList);
        $this->modulesLoaded['css'][] = $moduleName;
    }
    private function getModuleDef($moduleName){
        if(!isset($this->moduleDefs[$moduleName])){
            $this->moduleDefs[$moduleName] = new ModuleDef($this->moduleRootPath, $moduleName);
        }
        return $this->moduleDefs[$moduleName];
    }
    private function processTemplate($moduleName){
        $generateTemplate = FALSE;

        $moduleDefObj = $this->getModuleDef($moduleName);

        if( ($moduleDefObj->templateDirPath == '') && !is_dir("{$this->moduleRootPath}/{$moduleName}/template")){
            if($this->manual && count($this->requireList) == 1){
                echo "there's no [$moduleName] module tmpl\n";
                die(1);
            }
            return ;
        }
        $this->fileHelper->copyTmplFileWithAssignedName($moduleName, "{$moduleDefObj->name}.html");

        return $moduleDefObj->name;
    }
    private function processJs($moduleName, $packageName){
        if(!is_dir("{$this->moduleRootPath}/{$moduleName}/static/js")){
            if($this->manual && count($this->requireList) == 1){
                echo "there's no [$moduleName] module js\n";
                die(1);
            }
            return;
        }

        $this->fileHelper->copyJsFileFromModule($moduleName, &$this->fileList[$packageName]['js']);
    }
    private function processCss($moduleName, $packageName){
        if(!is_dir("{$this->moduleRootPath}/{$moduleName}/static/css")){
            if($this->manual && count($this->requireList) == 1){
                echo "there's no [$moduleName] module css\n";
                die(1);
            }
            return;
        }
        
        $this->fileHelper->copyCssFileFromModule($moduleName, &$this->fileList[$packageName]['css']);
        $this->fileHelper->copyImgFileFromModule($moduleName);
    }
}
?> 