<?php
/*
 * 消息队列
 * 基本用法：
 *1.生成一个消息队列的key
 *      $msg_key = ftok(__FILE__);
 *2.产生一个消息队列
 *      $msg_queue = msg_get_queue($msg_key, 0666);
 *3.检测一个队列是否存在 ,返回boolean值
 *      $status = msg_queue_exists($msg_key);
 *4.可以查看当前队列的一些详细信息
 *      $message_queue_status =  msg_stat_queue($msg_queue);
 *5.将一条消息加入消息队列
 *  $message 可以是字符串，也可以是数组，数组将被序列化
 *      msg_send($msg_queue, $msg_type, "Hello, 1");
 *6.从消息队列中读取一条消息
 *      msg_receive($msg_queue, 1, $message_type, 1024, $message1);
 *7.移除消息队列
 * msg_remove_queue($msg_queue);
 */
class MsgQueue{
    public $key;
    public $msg_queue;
    public $pids=array();
    public $work_count=2;
    public function __construct()
    {
        set_time_limit(0);
        $this->key=ftok(__FILE__,1);
        if(!msg_queue_exists($this->key)){
            $this->msg_queue=msg_get_queue($this->key,0666);
        }else{
            die("msg_queue is exists");
        }

    }
    public function run(){
        //A->B->C.D.E.F
        $pid=pcntl_fork();
        if($pid >0){
            $father_pids[$pid]=$pid;
            $this->father();
            $this->recycle(posix_getpid(),$father_pids,"father");
        }elseif($pid ==0){

            sleep(5);//休息5秒 等待父进程发送任务
            $this->child();

        }else{
            die("fork error");
        }

    }
    public function child(){
        //fork N个进程来处理任务
        for($i=1;$i<=$this->work_count;$i++){
            $pid=pcntl_fork();
            if($pid > 0){
                $child_pids[$pid]=$pid;

            }elseif($pid ==0){
                while(1){
                    //MSG_IPC_NOWAIT 当队列里没有消息时，立即返回失败，如果不设置默认阻塞
                    $bool=msg_receive($this->msg_queue,0,$msg_type,1024,$message,true,MSG_IPC_NOWAIT,$error);
                    if(!$bool){
                        echo "child process ".posix_getpid()." exit\n";
                        exit();
                    }
                    echo "child process ".posix_getpid()." receive msg:".$message."\n";
                    sleep(1);
                }
            }else{
                die("fork error");
            }

        }
        //fork完毕，子进程等待孙子进程结束，并回收
        //此时孙子进程卡在循环，此代码由子进程执行，回收孙子进程
        $this->recycle(posix_getpid(),$child_pids,"child");
    }
    public function father(){
        $arr=range(1,20);
        foreach($arr as $val){
            $status=msg_send($this->msg_queue,1,$val);
            usleep(100);
        }
    }
    public function recycle($ppid,$pids,$type){

        //防止主进程先于子进程退出，形成僵尸进程
        while (count($pids) > 0) {
            foreach ($pids as $key => $pid) {
                $status = pcntl_waitpid($pid, $status);
                var_dump("status:".$status." pid: ".$pid." done!");
                if ($status == -1 || $status > 0) {
                    unset($pids[$key]);
                }
            }
            sleep(1);
        }
        echo PHP_EOL."type: ".$type." ppid: ".$ppid." recycle child process done!".PHP_EOL;

        if($type =="father"){
            //父进程往队列发送数据，很快就执行完毕,所以在最后子进程被回收时再删除队列
            if(!msg_remove_queue($this->msg_queue)){
                die("remove queue false");
            }
        }


    }

}
$msgQueue=new MsgQueue();
$msgQueue->run();