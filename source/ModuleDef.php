<?php
class ModuleDef{
    private $moduleRootPath, $moduleName;
    private $jsToBeLoaded, $cssToBeLoaded, $definition;

    public $name, $jsComponents, $cssComponents, $requireModules, $templateDirPath;
    
    public function ModuleDef($moduleRootPath, $moduleName){
        $this->moduleName = $moduleName;
        $this->moduleRootPath = $moduleRootPath;

        $this->process();
    }
    private function process(){
        $modulePath = "{$this->moduleRootPath}/{$this->moduleName}";
        $extension;
        $fileName;
        $filePath = '';
        if(is_dir($modulePath)){
            $dirObj = opendir($modulePath);
            while( ($fileName = readdir($dirObj)) != FALSE ){
                $extension = substr($fileName, strrpos($fileName, '.'));
                if($extension == '.php'){
                    $filePath = "{$modulePath}/{$fileName}";
                    break;
                }
            }
        }
        if($filePath != ''){
            $this->definition = include($filePath);
            isset($this->definition['name']) ? $this->name = $this->definition['name'] :
                    $this->name = substr($fileName, 0, strrpos($fileName, '.'));

            $this->getComponentsCss();
            $this->getComponentsJs();
            $this->getDependencies();
            $this->getTemplate();
        }
    }
    private function getComponentsCss(){
        if(!isset($this->definition['components']['css'])){
            return;
        }
        $this->cssComponents = $this->definition['components']['css'];
    }
    private function getComponentsJs(){
        if(!isset($this->definition['components']['js'])){
            return;
        }
        $this->jsComponents = $this->definition['components']['js'];
    }
    private function getDependencies(){
        if(!isset($this->definition['require'])){
            return;
        }
        $this->requireModules = $this->definition['require'];
    }
    private function getTemplate(){
        if(isset($this->definition['template'])){
            $this->templateDirPath = $this->definition['template'];
        }
        else{
            $this->templateDirPath = '';
        }
    }
}