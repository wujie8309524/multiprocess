<?php
class Job{
    public function job($param =array()){
        $time=time();
        echo "time:{$time},func:".get_class()."::".__FUNCTION__."(".json_encode($param).")\n";
    }
}

require_once (__DIR__."/Timer.php");
Timer::dellall();
Timer::add(1,array("Job","job"),array(),true);
Timer::add(3,array("Job","job"),array("a"=>1),false);

echo "Timer start".time()."\n";
Timer::run();

while (1){
    sleep(1);
    pcntl_signal_dispatch();
}