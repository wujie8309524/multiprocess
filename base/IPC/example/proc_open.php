<?php
/*
 * proc_open 打开php进程，执行php代码
 *
 */
$desc=array(
    0=>array('pipe',"r"),
    1=>array('pipe',"w"),
);
$process=proc_open('php',$desc,$pipes);
if($process){

    // $pipes 现在看起来是这样的：
    // 0 => 可以向子进程标准输入写入的句柄
    // 1 => 可以从子进程标准输出读取的句柄
    fwrite($pipes[0],'<?php print_r($_SERVER); ?>');
    fclose($pipes[0]);
    echo stream_get_contents($pipes[1]);
    fclose($pipes[1]);


    // 切记：在调用 proc_close 之前关闭所有的管道以避免死锁。
    $return_value = proc_close($process);

    echo "command returned $return_value\n";
}