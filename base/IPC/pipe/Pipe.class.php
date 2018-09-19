<?php
/*
 * 有名管道
 */
class Pipe{
    public $PipePath;
    public function __construct()
    {
        $this->PipePath="/tmp/test.pipe";
        if(!file_exists($this->PipePath)){
            if(!posix_mkfifo($this->PipePath,0666)){
                die("mk pipe false!".PHP_EOL);
            }
        }
    }
    public function run(){
        $pid = pcntl_fork();
        if( $pid == 0 ){
            //子进程写
            $this->write();

        }elseif( $pid > 0 ){
            //父进程读
            $this->read();

        }else{
            die("fork false!".PHP_EOL);
        }

    }

    private function write(){
        $file=fopen($this->PipePath,"w");
        if(!$file){
            die("fopen ".$this->PipePath." in W mode false!".PHP_EOL);
        }
        fwrite($file,"hello world!");
        sleep(5);
        exit();
    }

    private function read(){
        $file=fopen($this->PipePath,"r");
        if(!$file){
            die("fopen ".$this->PipePath." in R mode false!".PHP_EOL);
        }
        //打开此函数，设置为非阻塞读取，立刻输出空，不会等待子进程写入
        //stream_set_blocking( $file, False );
        echo fread($file,20).PHP_EOL;

        pcntl_wait($status);
    }

}
$pipe=new Pipe();
$pipe->run();