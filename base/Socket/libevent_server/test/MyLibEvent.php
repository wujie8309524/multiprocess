<?php
/**
* 基于libevent的webSocket连接,不支持php7
*/
class MyLibEvent
{
    protected $eventBase;
    protected $allEvents = [];

    public function __construct()
    {
        if (!extension_loaded('libevent')) {
        echo 'libevent extension is require' . PHP_EOL;
        exit(250);
    }
        $this->eventBase = event_base_new();
    }

    public function add($fd, $flag, $func, $args = array())
    {
        $fd_key = (int)$fd;
        $event = event_new();
        if (!event_set($event, $fd, $flag | EV_PERSIST, $func, null)) {
                return false;
        }
        if (!event_base_set($event, $this->eventBase)) {
                return false;
        }
        if (!event_add($event)) {
                return false;
        }

        $this->allEvents[$fd_key][$flag] = $event;
        return true;
    }

    public function del($fd, $flag)
    {
        $fd_key = (int)$fd;
        if (isset($this->allEvents[$fd_key][$flag])) {
            event_del($this->allEvents[$fd_key][$flag]);
            unset($this->allEvents[$fd_key][$flag]);
        }
        if (empty($this->allEvents[$fd_key])) {
            unset($this->allEvents[$fd_key]);
        }
    }

    public function loop()
    {
        event_base_loop($this->eventBase);
    }
}
