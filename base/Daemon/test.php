<?php
include_once "./Daemon.class.php";
$daemon=new Daemon($argv[0]);
$daemon->daemon();

$i=0;
while(1){
    echo $i."\n";
    $i++;
    sleep(1);
}



