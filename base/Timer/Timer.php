<?php
class Timer
{
    /* $task:
     * array(
     *  1438156396 =>array(
     *      0=>array(
     *          "interval"=>1,
     *          "func"=>array("Dojob","job"),
     *          "argv"=>array(),
     *          "persist"=>false,
     *          ),
     *      ),
     *      1=>array(
     *
     *      )
     * ),
     *  1438156396 =>array(
     *
     * )
     */
    public static $task = [];
    public static $time = 1;
    public static function run($time = null){
        if($time){
            self::$time = $time;
        }
        self::installHandler();
        pcntl_alarm(1);
    }
    public static function installHandler(){
        pcntl_signal(SIGALRM,array("Timer","signalHandler"));
    }
    public static function signalHandler(){
        self::task();
        pcntl_alarm(self::$time);
    }
    public static function task(){
        if(empty(self::$task)){
            return;
        }
        foreach(self::$task as $time=>$arr){
            $current = time();
            foreach($arr as $k=>$job){
                $func = $job['func'];
                $argv = $job["argv"];
                $interval = $job['interval'];
                $persist = $job['persist'];
                if($current == $time){
                    call_user_func($func,$argv);
                    unset(self::$task[$time][$k]);
                }
                if($persist){

                    self::$task[$current+$interval][] = $job;
                }

            }
            if(empty(self::$task[$time])){
                unset(self::$task[$time]);
            }
        }

    }
    /*
     array(
            '1438156396' => array(
                   0  => array(1,array('Class','Func'), array(), true),
            )
    )
    说明:
    时间戳
    array(1,array('Class','Func'), array(), true)
    参数依次表示: 执行时间间隔,回调函数,传递给回调函数的参数,是否持久化(ture则一直保存在数据中,否则执行一次后删除)
     */
    public static function add($interval,$func,$argv =array(),$persist = false){
        if(is_null($interval))
            return;
        $time = time() + $interval;
        self::$task[$time][] = array('func'=>$func,"argv"=>$argv,"interval"=>$interval,"persist"=>$persist);
    }
    public static function dellall(){
        self::$task = [];
    }
}