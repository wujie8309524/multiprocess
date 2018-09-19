php进程间通信的另外一个手段就是通过 信号 来在进程间传递信息。信号是一种系统调用。
===
通常我们用的kill命令就是发送某个信号给某个进程的。<br>
具体有哪些信号可以在liunx/mac中运行kill -l查看。<br>
特点：
    信号承载信息量少

PHP < 5.3
 使用declare(ticks=1);
 配合pcntl_signal使用,表示每执行一条低级指令，就检查一次信号，如果检测到注册的信号，就调用其信号处理器。
```php
 if ( ! function_exists('pcntl_signal_dispatch')) {     
    // PHP < 5.3 uses ticks to handle signals instead of pcntl_signal_dispatch      
    // call sighandler only every 10 ticks      
     declare(ticks = 10);        
}
    // Make sure PHP has support for pcntl
    function_exists('pcntl_signal')

    //注册信号处理
pcntl_signal(SIGINT, array(__CLASS__, "signalHandler"),false);
```

PHP > 5.3
```php
 pcntl_signal_dispatch();//监听信号 代替 declare(ticks = 10);
```



一些php信号的意思如下：
（SIGNIT等信号可以自定义处理方式，默认终止进程）

SIGHUP     终止进程      终端线路挂断 <br>
SIGINT     终止进程     中断进程<br>        
SIGQUIT    建立CORE文件终止进程，并且生成core文件<br>
SIGILL   建立CORE文件       非法指令<br>
SIGTRAP    建立CORE文件       跟踪自陷<br>
SIGBUS   建立CORE文件       总线错误<br>
SIGSEGV   建立CORE文件        段非法错误<br>
SIGFPE   建立CORE文件       浮点异常<br>
SIGIOT   建立CORE文件        执行I/O自陷<br>
SIGKILL   终止进程     杀死进程<br>
SIGPIPE   终止进程      向一个没有读进程的管道写数据<br>
SIGALARM   终止进程     计时器到时<br>
SIGTERM   终止进程      软件终止信号<br>
SIGSTOP   停止进程     非终端来的停止信号<br>
SIGTSTP   停止进程      终端来的停止信号<br>
SIGCONT   忽略信号     继续执行一个停止的进程<br>
SIGURG   忽略信号      I/O紧急信号<br>
SIGIO     忽略信号     描述符上可以进行I/O<br>
SIGCHLD   忽略信号      当子进程停止或退出时通知父进程<br>
SIGTTOU   停止进程     后台进程写终端<br>
SIGTTIN   停止进程      后台进程读终端<br>
SIGXGPU   终止进程     CPU时限超时<br>
SIGXFSZ   终止进程     文件长度过长<br>
SIGWINCH    忽略信号     窗口大小发生变化<br>
SIGPROF   终止进程     统计分布图用计时器到时<br>
SIGUSR1   终止进程      用户定义信号1<br>
SIGUSR2   终止进程     用户定义信号2<br>
SIGVTALRM 终止进程     虚拟计时器到时<br>
