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
 * //调用方法1
$daemon=new DaemonCommand(true);
$daemon->daemon();
$daemon->addJobs($jobs);//function 要运行的函数,argv运行函数的参数，runtime运行的次数
$daemon->start(2);//开启2个子进程工作

 */
class DaemonCommand{
    public $pid_dir = "/tmp";
    public $pid_file = "";
    public $is_sington = true;//单例模式创建pidfile，根据文件是否存在判断守护进程是否启动
    public $user;
    public $output;
    public $gc_enabled=null;
    public $terminate = false;
    public $workers_count = 0;

    public $workers_max=8;
    public $jobs=array();
    public $pids=array();

    public $queue;

    public function __construct($is_sington=true,$user='nobody',$output="/tmp/test.txt"){
        $this->is_sington=$is_sington;
        $this->user=$user;
        $this->output=$output;
        $this->checkPcntl();

        $key = ftok(__FILE__, 'R');
        if(msg_queue_exists($key)){
            $this->queue = msg_get_queue($key, 0666);
            msg_remove_queue($this->queue);
        }
        $this->queue = msg_get_queue($key, 0666);

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
            throw new Exception($message);
        }
        //信号处理
        pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler"),false);
        pcntl_signal(SIGINT, array(__CLASS__, "signalHandler"),false);
        pcntl_signal(SIGQUIT, array(__CLASS__, "signalHandler"),false);
        // Enable PHP 5.3 garbage collection
        if (function_exists('gc_enable'))
        {
            gc_enable();
            $this->gc_enabled = gc_enabled();
        }
    }

    public function daemon(){
        global $argv;

        set_time_limit(0);
        //只允许在cli下面执行
        if(php_sapi_name() != "cli"){
            die("only run in command line mode\n");
        }
        if($this->is_sington){
            $this->pid_file = $this->pid_dir . "/" .__CLASS__ . "_" . substr(basename($argv[0]), 0, -4) . ".pid";
            $this->checkPidfile();
        }

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
        //$this->setUser($this->user) or die("cannot channge owner");

        //关闭已经打开的文件描述符
        /*fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        */

        //创建pidfile，只运行一个进程
        if($this->is_sington){
            $this->createPidfile();
        }


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
        }
        else {
            $this->_log("the daemon proces end abnormally (非正常停止), please check pidfile " . $this->pid_file);
        }
        exit(1);

    }

    public function createPidfile(){

        if(!is_dir($this->pid_dir)){
            mkdir($this->pid_dir);
        }
        $fp = fopen($this->pid_file,"w") or die ("cannot create pid file");
        fwrite($fp,posix_getpid());
        fclose($fp);
        $this->_log("create pid file ".$this->pid_file);
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
    //信号处理函数
    public function signalHandler($signo){

        switch($signo){

            //用户自定义信号
            case SIGUSR1: //busy
                if ($this->workers_count < $this->workers_max){
                    $pid = pcntl_fork();
                    if ($pid > 0){
                        $this->workers_count ++;
                    }
                }
                break;
            //子进程结束信号
            case SIGCHLD:
                while(($pid=pcntl_waitpid(-1, $status, WNOHANG)) > 0){
                    $msg="【".$pid."】child process done!";
                    $this->_log($msg);
                    unset($this->pids[$pid]);
                    //$this->workers_count --; //动态创建子进程 继续工作
                }
                break;
            //中断进程
            case SIGTERM:
            case SIGHUP:
            case SIGQUIT:

                $this->terminate = true;
                break;
            default:
                return false;
        }

    }
    /**
     *开始开启进程
     *$count 准备开启的进程数
     */
    public function start($count=1){

        $this->_log("daemon process is running now");
        $this->pids[posix_getpid()]=1;
        //安装子进程终止信号处理函数
        pcntl_signal(SIGCHLD, array(__CLASS__, "signalHandler"),false); // if worker die, minus children num
        while (true) {
            if (function_exists('pcntl_signal_dispatch')){
                //监听信号（已安装的信号都是针对父进程）
                pcntl_signal_dispatch();
            }

            $cur_pid=posix_getpid();
            if(!isset($this->pids[$cur_pid])){
                $this->pids[$cur_pid]=1;
            }

            if ($this->terminate){
                break;
            }
            $pid=-1;
            if($this->workers_count<$count){

                $pid=pcntl_fork();
            }

            if($pid>0){
                //父进程执行
                $this->workers_count++;
                if(!isset($this->pids[$pid])){
                    $this->pids[$pid]=2;
                }

            }elseif($pid==0){
                //子进程

                // 这个符号表示恢复系统对信号的默认处理
                pcntl_signal(SIGTERM, SIG_DFL);
                pcntl_signal(SIGCHLD, SIG_DFL);
                if(!empty($this->jobs)){
                    while($this->jobs['runtime'] > 0 ){
                        if(!empty($this->jobs['argv'])){
                            call_user_func($this->jobs['function'],$this->jobs['argv']);
                        }else{
                            call_user_func($this->jobs['function'],posix_getpid());
                        }
                        $this->jobs['runtime']--;
                        sleep(2);
                    }
                    exit();

                }
                return;

            }else{

                sleep(2);
                if(count($this->pids) == 1){
                    //子进程工作完毕
                    break;
                }
            }


        }

        $this->mainQuit();
        exit(0);

    }

    //整个进程退出
    public function mainQuit(){

        if (file_exists($this->pid_file)){
            unlink($this->pid_file);
            $this->_log("delete pid file " . $this->pid_file);
        }
        $this->_log("daemon process exit now");
        posix_kill(0, SIGKILL);//杀死进程组里所有进程
        exit(0);
    }

    // 添加工作实例，目前只支持单个job工作
    public function addJobs($jobs=array()){

        if(!isset($jobs['argv'])||empty($jobs['argv'])){

            $jobs['argv']="";

        }
        if(!isset($jobs['runtime'])||empty($jobs['runtime'])){

            $jobs['runtime']=1;

        }

        if(!isset($jobs['function'])||empty($jobs['function'])){

            $this->_log("你必须添加运行的函数！");
        }

        $this->jobs=$jobs;

    }
    //日志处理
    private  function _log($message){
        $data=sprintf("%s\t pid : %d\t ppid : %d\t message : %s\n", date("c"), posix_getpid(), posix_getppid(), $message);
        $fp=fopen($this->output,"a+");
        fwrite($fp,$data);
    }




}
$daemon=new DaemonCommand(true);
$daemon->daemon();
$daemon->addJobs($jobs);//function 要运行的函数,argv运行函数的参数，runtime运行的次数
$daemon->start(2);//开启2个子进程工作




