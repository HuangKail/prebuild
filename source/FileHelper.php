<?php
/**
 *  该类主要用于copy文件，包括copy单个文件，以及整个模块文件一起copy
 */
Class FileHelper{
    private $mode, $modulesPath, $staticPath, $templatePath, $componentRootPath;
    public function FileHelper($mode, $modulesPath, $staticPath, $templatePath, $componentRootPath = ''){
        $this->mode = $mode;
        $this->modulesPath = $modulesPath;
        $this->staticPath = $staticPath;
        $this->templatePath = $templatePath;
        $this->componentRootPath = $componentRootPath;
        //
        //
        //
        // $this->componentPath = $componentPath;
        if($mode != 'test'){
            if(!file_exists($templatePath)){
            $this->makeDirectory($templatePath);
            }
            if(!file_exists($staticPath)){
                $this->makeDirectory($staticPath);
                $this->makeDirectory("$staticPath/js");
                $this->makeDirectory("$staticPath/css");
                $this->makeDirectory("$staticPath/img");
            }
        }
    }
    public function doCopy($from, $to, $filename){
        if($this->mode == 'debug'){
            $this->copyVirtually($from, $to, $filename);
        }
        else if($this->mode == 'release'){
            $this->copyPhysically($from, $to, $filename);
        }
    }
    public function copyCssFileFromModule($fromModule, &$fileList){
        $to = "{$this->staticPath}/css";
        $from = "{$this->modulesPath}/$fromModule/static/css";
        $this->copyDirecotry($from, $to, $fromModule, 'css', &$fileList);
    }
    public function copyJsFileFromModule($fromModule, &$fileList){
        $to = "$this->staticPath/js/$fromModule";
        $from = "$this->modulesPath/$fromModule/static/js";
        $this->makeDirectory($to);
        $this->copyDirecotry($from, $to, $fromModule, 'js', &$fileList);
    }
    public function copyImgFileFromModule($fromModule){
        $to = "$this->staticPath/img";
        $from = "$this->modulesPath/$fromModule/static/img";
        $this->copyDirecotry($from, $to, $fromModule, 'img');
    }
    public function copyTmplFileFromModule($fromModule){
        $to = "$this->templatePath";
        $from = "$this->modulesPath/$fromModule/template";
        $this->copyDirecotry($from, $to, $fromModule, 'tmpl');
    }
    public function copyCssFileFromComponent($fromComponent, &$fileList){
        $to = "{$this->staticPath}/css";
        $from = "{$this->componentRootPath}/$fromComponent/static/css";
        $this->copyDirecotry($from, $to, $fromComponent, 'css', &$fileList);
    }
    public function copyJsFileFromComponent($fromComponent, &$fileList){
        $to = "$this->staticPath/js/$fromComponent";
        $from = "$this->componentRootPath/$fromComponent/static/js";
        $this->makeDirectory($to);
        $this->copyDirecotry($from, $to, $fromComponent, 'js', &$fileList);
    }
    public function copyImgFileFromComponent($fromComponent){
        $to = "$this->staticPath/img";
        $from = "$this->componentRootPath/$fromComponent/static/img";
        $this->copyDirecotry($from, $to, $fromComponent, 'img');
    }
    public function copyTmplFileFromComponent($fromComponent){
        $to = "$this->templatePath";
        $from = "$this->componentRootPath/$fromComponent/template";
        $this->copyDirecotry($from, $to, $fromComponent, 'tmpl');
    }
    //
    // TODO: 根据模块间文件的依赖关系决定加载顺序，F.module(id, func, dependencies)/F.use(dependencies, func)
    //
    private function copyDirecotry($from, $to, $modulename, $type, &$fileList = ''){
        if(!is_dir($from)){
            return ;
        }
        $dirObj = opendir($from);
        while(($filename = readdir($dirObj)) !== FALSE){
            if (substr($filename, 0, 1) == '.') {
                continue;
            }
            if(is_dir("$from/$filename")){
                $this->makeDirectory("$to/$filename");
                $this->copyDirecotry("$from/$filename", "$to/$filename", $modulename, $type, &$fileList);
            }
            else{
                $copiedFileName = $this->copyFile("$from/$filename", $to, $filename, $modulename, $type);
                $pathLength = $type != 'tmpl' ? strlen("{$this->staticPath}/$type/") : 
                        strlen("{$this->templatePath}/");
                $copiedFile = substr($copiedFileName, $pathLength);
                $fileList[] = $copiedFile;
            }
        }
        closedir($dirObj);
    }
    private function copyFile($from, $to, $filename, $modulename, $type){
        $targetName = '';
        switch ($type) {
            case 'css':
                $targetName = "{$modulename}_{$filename}";
                break;
            default:
                $targetName = $filename;
                break;
        }
        $this->doCopy($from, $to, $targetName);
        return "$to/$targetName";
    }

    public function copyTmplFileWithAssignedName($moduleName, $name){
        $dirPath = "{$this->modulesPath}/{$moduleName}/template";
        $dirObj = opendir($dirPath);
        $fileName;
        if($dirObj){
            while( ($fileName = readdir($dirObj)) !== FALSE ){
                if(strpos($fileName, '.html') !== FALSE){
                    break;
                }
            }
        }
        closedir($dirObj);

        if($fileName == FALSE){
            return;
        }
        $from = "$dirPath/$fileName";
        $to = "$this->templatePath";

        $this->doCopy($from, $to, $name);
    }

    private function makeDirectory($path){
        if(is_dir($path) || $this->mode == 'test'){
            return ;
        }
        mkdir($path, 0777);
        chmod($path, 0777);
        // system("mkdir $path");
    }
    private function copyVirtually($from, $to, $filename){
        if(is_file("{$to}/{$filename}")){
            return ;
        }
        $isWin = strtolower(substr(PHP_OS, 0, 3)) == 'win';

        if($isWin){
            $fromPath = str_replace('/', '\\', $from);
            $toPath = str_replace('/', '\\', $to);
            system("mklink /H {$toPath}\\{$filename} $fromPath");
        }
        else{
            link($from, "{$to}/{$filename}");
        }
        // system("ln $from {$to}/{$filename}");
    }
    private function copyPhysically($from, $to, $filename){
        if(is_file("{$to}/{$filename}")){
            return ;
        }
        copy($from, "{$to}/{$filename}");
        // system("cp $from {$to}/{$filename}");
    }
}
?>