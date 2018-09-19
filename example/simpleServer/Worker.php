<?php
class Worker{
    //所有运行进程的id
    public static $workers = [];
    //事件轮询库对象
    public static $loop = null;
    //服务器socket
    public $mainSocket = null;
    //连接的socket
    public $conn_socket = null;

    public function __construct()
    {
        $this->forkWorker();
        $this->monitor();
    }
    public function forkWorker(){
        $mainPid = posix_getpid();

        //创建tcp服务器，监听2000端口
        $this->mainSocket = stream_socket_server("tcp://0.0.0.0:2000",$errno,$errstr);
        //设置为非阻塞模式
        stream_set_blocking($this->mainSocket,0);

        for($i=0;$i<2;$i++){
            $this->forkOne();
        }
    }
    public function forkOne(){
        $pid=pcntl_fork();
        if($pid > 0){
            self::$workers[$pid]=$pid;
        }elseif($pid == 0){
            //实例化轮询库
            self::$loop = new Libevent();
            //添加轮询回调函数
            self::$loop -> add ($this->mainSocket,[$this,'acceptCb']);
            //启动事件轮询
            self::$loop->loop();
            exit(0);
        }else{
            exit("fork error!".PHP_EOL);
        }
    }

    public function monitor(){

    }

}