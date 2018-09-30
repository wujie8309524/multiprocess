<?php
/*
 * 基于select的简单tcp服务器
 * 可以支持多个并发连接
 * 将客户端传过来的字符大写后返回
 * 测试
 * telnet 127.0.0.1:8888
 */

$servsock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);  // 创建一个socket

if (FALSE === $servsock)
{
    $errcode = socket_last_error();
    fwrite(STDERR, "socket create fail: " . socket_strerror($errcode));
    exit(-1);
}

if (!socket_bind($servsock, '127.0.0.1', 8888))    // 绑定ip地址及端口
{
    $errcode = socket_last_error();
    fwrite(STDERR, "socket bind fail: " . socket_strerror($errcode));
    exit(-1);
}else{
	echo "listen:127.0.0.1:8888\n";
}

if (!socket_listen($servsock, 128))      // 允许多少个客户端来排队连接
{
    $errcode = socket_last_error();
    fwrite(STDERR, "socket listen fail: " . socket_strerror($errcode));
    exit(-1);
}
/* 要监听的三个sockets数组 */  
$read_socks = array();  
$write_socks = array();  
$except_socks = NULL;  // 注意 php 不支持直接将NULL作为引用传参，所以这里定义一个变量  
  
$read_socks[] = $servsock;
while (1)
{
    sleep(4);
	 /* 这两个数组会被改变，所以用两个临时变量 */

    echo "======begin-======".PHP_EOL;
    var_dump($tmp_reads);
    echo "======BEGIN-======".PHP_EOL.PHP_EOL;
	$tmp_reads = $read_socks;
    /*  array(1){
     *      resource(4) of type (Socket)
     *  }
     *
     *  array(2){
     *      resource(4) of type (Socket)
     *      resource(5) of type (Socket)
     *  }
     */


	$tmp_writes = $write_socks;
    //$tmp_reads 传入监听的描述符，返回活跃的描述符, 没有活跃则返回空
    //默认传入$servsock 当$servsock活跃时表示有链接到达，响应链接，并加入监控
    //当客户端连接活跃时，表示有数据到达
	$count = socket_select($tmp_reads, $tmp_writes, $except_socks, 4);  // timeout 传 NULL 会一直阻塞直到有结果返回


    /*  新连接来时，resource(4)活跃
     *  array(1){
     *      resource(4) of type (Socket)
     *  }
     *
     * 数据来时，resource(5)活跃
     *  array(1){
     *      resource(5) of type (Socket)
     *  }
     */
    echo "======end=========".PHP_EOL;
    var_dump($tmp_reads);
    echo "======END=========".PHP_EOL.PHP_EOL;


	 foreach ($tmp_reads as $read)  
    {
        //判断活跃的描述符 是否为连接描述符
        /* array(0){
         *  resource(4) of type (Socket)
         * }
         */
        if ($read == $servsock)  
        {  
            /* 有新的客户端连接请求 */  
            $connsock = socket_accept($servsock);  //响应客户端连接， 此时不会造成阻塞  
            if ($connsock)  
            {  
                socket_getpeername($connsock, $addr, $port);  //获取远程客户端ip地址和端口  
                echo "client connect server: ip = $addr, port = $port" . PHP_EOL;  
  
                // 把新的连接sokcet加入监听  
                $read_socks[] = $connsock;  
                $write_socks[] = $connsock;  
            }  
        }else{

		 /* 客户端传输数据 */  
            $data = socket_read($read, 1024);  //从客户端读取数据, 此时一定会读到数组而不会产生阻塞  
  
            if ($data === '')  
            {  
                //移除对该 socket 监听  
                foreach ($read_socks as $key => $val)  
                {  
                    if ($val == $read) unset($read_socks[$key]);  
                }  
  
                foreach ($write_socks as $key => $val)  
                {  
                    if ($val == $read) unset($write_socks[$key]);  
                }  
  
  
                socket_close($read);  
                echo "client close" . PHP_EOL;  
  
            }else{

				socket_getpeername($read, $addr, $port);  //获取远程客户端ip地址和端口
                echo "read from client # $addr:$port # " . $data;
                $data = strtoupper($data);  //小写转大写
                if (in_array($read, $tmp_writes))
                {
                   //如果该客户端可写 把数据回写给客户端
                   socket_write($read, $data);

                }


			}
		}
	}  
}

socket_close($servsock);
