<?php

function print_line($buf, $arg)
{
    static $max_requests;

    $max_requests++;

    if ($max_requests == 10) {
        event_base_loopexit($arg);
    }

    // print the line
    //当回调函数被触发时，直接读取$buf就可获得数据，libevent已经封装了recv/read
    echo event_buffer_read($buf, 4096);
}

function error_func($buf, $what, $arg)
{
    // handle errors
}

$base = event_base_new();
//event_buffer_new 封装了read/recv等细节，当触发读写事件时，直接读取$buffer就可获取数据
$eb = event_buffer_new(STDIN, "print_line", NULL, "error_func", $base);

event_buffer_base_set($eb, $base);
//event_buffer_enable 封装了event_add event_base_loop
event_buffer_enable($eb, EV_READ);

event_base_loop($base);

?>
