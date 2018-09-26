<?php
/*封装libevent类的基本使用方法：
 * 1.初始化事件基类
 * $base=event_base_new();
 * 2.初始化一个事件
 * $event=event_new();
 * 3.设置事件参数
 * event_set($event, $socket, EV_READ | EV_PERSIST, 'accept_cb', $base);
 * event_base_set($event, $base);
 * 4.绑定事件，并开始监听
 * event_add($event);
 * event_base_loop($base);
 *
 * libevent buffer基本用法：
 * 1.初始化事件基类
 * $base = event_base_new();
 * 2.初始化带buffer的事件
 * $eb = event_buffer_new(STDIN, "print_line", NULL, "error_func", $base);
 * 3.设置事件参数
 * //event_buffer_new 封装了read/recv等细节，当触发读写事件时，直接读取$buffer就可获取数据
 * event_buffer_base_set($eb, $base);
 * //event_buffer_enable 封装了event_add
 * event_buffer_enable($eb, EV_READ);
 * 4.开始监听
 * event_base_loop($base);
 *
 * 5.回调函数
 * 读数据
 * event_buffer_read($buf, 4096);
 * 写数据
 * event_buffer_write($buf,$data)
 *
 *
 */
class Libevent
{
    public $base = null;
    public $events = [];

    public function __construct()
    {
        $this->base = event_base_new();

    }
    //添加新的事件
    //$socket :监听的描述符
    //$func : 回调函数
    public function add($socket,$func)
    {
        if(!is_resource($socket))
            return;

        $event= event_new();
        event_set($event,$socket,EV_READ | EV_PERSIST, $func, $this->base);
        event_base_set($event,$this->base);
        event_add($event);
        //记录事件
        $this->events[(int)$socket] = $event;

    }
    public function addBuffer($socket,$func,$id){
        if(!is_resource($socket)){
            return;
        }
        $eb = event_buffer_new($socket, $func, NULL, [$this,"error_func"], $id);
        event_buffer_base_set($eb, $this->base);
        event_buffer_enable($eb, EV_READ | EV_PERSIST);
        //记录事件
        $this->events[(int)$socket] = $eb;

    }
    public function error_func(){

    }

    public function loop()
    {
        event_base_loop($this->base);
    }
    public function del($socket)
    {
        if(!is_resource($socket))
            return;
        event_del($this->events[(int)$socket]);
        unset($this->events[(int)$socket]);
        fclose($socket);
    }

}