<?php
include_once "./Daemon.php";
$daemon=new Daemon($argv[0]);
$daemon->daemon();