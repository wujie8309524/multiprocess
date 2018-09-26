<?php
/*
 * 父子进程指向 不同文件句柄
 * 两次加锁产生锁等待
 * 正确用法
 */

$pid = pcntl_fork();
$fp = fopen("flock.log", "a");

if ($pid == 0)
{
    if(flock($fp, LOCK_EX))
    {
        echo "子进程加锁成功\n";
        $i=0;
        while(1)
        {
            $i++;
            echo "i: ".$i.PHP_EOL;
            if($i>10){
                break;
            }
            sleep(1);
        }
    }
} elseif($pid > 0) {
    sleep(2);
    if(flock($fp, LOCK_EX))
    {
        echo "父进程加锁成功\n";
    }
    pcntl_wait($status);
}