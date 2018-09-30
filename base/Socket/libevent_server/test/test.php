<?php
function __autoload($className){
    $filepath=dirname(__FILE__)."/".$className.".php";
    if(file_exists($filepath)){
        require_once $filepath;
        return true;
    }
    return false;
}
$connect = 'tcp://0.0.0.0:8800';
$socket = new Socket($connect);
$socket->start();