<?php
include_once ('./ModuleDef.php');
include_once('./FileHelper.php');
include_once('./ModuleProcesser.php');

// $pageArr = include_once('../page_index.php');

$templatePath = "../../template";
$staticPath = "../../static";
$componentPath = '../../components';
$modulePath = '../../modules';

system("rm -rf $templatePath");
system("rm -rf $staticPath");

$fileHelper = new FileHelper('debug', $modulePath, $staticPath, $templatePath, $componentPath);
$moduleProcesser = new ModuleProcesser($fileHelper, $modulePath, $componentPath);

$moduleProcesser->processModuleTempl('basicinfo');
$moduleProcesser->processModuleTempl('tieba_feed');
$moduleProcesser->processModuleTempl('tieba_honor');
$moduleProcesser->processModuleCss('basicinfo', 'test.css');
$moduleProcesser->processModuleCss('tieba_feed', 'test.css');
$moduleProcesser->processModuleCss('tieba_honor', 'test.css');
$moduleProcesser->processModuleJs('basicinfo', 'test');
$moduleProcesser->processModuleJs('tieba_feed', 'test');
$moduleProcesser->processModuleJs('tieba_honor', 'test');



print_r($moduleProcesser->fileList);


// $module = new ModuleDef($modulePath, 'basicinfo');

// print_r($module);


// $modulePath = "../../modules";



// $pageDef = new FileHelper('debug', $modulePath, $staticPath, $templatePath);
// $fileList = array();
// $pageDef->copyCssFileFromModule('tieba_feed', &$fileList);
// $pageDef->copyJsFileFromModule('tieba_feed', &$fileList);
// $pageDef->copyImgFileFromModule('tieba_feed', &$fileList);
// print_r($fileList);
// 
// $dir = opendir("$modulePath/basicpage/static/css");
// while(($filename = readdir($dir)) !== false){
    // print_r($filename);
    // echo "\n";
// }
// closedir($dir);
?>
