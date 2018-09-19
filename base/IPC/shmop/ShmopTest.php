<?php
/*
 * 共享内存 (必须加信号量进行并发读控制)
 * $key不能重复，否则读取垃圾数据
 * 单个子进程读取父进程写入的共享内存内容
 */
$key="123";
//$shm_key = ftok(__FILE__, 't');
$size=2048;
//创建共享内存
$shmid= shmop_open($key,"c",0666,$size);


if(!$shmid){
    die("shmop_open failed!");
}
//创建子进程
$arr_pid=array();

for($i=1;$i<=1;$i++){
    $pid=pcntl_fork();
    if($pid){
        $arr_pid[$pid]=$pid;
    }else{
        $pid=posix_getpid();
        while(1){
            //读取数据
            //默认读取$size个空值
            $shmop_data=shmop_read($shmid,0,$size);
            var_dump($shmop_data);
            if($shmop_data){
                var_dump("child pid $pid read shmop_data 【".$shmop_data."】".PHP_EOL);
                if(trim($shmop_data) ==  "exit"){
                    echo "child pid $pid exit!".PHP_EOL;
                    exit(0);
                }
            }
            sleep(1);
        }
    }
}
sleep(2);//休息两秒之后开始写
//回收子进程
var_dump($arr_pid);
while(count($arr_pid)>0){
    //写入exit到共享内存
    $bool= shmop_write($shmid,"exit",0);
    var_dump($bool);
    if($bool){
        $pid=pcntl_wait($status);
        echo "father receive child $pid exit!".PHP_EOL;
        unset($arr_pid[$pid]);

    }
    sleep(1);

}

shmop_delete($shmid);
shmop_close($shmid);


