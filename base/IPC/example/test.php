<?php
/*使用proc_open打开一个进程，调用C程序。
同时返回一个双向管道pipes数组，
PHP向$pipe['0']中写数据，从$pipe['1']中读数据
*/
$desc=array(
    0=> array("pipe","r"),////标准输入，子进程从此管道读取数据
    1=> array("pipe","w"),////标准输出，子进程向此管道写入数据
    //2 => array("file", "/opt/figli/php/error-output.txt","a")	//标准错误，写入到指定文件
);
$handle= proc_open("/Users/wujie/www/multiprocess/base/IPC/example/test",
        $desc,
        $pipes
);
if(!isset($argv[1]))
    die("argv[1] is empty!");
fwrite($pipes['0'],$argv[1]."\n");
echo fgets($pipes[1]);

fclose($pipes['0']);
fclose($pipes['1']);
proc_close($handle);