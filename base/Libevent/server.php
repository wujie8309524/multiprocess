<?php
$socket = stream_socket_server("tcp://0.0.0.0:8000",$errno,$errstr);
$base = event_base_new();
$event = event_new();

function read_cb($socket,$flag,$base){
    fread($socket);
    fwrite("hello world\n");
}
function accept_cb($socket,$flag,$base){
    $conn = stream_socket_accept($socket, 0);
    stream_set_blocking($conn, 0);
    $event = event_new();
    event_set($event, $conn, EV_READ | EV_PERSIST, 'read_cb', $base);
    event_base_set($event, $base);
    event_add($event);
}

event_set($event, $socket, EV_READ | EV_PERSIST, 'accept_cb', $base);
event_base_set($event, $base);
event_add($event);
event_base_loop($base);