<?php
class ComponentDef{
    private $componentRootPath, $componentName;

    //
    // {js: '', css: '', tmpl:''}
    //
    public $requireComponents;

    public function ComponentDef($componentRootPath, $componentName){
        $this->componentRootPath = $componentRootPath;
        $this->componentName = $componentName;

        $this->process();
    }
    private function process(){ 
        $componentPath = "{$this->componentRootPath}/{$this->componentName}";
        if(!file_exists("{$componentPath}/require.php")){
            return;
        }
        $fileName = "{$componentPath}/require.php";
        $this->definition = include_once($fileName);

    }
    private function getDependencies(){
        if(!isset($this->definition['require'])){
            return;
        }
        $this->requireComponents = $this->definition['require'];
    }
}