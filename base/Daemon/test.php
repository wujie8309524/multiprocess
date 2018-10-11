<?php
include_once "./Daemon.class.php";
$daemon=new Daemon($argv[0]);
$daemon->daemon();

$i=0;
while(1){
    echo $i."\n";
    $i++;
    //监控信号
    pcntl_signal_dispatch();
    sleep(1);
}
