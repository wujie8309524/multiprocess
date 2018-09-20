<?php
/* 简单php版http服务器
 * 基于socket，事件机制采用select
 * 解析http请求（执行php脚本，返回给客户端）
 *
 * 测试：
 * curl 127.0.0.1:8888
 * ab -c 100 -n 1000 http://127.0.0.1:8888/
 *
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
	 /* 这两个数组会被改变，所以用两个临时变量 */  
	$tmp_reads = $read_socks;  
	$tmp_writes = $write_socks;  
	$count = socket_select($tmp_reads, $tmp_writes, $except_socks, NULL);  // timeout 传 NULL 会一直阻塞直到有结果返回  
	 foreach ($tmp_reads as $read)  
    {  
  
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
                //
                // $data: http请求 GET / HTTP/1.0\n Host: 127.0.0.1:8888\n
                //
                echo "read from client # $addr:$port # " . $data;  

 				$response = "HTTP/1.1 200 OK\r\n";  
                $response .= "Server: phphttpserver\r\n";  
                $response .= "Content-Type: text/html\r\n";

                $response .= "Content-Length: 3\r\n\r\n";
                $response .= "ok\n";

                /*
                 * 可以引入php文件执行,$str获取php文件return后的字符串
                 *
                $str= include "test.php";
                $response .= "Content-Length: ".strlen($str)."\r\n\r\n";
				$response .= $str;
                */

				if (in_array($read, $tmp_writes))  
                {  
                    //如果该客户端可写 把数据回写给客户端  
                    socket_write($read, $response);

                    socket_close($read);  // 主动关闭客户端连接  
                    //移除对该 socket 监听  
                    foreach ($read_socks as $key => $val)  
                    {  
                        if ($val == $read) unset($read_socks[$key]);  
                    }  
  
                    foreach ($write_socks as $key => $val)  
                    {  
                        if ($val == $read) unset($write_socks[$key]);  
                    }  
                }
			}
		}
	}  
}

socket_close($servsock);
