<?php
/*
 * shell获取进程id echo $$
 * 守护进程要点
 * 1.fork子进程，让父进程终止，子进程继续
 * 2.setsid() 脱离控制终端，登录会话和进程组
 * linux中的进程与控制终端、登录会话、进程组之间的关系：
 *      进程属于一个进程组，组id(GID) == 进程组组长的进程号id(PID)
 *      登录会话可以包含多个进程组
 *      一个控制终端被多个进程组共享（前台进程和后台进程）
 * 控制终端、登录会话、进程组可以从父进程中继承下来，摆脱方法setsid 使得子进程成为会话组长posix_setsid()
 * setsid()：
 *      是会话组长时，调用会失败
 *      不是会话组长，调用会成为新的会话组长和进程组长
 *
 *3.禁止进程重新打开控制终端
 *  继续fork出来子进程
 *
 *4.关闭打开的文件描述符
 *  fclose(STDIN),fclose(STDOUT),fclose(STDERR)
 *5.改变工作目录，防止工作目录所在的文件系统不能被卸载
 *  chdir("/")
 *6.重设文件掩码
 *  umask(0);
 *7.处理SIGCHLD信号
 *
 * //调用方法
    $daemon=new DaemonCommand(argv[0]);
    $daemon->daemon();


 */
class Daemon{
    public $log_dir;
    public $log_file;
    public $pid_file;
    public $user;

    public $gc_enabled=null;

    public static $log_info="INFO";
    public static $log_error="ERROR";

    public function __construct($filename,$log_dir="/tmp",$user='nobody'){

        $this->log_dir=$log_dir;
        $this->log_file = self::getConfigPath($filename,$log_dir)['log_file'];
        $this->pid_file = self::getConfigPath($filename,$log_dir)['pid_file'];
        $this->user=$user;

        $this->checkPcntl();
    }
    public static function getConfigPath($filename,$log_dir){
        $log_file = $log_dir ."/" .__CLASS__ . "." . substr($filename,0,-4) . ".log";
        $pid_file = $log_dir ."/" .__CLASS__ . "." . substr($filename,0,-4) . ".pid";

        return ["log_file"=>$log_file,"pid_file"=>$pid_file];

    }
    public function checkPcntl(){
        if ( ! function_exists('pcntl_signal_dispatch')) {
            // PHP < 5.3 uses ticks to handle signals instead of pcntl_signal_dispatch
            // call sighandler only every 10 ticks
            declare(ticks = 10);
        }
        // Make sure PHP has support for pcntl
        if ( ! function_exists('pcntl_signal')) {
            $message = 'PHP does not appear to be compiled with the PCNTL extension.  This is neccesary for daemonization';
            $this->_log($message);
        }

    }

    public function daemon(){
        set_time_limit(0);
        //只允许在cli下面执行
        if(php_sapi_name() != "cli"){
            $message="only run in command line mode";
            $this->_log($message);
        }

        $this->checkPidfile();

        if(pcntl_fork() !=0){
            //父进程退出
            exit();
        }
        posix_setsid();//设置子进程为新的会话组长，脱离原会话，原终端
        if(pcntl_fork() !=0){ //继续fork新会话组长进程，关闭父进程，防止重新获得控制终端
            exit();
        }
        chdir("/");//改变工作目录

        umask(0);//设置文件掩码

        //设置执行用户
        /*if(!$this->setUser($this->user)){
            $message="cannot channge owner";
            $this->_log($message);
        }*/

        //关闭已经打开的文件描述符
        /*fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);*/

        $this->createPidfile();

    }
    //单例模式，pidfile不存在，则返回真继续执行下面代码
    //pidfile存在，且正常，则提示守护进程已经启动，exit代码
    public function checkPidfile(){
        if(!file_exists($this->pid_file)){
            return true;
        }

        $pid = file_get_contents($this->pid_file);
        $pid = intval($pid);
        if ($pid > 0 && posix_kill($pid, 0)){
            $this->_log("the daemon process is already started");
        }else {
            $this->_log("the daemon proces end abnormally (非正常停止), please remove pidfile " . $this->pid_file);
        }

    }

    public function createPidfile(){

        if(!is_dir($this->log_dir)){
            mkdir($this->log_dir);
        }
        $fp = fopen($this->pid_file,"w");
        if(!$fp){
            $this->_log("cannot create pid file");
        }
        fwrite($fp,posix_getpid());
        fclose($fp);
        $this->_log("create pid file ".$this->pid_file,self::$log_info);
    }
    //设置运行的用户
    public function setUser($name){

        $result = false;
        if (empty($name)){
            return true;
        }
        $user = posix_getpwnam($name);
        if ($user) {
            $uid = $user['uid'];
            $gid = $user['gid'];
            $result = posix_setuid($uid);
            posix_setgid($gid);
        }
        return $result;

    }


    //整个进程退出
    public function stop(){

        if (file_exists($this->pid_file)) {
            $pid = file_get_contents($this->pid_file);
            $pid = intval($pid);
            //给主进程发送ctrl+c信号
            if ($pid > 0 && posix_kill($pid, SIGINT)) {
                $this->_log("Send the SIGINT to master process",self::$log_info);
                unlink($this->pid_file);
                $this->_log("Delete pid file:" . $this->pid_file,self::$log_info);
            }else{
                $this->_log("Failure to send the SIGINT to master process");
            }
        }else{
            $this->_log("master pid file not exists !");
        }

    }


    //日志处理
    private  function _log($message, $log_level= "ERROR"){
        $data=sprintf("%s\t pid : %d\t 【%s】 :  message : %s\n", date("c"), posix_getpid(), $log_level, $message);
        $fp=fopen($this->log_file,"a+");
        fwrite($fp,$data);
        if($log_level == Daemon::$log_error){
            exit(1);
        }
    }
}