<?php
include_once("./source/TestProcesser.php");
include_once('./source/FileHelper.php');

$templatePath = "../template";
$staticPath = "../static";
$componentRootPath = "../components";
$moduleRootPath = '../modules';
$pageRootPath = '../pages';

$product = 'unittest';

if(isset($argv[1]) && isset($argv[2])){
    $type = $argv[1];
    $module = $argv[2];
}
if(!isset($module)){
    echo "usage: testbuild.php [m/c] [moduleName/componentName]\n";
    die(1);
}
$mode = 'debug';

//
// start merging templates
//
$fileHelper = new FileHelper($mode, $moduleRootPath, $staticPath, $templatePath, $componentRootPath);

$definition = array(
        'name' => "unittest_page_{$module}",
        'pageTitle' => 'unittest page',
        
        'modules' => array(
            'framework' => array('qunit'),
            'module_code' => array($module)
        ),
        'pack' => array(
            'js'=> true,
            'css'=> false
        ),

        'pageHeadScript' => array('framework', 'module_code'),
        'pageHeadStyle' => array('framework'),
        
        'cssPath' => '<&$pDomain.static&>/static/princess/css/',
        'jsPath' => '<&$pDomain.static&>/static/princess/js/',
);

$pageProcesser = new TestProcesser($pageRootPath, $moduleRootPath, $componentRootPath, 'unittest', 
        $fileHelper, $staticPath, $templatePath, &$definition, $module);
$pageProcesser->process();

?>
