<?php
/*
 * 基于libevent的简单http服务器
 */
$socket = stream_socket_server("tcp://0.0.0.0:8888", $errno, $errstr);

$base = event_base_new();
$event = event_new();

event_set($event, $socket, EV_READ | EV_PERSIST, 'accept_cb', $base);
event_base_set($event, $base);
event_add($event);
event_base_loop($base);

function read_cb($buffer)
{
    $client_data = '';
    while ($read = event_buffer_read($buffer, 1024)) {
        $client_data .= $read;
    }

    echo "client say : " . $client_data .PHP_EOL;
    $response = "HTTP/1.1 200 OK\r\n";
    $response .= "Server: phphttpserver\r\n";
    $response .= "Content-Type: text/html\r\n";

    $response .= "Content-Length: 3\r\n\r\n";
    $response .= "ok\n";
    event_buffer_write($buffer, $response);

}

function write_cb($buffer)
{
    //echo "我在打酱油 " . PHP_EOL;
}

function error_cb($buffer, $error)
{
    // 客户端断开连接之后,清除
    event_buffer_disable($GLOBALS['buffer'], EV_READ | EV_WRITE);
    event_buffer_free($GLOBALS['buffer']);
    fclose($GLOBALS['connection']);
    unset($GLOBALS['buffer'], $GLOBALS['connection']);
}

function accept_cb($socket, $flag, $base)
{
    $connection = stream_socket_accept($socket);
    stream_set_blocking($connection, 0);

    $buffer = event_buffer_new($connection, 'read_cb', 'write_cb', 'error_cb');
    event_buffer_base_set($buffer, $base);
    event_buffer_timeout_set($buffer, 30, 30);
    event_buffer_watermark_set($buffer, EV_READ, 0, 0xffffff);
    event_buffer_priority_set($buffer, 10);
    event_buffer_enable($buffer, EV_READ | EV_PERSIST);

    // 必须将 $connection 和 $buffer 赋值给一个全局变量，否则无法生效
    $GLOBALS['connection'] = $connection;
    $GLOBALS['buffer'] = $buffer;
}