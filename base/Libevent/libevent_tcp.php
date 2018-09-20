<?php
$socket = stream_socket_server ('tcp://0.0.0.0:8000', $errno, $errstr);
echo "TCP server on 0.0.0.0:8000".PHP_EOL;
stream_set_blocking($socket, 0);
$base = event_base_new();
$event = event_new();
//1.创建一个socket描述符，并交给event托管
event_set($event, $socket, EV_READ | EV_PERSIST, 'ev_accept', $base);
event_base_set($event, $base);
event_add($event);
event_base_loop($base);

$GLOBALS['connections'] = array();
$GLOBALS['buffers'] = array();

function ev_accept($socket, $flag, $base) {
    static $id = 0;

    $connection = stream_socket_accept($socket);
    stream_set_blocking($connection, 0);

    $id += 1;
    //2.当有新连接时，将新连接交给event托管
    //event_buffer_new 封装了read/recv等细节，当触发读写事件时，直接读取$buffer就可获取数据
    $buffer = event_buffer_new($connection, 'ev_read', NULL, 'ev_error', $id);
    event_buffer_base_set($buffer, $base);
    event_buffer_timeout_set($buffer, 30, 30);
    event_buffer_watermark_set($buffer, EV_READ, 0, 0xffffff);
    event_buffer_priority_set($buffer, 10);
    //event_buffer_enable 封装了event_add event_base_loop
    event_buffer_enable($buffer, EV_READ | EV_PERSIST);

    // we need to save both buffer and connection outside
    $GLOBALS['connections'][$id] = $connection;
    $GLOBALS['buffers'][$id] = $buffer;
    //var_dump($GLOBALS);
}
function ev_read($buffer, $id) {
    //3.当新连接有数据可读时，打印数据
    echo "connect id:".$id.PHP_EOL;
    while ($read = event_buffer_read($buffer, 256)) {
        var_dump($read);
    }
}
function ev_error($buffer, $error, $id) {
    event_buffer_disable($GLOBALS['buffers'][$id], EV_READ | EV_WRITE);
    event_buffer_free($GLOBALS['buffers'][$id]);
    fclose($GLOBALS['connections'][$id]);
    unset($GLOBALS['buffers'][$id], $GLOBALS['connections'][$id]);
}


?>