<?php
class PageDef{
    private $pageName, $name, $isCssSeparated, $isJsSeparated, $isManual;

    private $pageRootPath, $moduleRootPath, $componentRootPath;

    private $definition, $moduleProcesser, $fileHelper, $filePath;

    private $jsPath, $cssPath;
    
    public function PageDef($pageRootPath, $moduleRootPath, $componentRootPath, 
            $pageName, $fileHelper){

        $this->pageRootPath = $pageRootPath;
        $this->moduleRootPath = $moduleRootPath;
        $this->componentRootPath = $componentRootPath;
        $this->pageName = $pageName;
        $this->getDefinition($pageName);
        $this->reservedWords = array('js','css','name', 'tmpl', 'modules', 'without', 'inlinejs', 'pack', 'jsPath', 'cssPath');
    }

    public function processPage(){
        print_r($this->filePath);
    }

    public function getFilePath(){
        return $this->filePath;
    }

    public function checkIsManual(){
        if(!isset($this->isManual)){
            $this->isManual = !isset($this->definition['modules']);
        }
        return $this->isManual;
    }
    public function getName(){
        return $this->name;
    }

    private function getDefinition($pageName){
        $path = "{$this->pageRootPath}/{$pageName}";
        $fileName;
        $definition;
        $errorMessage = '';
        $templatePath;
        if(is_dir($path)){
            $fileName = "$path/definition.php";
            if(file_exists($fileName)){
                $definition = $this->definition = include_once($fileName);
                $this->name = isset($this->definition['name']) ? 
                        $this->definition['name'] : $pageName;
            }
            else{
                $errorMessage = "definition.php is missing in page $pageName\n";
            }
        }
        if(!isset($definition)){
            echo isset($errorMessage)? "there's no such page $pageName\n" : 
                    $errorMessage;
            die(1);
        }
        $fileName = FALSE;
        //
        // get Template of page
        //
        if(isset($this->definition['pageTemplate'])){
            //
            // TODO: functionality of assigning a page template
            //
        }
        else if(is_dir("$path/template")){
            $dirObj = opendir("$path/template");
            while( ($fileName = readdir($dirObj)) != FALSE){
                $extension = substr($fileName, strrpos($fileName, '.'));
                if($extension == '.html'){
                    break;
                }
            }
            closedir($dirObj);
        }
        if($fileName != FALSE){
            $this->filePath = "$path/template/$fileName";
        }
        else{
            echo "there's no page template for page [$this->pageName]\n";
            die(1);
        }
    }

    public function & getTemplates(){
        return $this->definition['tmpl'];
    }

    public function & getJsFiles(){
        return $this->definition['js'];
    }

    public function & getCssFiles(){
        return $this->definition['css'];
    }

    public function & getVariable($variableName){
        if(in_array($variableName, $this->reservedWords)){
            return FALSE;
        }
        return $this->definition[$variableName];
    }

    public function getJsFileType($packageName){
        $retStr = 'ext';
        if(!$this->checkIsManual() && isset($this->definition['js'][$packageName]['type'])){
            $retStr = $this->definition['js'][$packageName]['type'];
        }
        else if(isset($this->definition['inlinejs'][$packageName])){
            $retStr = 'inline';
        }
        return $retStr;
    }
    public function isPackageDefined($packageName, $type){
        if($this->checkIsManual()){
            return isset($this->definition[$type][$packageName]);            
        }
        else{
            if($type == 'css'){
                $isSeparated = $this->isCssSeparated();
            }
            else if($type == 'js'){
                $isSeparated = $this->isJsSeparated();                
            }

            if($isSeparated){
                return array_key_exists($packageName, $this->definition['modules']);
            }
            else{
                $keys = array_keys($this->definition['modules']);
                return $packageName == $keys[0];
            }
        }
    }

    public function getJsPath(){
        if(!isset($this->jsPath)){
            $jsPath = $this->definition['jsPath'];
            $jsPath = trim($jsPath);
            if ($jsPath[strlen($jsPath) - 1] == '/') {
                $jsPath = substr($jsPath, 0, strlen($jsPath) - 1);
            }
            $this->jsPath = $jsPath;
        }
        return $this->jsPath;
    }
    public function getCssPath(){
        if(!isset($this->cssPath)){
            $cssPath = $this->definition['cssPath'];
            $cssPath = trim($cssPath);
            if ($cssPath[strlen($cssPath) - 1] == '/') {
                $cssPath = substr($cssPath, 0, strlen($cssPath) - 1);
            }
            $this->cssPath = $cssPath;
        }
        return $this->cssPath;
    }
    public function & getJsOverrideFiles(){
        $pageName = $this->pageName;
        $rootPath = $this->pageRootPath;
        $jsDirPath = "$rootPath/$pageName/static/js";
        $jsOverrideFiles = array();
        if (is_dir($jsDirPath)) {
            $dirObj = opendir($jsDirPath);
            while( ($fileName = readdir($dirObj)) != FALSE ){
                $extension = substr($fileName, strrpos($fileName, '.'));
                if($extension == '.js'){
                    array_push($jsOverrideFiles, array("path"=>"$jsDirPath/$fileName", "name" => $fileName));
                }
            }
        }
        return $jsOverrideFiles;
    }
    public function & getCssOverrideFiles(){
        $pageName = $this->pageName;
        $rootPath = $this->pageRootPath;
        $cssDirPath = "$rootPath/$pageName/static/css";
        $cssOverrideFiles = array();
        if (is_dir($cssDirPath)) {
            $dirObj = opendir($cssDirPath);
            while( ($fileName = readdir($dirObj)) != FALSE ){
                $extension = substr($fileName, strrpos($fileName, '.'));
                if($extension == '.css'){
                    array_push($cssOverrideFiles, array("path"=>"$cssDirPath/$fileName", "name" => $fileName));
                }
            }
        }
        return $cssOverrideFiles;
    }
    public function isCssSeparated(){
        if(!isset($this->isCssSeparated)){
            if(!isset($this->definition['pack']['css'])){
                $this->isCssSeparated = FALSE;
            }
            else{
                $this->isCssSeparated = $this->definition['pack']['css'] == TRUE ?
                        TRUE : FALSE; 
            }
        }
        return $this->isCssSeparated;
    }
    public function isJsSeparated(){
        if(!isset($this->isJsSeparated)){
            if(!isset($this->definition['pack']['js'])){
                $this->isJsSeparated = FALSE;
            }
            else{
                $this->isJsSeparated = $this->definition['pack']['js'] == TRUE ?
                        TRUE : FALSE; 
            }
        }
        return $this->isJsSeparated;
    }
    public function getIgnoreJsPackage(){
        $ignoredPackage = $this->getIngorePackage('js');
        return is_null($ignoredPackage) ? array() : $ignoredPackage;
    }
    public function getIgnoreCssPackage(){
        $ignoredPackage = $this->getIngorePackage('css');
        return is_null($ignoredPackage) ? array() : $ignoredPackage;
    }
    private function getIngorePackage($type){
        if(!isset($this->definition['without'][$type])){
            return null;
        }
        return $this->definition['without'][$type];
    }
    public function getModules(){
        return $this->definition['modules'];
    }
}