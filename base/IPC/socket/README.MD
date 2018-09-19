socket
==
可以实现不同主机进程之间通讯
--

socket_accept() 接受一个Socket连接

socket_bind() 把socket绑定在一个IP地址和端口上

socket_clear_error() 清除socket的错误或者最后的错误代码

socket_close() 关闭一个socket资源

socket_connect() 开始一个socket连接

socket_create_listen() 在指定端口打开一个socket监听

socket_create_pair() 产生一对没有区别的socket到一个数组里

socket_create() 产生一个socket，相当于产生一个socket的数据结构

socket_get_option() 获取socket选项

socket_getpeername() 获取远程类似主机的ip地址

socket_getsockname() 获取本地socket的ip地址

socket_iovec_add() 添加一个新的向量到一个分散/聚合的数组

socket_iovec_alloc() 这个函数创建一个能够发送接收读写的iovec数据结构

socket_iovec_delete() 删除一个已经分配的iovec

socket_iovec_fetch() 返回指定的iovec资源的数据

socket_iovec_free() 释放一个iovec资源

socket_iovec_set() 设置iovec的数据新值

socket_last_error() 获取当前socket的最后错误代码

socket_listen() 监听由指定socket的所有连接

socket_read() 读取指定长度的数据

socket_readv() 读取从分散/聚合数组过来的数据

socket_recv() 从socket里结束数据到缓存

socket_recvfrom() 接受数据从指定的socket，如果没有指定则默认当前socket

socket_recvmsg() 从iovec里接受消息

socket_select() 多路选择

socket_send() 这个函数发送数据到已连接的socket

socket_sendmsg() 发送消息到socket

socket_sendto() 发送消息到指定地址的socket

socket_set_block() 在socket里设置为块模式   阻塞模式

socket_set_nonblock() socket里设置为非块模式  非阻塞模式

socket_set_option() 设置socket选项

socket_shutdown() 这个函数允许你关闭读、写、或者指定的socket

socket_strerror() 返回指定错误号的详细错误

socket_write() 写数据到socket缓存

socket_writev() 写数据到分散/聚合数组




stream_bucket_append函数：为队列添加数据

stream_bucket_make_writeable函数：从操作的队列中返回一个数据对象

stream_bucket_new函数：为当前队列创建一个新的数据

stream_bucket_prepend函数：预备数据到队列

stream_context_create函数：创建数据流上下文

stream_context_get_default函数：获取默认的数据流上下文

stream_context_get_options函数：获取数据流的设置

stream_context_set_option函数：对数据流、数据包或者上下文进行设置

stream_context_set_params函数：为数据流、数据包或者上下文设置参数

stream_copy_to_stream函数：在数据流之间进行复制操作

stream_filter_append函数：为数据流添加过滤器

stream_filter_prepend函数：为数据流预备添加过滤器

stream_filter_register函数：注册一个数据流的过滤器并作为PHP类执行

stream_filter_remove函数：从一个数据流中移除过滤器

stream_get_contents函数：读取数据流中的剩余数据到字符串

stream_get_filters函数：返回已经注册的数据流过滤器列表

stream_get_line函数：按照给定的定界符从数据流资源中获取行

stream_get_meta_data函数：从封装协议文件指针中获取报头/元数据

stream_get_transports函数：返回注册的Socket传输列表

stream_get_wrappers函数：返回注册的数据流列表

stream_register_wrapper函数：注册一个用PHP类实现的URL封装协议

stream_select函数：接收数据流数组并等待它们状态的改变

stream_set_blocking函数：将一个数据流设置为堵塞或者非堵塞状态

stream_set_timeout函数：对数据流进行超时设置

stream_set_write_buffer函数：为数据流设置缓冲区

stream_socket_accept函数：接受由函数stream_ socket_server()创建的Socket连接

stream_socket_client函数：打开网络或者UNIX主机的Socket连接

stream_socket_enable_crypto函数：为一个已经连接的Socket打开或者关闭数据加密

stream_socket_get_name函数：获取本地或者网络Socket的名称

stream_socket_pair函数：创建两个无区别的Socket数据流连接

stream_socket_recvfrom函数：从Socket获取数据，不管其连接与否

stream_socket_sendto函数：向Socket发送数据，不管其连接与否

stream_socket_server函数：创建一个网络或者UNIX Socket服务端

stream_wrapper_restore函数：恢复一个事先注销的数据包

stream_wrapper_unregister函数：注销一个URL地址包

