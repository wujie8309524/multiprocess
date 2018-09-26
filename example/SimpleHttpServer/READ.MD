简单HTTP服务器
--
类似work进程模型，依赖libevent扩展

        1.master创建tcp服务器，启动子进程work接收客户端连接，处理逻辑
        2.子进程采用libevent库处理事件