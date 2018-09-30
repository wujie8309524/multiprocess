<?php
/* worker类主要工作：
   1.创建work子进程
   2.监视子进程
 *
 */
class Worker{
    //所有运行进程的id
    public static $workers = [];

    //服务器socket
    public $mainSocket = null;
    public $_sockets = [];

    //事件轮询库对象
    public $event_base = null;
    //加入监控的事件
    public $events = [];


    //配置属性
    public $daemon = null;
    public $work_num = 2;
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
        $mainSocket = stream_socket_server("tcp://0.0.0.0:8010",$errno,$errstr);
        echo "Work is running at TCP://0.0.0.0:8010...".PHP_EOL;
        //设置为非阻塞模式
        stream_set_blocking($mainSocket,0);

        $this-> mainSocket = $mainSocket;

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

            $this->event_base=event_base_new();
            $event = event_new();
            event_set($event,$this->mainSocket,EV_READ | EV_PERSIST, [$this,'acceptCB'], $this->event_base);
            event_base_set($event,$this->event_base);
            event_add($event);
            $id = (int) $this->mainSocket;
            $this->events[$id] = $event;
            event_base_loop($this->event_base);

            exit(0);
        }else{
            exit("fork error!".PHP_EOL);
        }
    }
    //子进程新连接回调
    public function acceptCB($socket,$flag,$base){
        /*
         * stream_socket_accept 是从系统连接队列里面获取一个socket连接
         * 这个操作不一定成功，因为多进程运行时，可能其他进程已经把最后一个socket从队列中取出
         * 当前进程再去调用就会产生一个warning，使用@抑制，并不影响使用
         * 多进程惊群
         */

        $conn_socket= @stream_socket_accept($socket,0);
        if(!is_resource($conn_socket)) {
            return;
        }
        //设置非阻塞
        @stream_set_blocking($conn_socket,0);

        $id = (int) $conn_socket;
        $this->_sockets[$id]=$conn_socket;

        //将新连接描述符 托管至 libevent
        $event = event_new();
        event_set($event,$conn_socket,EV_READ | EV_PERSIST, [$this,'readCB'], $this->event_base);
        event_base_set($event,$this->event_base);
        event_add($event);
        $this->events[$id] = $event;

    }
    public function readCB($socket,$flag,$base){
        if(!is_resource($socket)){
            return;
        }
        while(1){
            $buffer=fread($socket,1024);
            var_dump($buffer);

            if(substr($buffer,0,3) == '' || $buffer === false){
                //接收完成，关闭此连接
                $this->del($socket);
                return;
                if((feof($socket) || !is_resource($socket)) || $buffer === false){
                    //接收完成，关闭此连接
                    $this->del($socket);
                    return;
                }
            }else{
                $msg ="Receive data from client: ".$buffer;
                echo $msg.PHP_EOL;
                $response=$this->data();
                fwrite($socket,$response);

            }

        }


    }
    public function data(){
        $response = "HTTP/1.1 200 OK\r\n";
        $response .= "Server: phphttpserver\r\n";
        $response .= "Content-Type: text/html\r\n";

        $response .= "Content-Length: 3\r\n\r\n";
        $response .= "ok\n";

        return $response;
    }
    public function del($socket)
    {
        if(!is_resource($socket))
            return;
        $id=(int)$socket;
        event_del($this->events[$id]);
        unset($this->events[$id]);
        fclose($socket);
        unset($this->_sockets[$id]);

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