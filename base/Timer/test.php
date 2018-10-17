<?php
//证实每次pcntl_alarm预定一个计时器,然后当计时器到时间的时候,就会给当前进程触发SIGALRM信号
declare(ticks = 1);

function signal_handler($signal) {
    print "catch you ";
    pcntl_alarm(5);
}

pcntl_signal(SIGALRM, "signal_handler", true);
pcntl_alarm(5);

while(1) {
    sleep(1);
}
?>