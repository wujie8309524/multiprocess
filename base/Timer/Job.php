<?php
class Job{
    public function test($param =array()){
        $time=time();
        echo "time:{$time},func:".get_class()."::".__FUNCTION__."(".json_encode($param).")\n";
    }
}

require_once (__DIR__."/Timer.php");
Timer::dellall();
//调用非静态方法会产生E_STRICT警告
/*
     5.3.0 	对面向对象里面的关键字的解析有所增强。
    在此之前，使用两个冒号来连接一个类和里面的一个方法，
    把它作为参数来作为回调函数的话，将会发出一个E_STRICT的警告，因为这个传入的参数被视为静态方法。
*/
Timer::add(1,array("Job","test"),array(),true);
Timer::add(3,array("Job","test"),array("a"=>1),false);

echo "Timer start".time()."\n";
Timer::run();

while (1){
    sleep(1);
    pcntl_signal_dispatch();
}