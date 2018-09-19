<?php
/*
 * 管道通讯-简单实例
 * 两个子进程向文件中写信息
 * 父进程监听文件是否写入完成，完成之后copy一份
 */
$PipePath="/tmp/test.pipe";
if(!file_exists($PipePath)){
    if(!posix_mkfifo($PipePath,0666)){
        die("mk pipe false!".PHP_EOL);
    }
}
for($i=0;$i<2;$i++){
    $pid=pcntl_fork();
    if($pid == 0){
        //子进程
        file_put_contents("./pipe.log","Child ".$i." write pipe \n",FILE_APPEND);//写入文件
        $file=fopen($PipePath,"w");
        fwrite($file,"Child ".$i." write pipe \n");
        fclose($file);
        exit();

    }
}
//父进程
/*
 * 1.读取管道中的写入状态，判断是否写完
 * 2.拷贝写好的文件
 * 3.删除管道
 * 4.回收子进程
 */
$file=fopen($PipePath,"r");
$line=0;
while(1){
    $end=fread($file,1024);

    foreach(str_split($end) as $char){

        if($char == "\n"){
            $line++;
        }
    }
    if($line == 2){
        copy("./pipe.log","./pipe_copy.log");
        fclose($file);
        unlink($PipePath);
        pcntl_wait($status);
        exit("ok \n");
    }
}