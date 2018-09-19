<?php
/*
 * 管道的特点：只能接受无差别的消息格式
 * 命名管道
 * 父进程分别给不同管道写，子进程各自读取pid管道
 * 1.fork父进程记录子进程pid
 * 2.子进程创建pid相关管道，并死循环等待读取管道数据
 * 3.父进程写入各pid相关管道数据
 * 4.子进程读取数据，退出进程，删除管道
 * 5.父进程回收子进程
 *
 * 基本用法：
 * 1.创建
 *      posix_mkfifo($fifo_path,0666);
 * 2.打开
 *      $fd=fopen($fifo_path,"r");
 * 3.读取或者写入
 *      fread($fd,1024)
 *      fwrite($fd,$data);
 * 4.关闭
 *      fclose($fd);
 *      unlink($fifo_path);
 *
 */
class Fifo{
    public $pids=array();
    public $fifo_path="/tmp/fifo";
    public $work_count=5;

    public function __construct()
    {

    }
    public function run(){
        for($i=1;$i<=$this->work_count;$i++){
            $pid=pcntl_fork();
            if($pid){
                $this->pids[$pid]=$pid;
                $this->write();
            }else{
                $this->read();
            }
        }
    }
    public function read(){
        $pid=posix_getpid();
        //命名管道
        $fifo_path=$this->fifo_path."_".$pid;
        if(!file_exists($fifo_path)){
            posix_mkfifo($fifo_path,0666);
            echo "child process ".$pid." mkfifo success".PHP_EOL;
        }
        $fd=fopen($fifo_path,"r");
        while(1){
            $data=fread($fd,1024);
            if($data == "exit"){
                echo "child process ".$pid." read $data".PHP_EOL;
                fclose($fd);
                unlink($fifo_path);
                exit(0);
            }

            sleep(1);
        }

    }
    public function write(){
        sleep(1);
        foreach($this->pids as $pid){
            $fifo_path=$this->fifo_path."_".$pid;
            $fifo=fopen($fifo_path,"w");
            fwrite($fifo,"exit");
            fclose($fifo);
            $pid=pcntl_wait($status);
            echo "farther process receive child process ".$pid." ".PHP_EOL;
            unset($this->pids[$pid]);
            sleep(1);
        }
    }

}
$fifo=new Fifo();
$fifo->run();