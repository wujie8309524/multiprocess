<?php
/*
 * 1.子进程循环监听信号SIGINT
 * 2.父进程循环发送SIGINT信号    （SIGNIT等信号可以自定义处理方式，默认终止进程）
 * 3.子进程收到SIGINT信号，退出
 * 4.父进程回收
 */

function sighandler($signo){
    //修改SIGINT信号默认动作，先输出语句，再停止进程（子进程）
    if($signo == SIGINT){
        $pid=posix_getpid();
        exit("$pid process will be killed\n");
    }

}


pcntl_signal(SIGINT,'sighandler');//注册信号处理函数
$pids=array();

for($i=0 ;$i<3;$i++){
    $pid=pcntl_fork();
    if($pid == 0){
        while(true){
            pcntl_signal_dispatch();//监听信号
            echo "child process ".posix_getpid()." is running!".PHP_EOL;
            sleep(rand(1,2));
        }

    }elseif($pid >0){
        $pids[$pid]=$pid;

    }else{
        die("fork fail!".PHP_EOL);
    }

}

//主进程
sleep(5);
var_dump($pids);
while(count($pids) >0 ) {
    foreach ($pids as $pid) {
        posix_kill($pid, SIGINT); //触发SIGINT信号
        if ($id = pcntl_wait($status)) {
            echo "father process recycle child pid " . $id . " work down!" . PHP_EOL;
            unset($pids[$id]);
        }
    }
}

