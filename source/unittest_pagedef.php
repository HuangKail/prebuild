<?php
include_once ('./PageProcesser.php');
include_once('./FileHelper.php');

// $pageArr = include_once('../page_index.php');

$templatePath = "../../template";
$staticPath = "../../static";
$componentRootPath = '../../components';
$moduleRootPath = '../../modules';
$pageRootPath = '../../pages';

system("rm -rf $templatePath");
system("rm -rf $staticPath");

$fileHelper = new FileHelper('debug', $moduleRootPath, $staticPath, $templatePath, $componentRootPath);

$pageDef = new PageProcesser($pageRootPath, $moduleRootPath, $componentRootPath, 'index', 
        $fileHelper, $staticPath, $templatePath, 'princess', 'debug');
$pageDef->process();

?>
