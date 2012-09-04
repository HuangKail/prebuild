<?php
include_once("./source/PageProcesser.php");
include_once('./source/FileHelper.php');

$templatePath = "../template";
$staticPath = "../static";
$componentRootPath = "../components";
$moduleRootPath = '../modules';
$pageRootPath = '../pages';

$isWin = strtolower(substr(PHP_OS, 0, 3)) == 'win';
$removeCommand =  $isWin ? 'RD /S /Q' : 'rm -rf';

$product = $siteDefinition['product'];

if(isset($argv[1]) && isset($argv[2])){
    $type = $argv[1];
    $module = $argv[2];
}
if(!isset($module)){
    echo "usage: testbuild.php [m/c] [moduleName/componentName]\n";
    die(1);
}
$mode = 'debug';

if (file_exists($templatePath)) {
    $pathToBeDeleted = $templatePath;
    if($isWin){
        $pathToBeDeleted = str_replace('/', '\\', $pathToBeDeleted);
    }
    system("$removeCommand $pathToBeDeleted");
}

if (file_exists($staticPath)) {
    $pathToBeDeleted = $staticPath;
    if($isWin){
        $pathToBeDeleted = str_replace('/', '\\', $pathToBeDeleted);
    }
    system("$removeCommand $pathToBeDeleted");
}
//
// start merging templates
//
$fileHelper = new FileHelper($mode, $moduleRootPath, $staticPath, $templatePath, $componentRootPath);

foreach ($siteDefinition['pages'] as $page) {
    $pageProcesser = new PageProcesser($pageRootPath, $moduleRootPath, $componentRootPath, $page, 
            $fileHelper, $staticPath, $templatePath, $product, $mode);
    $pageProcesser->process();
}
?>
