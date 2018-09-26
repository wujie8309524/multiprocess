<?php
/* worker类主要工作：
   1.创建work子进程
   2.监视子进程
 *
 */
class Worker{
    //所有运行进程的id
    public static $workers = [];
    //事件轮询库对象
    public static $loop = null;
    //服务器socket
    public $mainSocket = null;
    //连接的socket 主要由子进程接收
    public $conn_socket = null;
    //连接id
    public static $conn_id=0;
    //配置文件路径
    public $config_file = null;

    //配置属性
    public $daemon = null;
    public $work_num = 1;
    public $onConn = null;
    public $onReceive= null;



    public function __construct($config_file = null)
    {

        if(!is_null($config_file)){
            $this->config_file = $config_file;
            $this->parseConfigFile();
        }else{
            $this->parseCommand();
        }

    }
    public function parseConfigFile(){



    }
    public function parseCommand(){
        /*
         * 单:必填 ，双::选填，无：bool
         * E.G:
         * c:  命令中必须带 -c value 才可解析
         * d   命令中出现 -d 即可解析
         * f:: 命令中出现 -f 或者 -f=value 都可解析
         *
         */
        $short = "c:dhf::";// -c 2 -d -f "config_path"
        $longopts = array('help','reload','stop');
        $options = getopt($short,$longopts);

        if(isset($options['c']) && $options['c'] > 0){
            $this->work_num = $options['c'];
        }
        if(isset($options['d'])){
            $this->daemon = true;
        }
        if(isset($options['stop'])){
            $this->stop();
            exit();
        }
        if(isset($options['reload'])){
            $this->reload();
        }
        if(isset($options['help']) || isset($options['h'])){
            global $argv;
            echo $argv[0]." support below options:".PHP_EOL;
            echo "[-c <number>]        worker number".PHP_EOL;
            echo "[-d]                 dameon".PHP_EOL;
            echo "[--stop]             stop all worker".PHP_EOL;
            echo "[--reload]           reload the config".PHP_EOL;
            echo "[-h | --help]        help".PHP_EOL;
            exit();
        }
    }
    public function start()
    {
        if($this->daemon){
            global $argv;
            $daemon=new Daemon($argv[0]);
            $daemon -> daemon();
        }
        //安装信号处理函数，处理ctrl+c 信号
        pcntl_signal(SIGINT, array(__CLASS__, "signalHandler"),false);

        $this->forkWorker();
        $this->monitor();
    }
    public function signalHandler($signo){
        //修改SIGINT信号默认动作，先输出语句，再停止进程（子进程）
        var_dump($signo);
        if($signo == SIGINT){
            $pid=posix_getpid();
            echo "$pid process will be killed\n";
        }

    }
    public function forkWorker(){
        $mainPid = posix_getpid();

        //创建tcp服务器，监听8000端口
        $this->mainSocket = stream_socket_server("tcp://0.0.0.0:8000",$errno,$errstr);
        echo "Work is running at TCP://0.0.0.0:8000...".PHP_EOL;
        //设置为非阻塞模式
        stream_set_blocking($this->mainSocket,0);

        for($i=0;$i<$this->work_num;$i++){
            $this->forkOne();
        }
    }
    public function forkOne(){
        $pid=pcntl_fork();
        if($pid > 0){
            self::$workers[$pid]=$pid;
        }elseif($pid == 0){
            //子进程：
            //1、监听连接，
            //2、处理数据，执行主进程设置的回调函数，实现相关业务逻辑


            //实例化轮询库
            self::$loop = new Libevent();
            //添加socket监听，注册回调函数
            self::$loop -> add ($this->mainSocket,[$this,'acceptCB']);
            //启动事件轮询
            self::$loop->loop();
            exit(0);
        }else{
            exit("fork error!".PHP_EOL);
        }
    }
    //子进程新连接回调
    public function acceptCB($socket,$flag,$arg){
        /*
         * stream_socket_accept 是从系统连接队列里面获取一个socket连接
         * 这个操作不一定成功，因为多进程运行时，可能其他进程已经把最后一个socket从队列中取出
         * 当前进程再去调用就会产生一个warning，使用@抑制，并不影响使用
         * 多进程惊群
         */

        $this->conn_socket= @stream_socket_accept($socket,0);
        if(!is_resource($this->conn_socket)) {
            return;
        }
        self::$conn_id++;
        $msg="Connect is build!";
        $this->_log(posix_getpid(),self::$conn_id,$msg);

        //设置非阻塞
        @stream_set_blocking($this->conn_socket,0);

        //监听新连接是否有数据到达
        new Connect($this->conn_socket,self::$loop,self::$conn_id);
    }
    public function _log($pid,$conn_id,$msg){
        printf("Pid : %s , Conn_id : %s, Msg : %s \n",$pid,$conn_id,$msg);
    }

    public function monitor(){
        while(count(self::$workers) >0){
            pcntl_signal_dispatch();//主进程监听信号

            foreach (self::$workers as $key => $pid) {
                $status = pcntl_waitpid($pid, $status);
                echo "Child process pid: ".$pid." status: ".$status." done!".PHP_EOL;
                if ($status == -1 || $status > 0) {
                    unset(self::$workers[$key]);
                }
            }
            sleep(1);
        }
    }

    public function stop(){

        global $argv;
        $daemon=new Daemon($argv[0]);
        $daemon -> stop();

    }

}