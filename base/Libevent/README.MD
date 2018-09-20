#php libevent扩展具有很强大的功能。<br>以下摘自百度百科：

　　Libevent 是一个用C语言编写的、轻量级的开源高性能网络库，主要有以下几个亮点：事件驱动（ event-driven），高性能;轻量级，专注于网络，不如 ACE 那么臃肿庞大；

源代码相当精炼、易读；跨平台，支持 Windows、 Linux、 *BSD 和 Mac Os；支持多种 I/O 多路复用技术， epoll、 poll、 dev/poll、 select 和 kqueue 等；支持 I/O，

定时器和信号等事件；注册事件优先级。

##安装：
    1.是否已安装
    rpm -qa |grep libevent      
    目录：/usr/lib /usr/local/lib /usr/lib64
##基本用法：
    事件管理器内部有一个循环。
    循环阻塞在epoll/kqueue等系统调用上。
    直到一个或者一些事件发生，去处理这些事件
    要素：
        事件管理器、事件event、监听fd或者信号、绑定
    1.创建事件管理器
    $base = event_base_new();
    
    2.事件
    $event = event_new();
    
    3.设置事件监听属性,事件属性
    描述符：$fd= STDIN $fd=$socket
    event_set($event, 描述符, EV_READ | EV_PERSIST, 'callback', $base);
    event_base_set($event, $base);
    
    4.绑定并开启监听
    event_add($event);
    event_base_loop($base)
    
    5.回调函数
    function callback(){}
    
##libevent buffer
    libevent buffer 封装了read/recv等细节
    当触发读事件时，直接读取$buffer就可获取数据
