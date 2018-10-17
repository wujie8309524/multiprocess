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
$work->onStart = function(Worker $worker,$pid){

    echo "child :".$pid." is start!".PHP_EOL;

};
$work->onConn = function(Worker $worker,$socket,$pid){
    echo (int) $socket. " is connect to child ".$pid.PHP_EOL;
    var_dump($worker);
};
$work->onReceive = function(Worker $worker,$message,$pid){
    echo "child :".$pid." is receive data :".$message;
    var_dump($worker);
};
$work->start();
