<?php
//
// 该类每个page拥有一个实例，用于整合单页的资源
// 
$thisFilePath = dirname(__FILE__);
include_once("{$thisFilePath}/PageDef.php");
include_once("{$thisFilePath}/ModuleProcesser.php");

class TestProcesser{
    private $pageName, $fileHelper, $mode;

    private $pageRootPath, $moduleRootPath, $componentRootPath, $staticPath, $templatePath;

    private $moduleProcesser;

    private $jsOverrideVariable = '', $cssOverridePackageName = '', $fileContent;

    public function TestProcesser($pageRootPath, $moduleRootPath, $componentRootPath, 
                $pageName, $fileHelper, $staticPath, $templatePath, &$definition, $module){

        $this->pageRootPath = $pageRootPath;
        $this->moduleRootPath = $moduleRootPath;
        $this->componentRootPath = $componentRootPath;
        $this->pageName = $pageName;
        $this->fileHelper = $fileHelper;
        $this->product = 'unittest';
        $this->module = $module;

        $staticPath = $this->adjustPath(trim($staticPath));
        $templatePath = $this->adjustPath(trim($templatePath));

        $this->staticPath = $staticPath;
        $this->templatePath = $templatePath;

        $this->mode = 'debug';

        $this->pageDef = new PageDef($pageRootPath, $pageName, $fileHelper, $definition);            
    }
    public function process(){
        if (!is_dir("{$this->moduleRootPath}/{$this->module}/unittest")){
            echo "there no unittest file in [$module] module\n";
            die(1);
        }
        $filePath = $this->pageDef->getFilePath();
        //
        // step 1: 先分析page template，得到所有的token，variable，type
        //
        $matchs = $this->analyzePageTemplate($filePath);
        // print_r($matchs);
        // 
        $tokens = $matchs[0];
        $variables = $matchs[1];
        $types = $matchs[2];

        $jsFilesOrderList = array();
        $cssFilesOrderList = array();
        $valuedVariables = array();
        //
        // step 2: 分析js/css的加载顺序，根据variable在template中出现的顺序作为加载顺序
        //
        foreach ($types as $key=>$value) {
            $variable = $variables[$key];
            $packageList = $this->pageDef->getVariable($variable);
            $valuedVariables[$variable] = array();
            if($packageList === FALSE || count($packageList) == 0){
                continue;
            }
            if($value == 'js'){
                foreach ($packageList as $package) {
                    if($this->pageDef->isPackageDefined($package, 'js')){
                        array_push($jsFilesOrderList, $package);
                        array_push($valuedVariables[$variable], $package);
                        $this->jsOverrideVariable = $variable;
                    }
                }
            }
            else if($value == 'css'){
                foreach ($packageList as $package) {
                    if($this->pageDef->isPackageDefined($package, 'css')){
                        array_push($cssFilesOrderList, $package);     
                        array_push($valuedVariables[$variable], $package);           
                        $this->cssOverridePackageName = $package;
                    }
                }
            }
        }
        //
        // step 3: 开始合并模块，根据definition的规则开始进行合并
        // 分为2中: 
        // 1). 当有modules存在时，作为自动加载方式，使用沉默错误方式进行加载
        // 2). 当没有modules时，按照手动方式加载
        //
        $isManual = $this->pageDef->checkIsManual();

        $this->moduleProcesser = new ModuleProcesser($this->fileHelper, $this->moduleRootPath, 
                $this->componentRootPath, $isManual);
        
        $tmplsMap = $this->processModules($isManual, $jsFilesOrderList, $cssFilesOrderList);

        //
        // step 4: 开始替换template中的variable
        //
        if(!is_dir("$this->templatePath/inc")){
            mkdir("$this->templatePath/inc");
        }
        foreach ($tokens as $key => $token) {
            $variable = $variables[$key];
            switch ($types[$key]) {
                case 'tmpl':
                    $this->replaceTmplToken($token, $variable, $tmplsMap);
                    break;                
                case 'js':
                case 'css':
                    $this->replaceStaticToken($token, $variable, $valuedVariables[$variable], 
                            $types[$key]);
                    break;
                case 'str':
                    $this->replaceStringToken($token, $variable);
                    break;
            }
        }
        echo "process page [{$this->pageDef->getName()}] successfully\n\n";
        file_put_contents("{$this->templatePath}/{$this->pageDef->getName()}.html", $this->fileContent);
    }
    private function replaceStringToken($token, $variable){
        $fileContent = &$this->fileContent;

        $value = $this->pageDef->getVariable($variable);
        $fileContent = str_replace($token, $value, $fileContent);        
    }
    private function replaceStaticToken($token, $variable, &$packageList, $type){
        $fileContent = &$this->fileContent;
        $value = '';
        if(count($packageList) > 0){
            foreach ($packageList as $packageName) {
                $value .= $this->writeStaticIncludingStr($packageName, $type);
            }
        }
        if($variable == 'unittest_script'){
            $value .= $this->generateUnittestFile();
        }
        $fileContent = str_replace($token, $value, $fileContent);
    }
    private function replaceTmplToken($token, $variable, &$tmplsMap){
        $fileContent = &$this->fileContent;
        $tmplList = $this->pageDef->getVariable($variable);

        $value = '';
        if($tmplList === FALSE || count($tmplList) == 0){
            return;
        }
        foreach($tmplList as $tmplName){
            if(isset($tmplsMap[$tmplName])){
                $value .= $this->writeTmplIncludingStr($tmplsMap[$tmplName]);
            }
        }
        $fileContent = str_replace($token, $value, $fileContent);
    }
    private function writeTmplIncludingStr($tmpl){
        if($this->mode == 'debug'){
            $retStr = "<&include file=\"{$this->product}/{$tmpl}.html\"&>";
        }
        else{
            $retStr = file_get_contents("{$this->templatePath}/{$tmpl}.html");
        }
        return $retStr;
    }
    private function writeStaticIncludingStr($packageName, $type){
        if($type == 'js'){
            $inlineContent = $this->pageDef->getJsFileType($packageName) == 'inline' ?
                 ' InlineContent': '';
            $includeFileContent = "<script type=\"text/javascript\" src=\"{$this->pageDef->getJsPath()}/{$this->pageName}_{$packageName}.js\"{$inlineContent}></script>";
        }
        else if ($type == 'css'){
            $includeFileContent = "<link type=\"text/css\" rel=\"stylesheet\" href=\"{$this->pageDef->getCssPath()}/{$this->pageName}_{$packageName}.css\" />";
        }

        file_put_contents("{$this->templatePath}/inc/{$this->pageName}_{$type}_{$packageName}.inc", 
                $includeFileContent);
        $retStr = "<&include file=\"{$this->product}/inc/{$this->pageName}_{$type}_{$packageName}.inc\"&>";
        return $retStr;
    }
    private function adjustPath($path){
        return $path[strlen($path) - 1] == '/' ? substr($staticPath, 0, strlen($staticPath) - 1) : $path;
    }
    private function & analyzePageTemplate($filename){
        $file = $this->fileContent = file_get_contents($filename);
        $contentPatternStr = '/<!--<#(\w+):(\w+)#>-->/';
        $matchs;
        preg_match_all($contentPatternStr, $file, &$matchs);
        /*
         * return sample:
         * Array(
                    [0] => Array
                        (
                            [0] => <!--<#pageTitle:str#>-->
                            [1] => <!--<#pageHeadStyle:style#>-->
                            [2] => <!--<#pageHeadScript:script#>-->
                            [3] => <!--<#pageFootScript:script#>-->
                        )
                
                    [1] => Array
                        (
                            [0] => pageTitle
                            [1] => pageHeadStyle
                            [2] => pageHeadScript
                            [3] => pageFootScript
                        )
                
                    [2] => Array
                        (
                            [0] => str
                            [1] => style
                            [2] => script
                            [3] => script
                        )
                )
         */
        return $matchs;
    }
    private function & processModules($isManual, &$jsFilesOrderList, &$cssFilesOrderList){
        if($isManual){
            $tmpls = $this->pageDef->getTemplates();
            $jsFiles = $this->pageDef->getJsFiles();
            $cssFiles = $this->pageDef->getCssFiles();
        }
        else{
            $modules = $this->pageDef->getModules();
            $isCssPackageSeparated = $this->pageDef->isCssSeparated();
            $isJsPackageSeparated = $this->pageDef->isJsSeparated();
            $jsPackageWithout = $this->pageDef->getIgnoreJsPackage();
            $cssPackageWithout = $this->pageDef->getIgnoreCssPackage();

            $tmpls = array();
            $jsFiles = array();
            $cssFiles = array();
            $cssPackage;
            $jsPackage;
            $firstPackageName = null;

            foreach ($modules as $packageName => $moduleList) {
                if(is_null($firstPackageName)){
                    $firstPackageName = $packageName;
                    if(!$isCssPackageSeparated){
                        $cssFiles[$firstPackageName] = array();
                        $cssPackage = &$cssFiles[$firstPackageName];
                    }
                    if(!$isJsPackageSeparated){
                        $jsFiles[$firstPackageName] = array(
                            'modules' => array()
                        );
                        $jsPackage = &$jsFiles[$firstPackageName]['modules'];
                    }
                }
                if($isCssPackageSeparated){
                    $cssFiles[$packageName] = array();
                    $cssPackage = &$cssFiles[$packageName];
                }

                if($isJsPackageSeparated){
                    $jsFiles[$packageName] = array(
                        'modules' => array()
                    );
                    $jsPackage = &$jsFiles[$packageName]['modules'];
                }

                foreach ($moduleList as $module) {
                    array_push($tmpls, $module);

                    if(!in_array($module, $cssPackageWithout)){
                        array_push($cssPackage, $module);                        
                    }

                    if(!in_array($module, $jsPackageWithout)){
                        array_push($jsPackage, $module);
                    }
                }
            }
        }

        $tmplsMap = $this->processTmpl($tmpls);
        $this->processJs($jsFiles, $jsFilesOrderList);
        $this->processCss($cssFiles, $cssFilesOrderList);

        return $tmplsMap;
    }
    private function & processTmpl($tmpls){
        $tmplsMap = array();
        foreach ($tmpls as $tmpl) {
            $name = $this->moduleProcesser->processModuleTempl($tmpl);
            if(!isset($tmplsMap[$tmpl])){
                $tmplsMap[$tmpl] = $name;                
            }
            else{
                echo "\n[$tmpl] module has been included many times in [$this->pageName] page\n";
                die(1);
            }
        }
        // print_r($tmplsMap);
        return $tmplsMap;
    }
    private function processJs($jsFiles, &$jsFilesOrderList){
        $packageLoaded = array();
        foreach ($jsFilesOrderList as $packageName) {
            if(isset($jsFiles[$packageName])){
                foreach ($jsFiles[$packageName]['modules'] as $moduleName) {
                    $this->moduleProcesser->processModuleJs($moduleName, $packageName);
                }
                array_push($packageLoaded, $packageName);
                if(isset($this->moduleProcesser->fileList[$packageName]['js']) && 
                    count($this->moduleProcesser->fileList[$packageName]['js']) > 0){
     
                    $this->generateJsPackageFile($packageName, $this->moduleProcesser->fileList[$packageName]['js'], 
                        $this->pageDef->getJsFileType($packageName) == 'inline');
                }
            }
        }
        foreach ($jsFiles as $key => $jsFile) {
            $packageName = $key;
            if(in_array($packageName, $packageLoaded)){
                continue;
            }
            foreach ($jsFile['modules'] as $moduleName) {
                $this->moduleProcesser->processModuleJs($moduleName, $packageName);
            }
            if(isset($this->moduleProcesser->fileList[$packageName]['js']) && 
                    count($this->moduleProcesser->fileList[$packageName]['js']) > 0){
                
                $this->generateJsPackageFile($packageName, $this->moduleProcesser->fileList[$packageName]['js'], 
                        $this->pageDef->getJsFileType($packageName) == 'inline');
            }  
        }
    }
    private function processCss($cssFiles, &$cssFilesOrderList){
        $packageLoaded = array();
        foreach ($cssFilesOrderList as $packageName) {
            if(isset($cssFiles[$packageName])){
                foreach ($cssFiles[$packageName] as $moduleName) {
                    $this->moduleProcesser->processModuleCss($moduleName, $packageName);
                }
                array_push($packageLoaded, $packageName);
                if(isset($this->moduleProcesser->fileList[$packageName]['css']) && 
                    count($this->moduleProcesser->fileList[$packageName]['css']) > 0){
                
                    $this->generateCssFile($packageName, $this->moduleProcesser->fileList[$packageName]['css']);
                }
            }
        }
        foreach ($cssFiles as $key => $cssModuls) {
            $packageName = $key;
            foreach ($cssModuls as $moduleName) {
                if(in_array($packageName, $packageLoaded)){
                    continue;
                }
                $this->moduleProcesser->processModuleCss($moduleName, $packageName);
            }
            if(isset($this->moduleProcesser->fileList[$packageName]['css']) && 
                    count($this->moduleProcesser->fileList[$packageName]['css']) > 0){
                
                $this->generateCssFile($packageName, $this->moduleProcesser->fileList[$packageName]['css']);
            }
        }
    }
    private function generateJsPackageFile($fileName, &$fileList, $isInline){
        $outputStr = "";
        if ($isInline && $this->mode == 'release') {
            foreach($fileList as $file){
                $outputStr .= $this->writeJsInlineContent($file);
            }
        }
        else {
            $dirPath = dirname(__FILE__);
            $outputStr = file_get_contents("$dirPath/externalJsTemplate.js");
            $outputStr .= "\n";
            foreach ($fileList as $file) {
                $outputStr .= "importScript(\"$file\");\n";
            }
        }
        file_put_contents("{$this->staticPath}/js/{$this->pageName}_{$fileName}.js", $outputStr);
    }
    private function writeJsInlineContent($file){
        $jsContent = file_get_contents("$this->staticPath/js/$file");
        $jsContent = trim($jsContent);
        $lastWord = $jsContent[strlen($jsContent) - 1];
        if($lastWord !== ';' && $lastWord !== '}'){
            $jsContent .= ';';
        }
        $jsContent .= "\n";
        return $jsContent;
    }
    private function generateCssFile($fileName, &$fileList){
        $outputStr = "";
        // print_r($filenameList);
        foreach ($fileList as $file) {
            $outputStr .= "@import url(\"$file\");\n";
        }
        file_put_contents("{$this->staticPath}/css/{$this->pageName}_{$fileName}.css", $outputStr);
    }

    private function generateUnittestFile(){
        $dirPath = dirname(__FILE__);        
        $fileName = "{$this->pageDef->getName()}_unittest.js";

        $fileList = array();
        $this->makeUnittestJsDirectory();
        $this->copyUnittestFile("{$this->moduleRootPath}/{$this->module}/unittest/",
                $fileList);

        

        $fileContent = file_get_contents("{$dirPath}/externalJsTemplate.js") . "\n";
        foreach ($fileList as $file) {
            $fileContent .= "importScript(\"$file\");\n";
        }

        file_put_contents("{$this->staticPath}/js/{$fileName}", $fileContent);
        $incFileContent = "<script type=\"text/javascript\" src=\"{$this->pageDef->getJsPath()}/{$fileName}\"></script>";
        file_put_contents("{$this->templatePath}/inc/js_{$this->pageDef->getName()}_unittest.inc", 
                $incFileContent);
        return "<&include file=\"{$this->product}/inc/js_{$this->pageDef->getName()}_unittest.inc\"&>";
    }
    private function copyUnittestFile($path, &$fileList, $dir=null){
        print_r($fileList);
        $dirObj =  opendir($path);
        $fileName = '';
        $hasRunJs = FALSE;
        while( ($fileName = readdir($dirObj)) != FALSE ){
            //
            // including '.js' and has no .js extension
            //
            if(strpos($fileName, '.js') != FALSE){
                if($fileName === 'run.js' && is_null($dir)){
                    $hasRunJs = true;
                    continue;
                }
                $dirPath = is_null($dir) ? '' : $dir;
                $this->fileHelper->doCopy("{$path}/{$fileName}", 
                        "{$this->staticPath}/js/{$this->module}_unittest/{$dirPath}/", 
                        $fileName);
                $fileList[] = "{$this->module}_unittest/{$dirPath}/$fileName";
            }
            //
            // recursion for copying directory
            //
            else if(strpos($fileName, '.') !== 0 && is_dir("{$path}/{$fileName}")){
                $dirPath = is_null($dir) ? $fileName : "{$dir}/{$fileName}";
                $this->makeUnittestJsDirectory($dirPath);
                $this->copyUnittestFile("{$path}/{$fileName}", $fileList, $dirPath);
            }
        }
        if($hasRunJs){
            $this->fileHelper->doCopy("{$path}/run.js", 
                    "{$this->staticPath}/js/{$this->module}_unittest/", 
                    'run.js');
            $fileList[] = "{$this->module}_unittest/run.js";
        }
    }
    private function makeUnittestJsDirectory($dir=null){
        $unittestPath = "{$this->staticPath}/js/{$this->module}_unittest/";
        if(!is_null($dir)){
            $unittestPath .= $dir;
        }
        if(!is_dir($unittestPath)){
            mkdir($unittestPath);
        }
    }
}