<?php
//
// 用于在page级别统一进行Component的解析和相关copy操作，防止重复copy
// 既每一个page都有一个Component processer
//
$thisFilePath = dirname(__FILE__);
include_once("{$thisFilePath}/ComponentDef.php");
class ComponentProcesser{
    private $fileHelper, $componentRootPath;
    
    private $componentsLoaded, $componentDefs, $requireList;


    public function ComponentProcesser($fileHelper, $componentRootPath){
        $this->fileHelper = $fileHelper;
        $this->componentRootPath = $componentRootPath;

        $this->componentsLoaded = array(
            'tmpl'=> array(),
            'css'=> array(),
            'js'=> array()
            );
        $this->requireList = array();
    }
    public function processComponentTmpl($componentName){
        if(in_array($componentName, $this->requireList)){
            echo "there is a circular reference when dealing $componentName component\n";
            print_r($this->requireList);
            die(1);
        }

        if(in_array($componentName, $this->componentsLoaded['tmpl'])){
            return;
        }
        else{
            array_push($this->requireList,$componentName);
        }
        
        $componentDefObj = $this->getcomponentDef($componentName);

        if(isset($componentDefObj->requireComponents['tmpl'])){
            foreach ($componentDefObj->requirecomponents['tmpl'] as $requireComponent) {
                $this->processComponentTmpl($requireComponent);
            }
        }
        $this->processTmpl($componentName);

        array_pop($this->requireList);
        $this->componentsLoaded['tmpl'][] = $componentName;
    }
    public function processComponentCss($componentName, &$fileList){
        if(in_array($componentName, $this->requireList)){
            echo "there is a circular reference when dealing $componentName component\n";
            print_r($this->requireList);
            die(1);
        }

        if(in_array($componentName, $this->componentsLoaded['css'])){
            return;
        }
        else{
            array_push($this->requireList,$componentName);
        }

        $componentDefObj = $this->getcomponentDef($componentName);

        if(isset($componentDefObj->requireComponents['css'])){
            foreach ($componentDefObj->requirecomponents['css'] as $requireComponent) {
                $this->processComponentCss($requireComponent);
            }
        }
        $this->processCss($componentName, &$fileList);

        array_pop($this->requireList);
        $this->componentsLoaded['css'][] = $componentName;
    }
    public function processComponentJs($componentName, &$fileList){
        if(in_array($componentName, $this->requireList)){
            echo "there is a circular reference when dealing $componentName component\n";
            print_r($this->requireList);
            die(1);
        }
        
        if(in_array($componentName, $this->componentsLoaded['js'])){
            return;
        }
        else{
            array_push($this->requireList,$componentName);
        }

        $componentDefObj = $this->getComponentDef($componentName);

        if(isset($componentDefObj->requireComponents)){
            foreach ($componentDefObj->requireComponents['js'] as $requireComponent) {
                $this->processComponentJs($requireComponent);
            }
        }
        $this->processJs($componentName, &$fileList);

        array_pop($this->requireList);
        $this->componentsLoaded['js'][] = $componentName;
    }
    private function getComponentDef($componentName){
        if(!isset($this->componentDefs[$componentName])){
            $this->componentDefs[$componentName] = new ComponentDef($this->componentRootPath, $componentName);
        }

        return $this->componentDefs[$componentName];
    }
    private function processJs($componentName, &$fileList){
        if(!is_dir("{$this->componentRootPath}/{$componentName}/static/js")){
            return;
        }
        $this->fileHelper->copyJsFileFromComponent($componentName, &$fileList);
    }
    private function processCss($componentName, &$fileList){
        if(!is_dir("{$this->componentRootPath}/{$componentName}/static/css")){
            return;
        }
        $this->fileHelper->copyCssFileFromComponent($componentName, &$fileList);
        $this->fileHelper->copyCssFileFromComponent($componentName);
    }
    private function processTmpl($componentName, &$fileList){
        if(!is_dir("{$this->componentRootPath}/{$componentName}/template")){
            return;
        }
        $this->fileHelper->copyTmplFileFromComponent($componentName);
    }
}
?>