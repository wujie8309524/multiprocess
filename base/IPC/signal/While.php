<?php
/*
 * 测试信号捕捉
 * php while.php
 * ctrl+c
 * kill -SIGINT pid
 */
//使用ticks需要PHP 4.3.0以上版本
declare(ticks = 1);

//信号处理函数
function sig_handler($signo)
{

    switch ($signo) {
        case SIGINT:
            echo "Caught SIGINT...\n";
            break;
        case SIGTERM:
            // 处理SIGTERM信号
            exit;
            break;
        case SIGHUP:
            //处理SIGHUP信号
            break;
        case SIGUSR1:
            echo "Caught SIGUSR1...\n";
            break;
        default:
            // 处理所有其他信号
    }

}

echo "Installing signal handler...\n";

//安装信号处理器
pcntl_signal(SIGINT, "sig_handler");
pcntl_signal(SIGTERM, "sig_handler");
pcntl_signal(SIGHUP,  "sig_handler");
pcntl_signal(SIGUSR1, "sig_handler");

while(1){

    pcntl_signal_dispatch();//监听信号

    sleep(2);
}
