<?php
function __autoload($className){
    $filepath=dirname(__FILE__)."/lib/".$className.".php";
    if(file_exists($filepath)){
        require_once $filepath;
        return true;
    }
    return false;
}
$work=new Worker();
$work->start();
