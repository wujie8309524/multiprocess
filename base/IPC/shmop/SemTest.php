<?php
/*
 * 3个子进程读取共享内存中的变量，不存在设为0，存在自增
 * 父进程读取共享内存的值
 *
 * 结论：
 *  count=0;
 *     3个子进程同时打开共享内存，都没有读到count的值，所以都设为0，最终也为0
 *     通过信号量避免并发写入
 *
 */

//共享内存通信

//1、创建共享内存区域
$shm_key = ftok(__FILE__, 't');
$shm_id = shm_attach( $shm_key, 1024, 0655 );
const SHARE_KEY = 1;
$childList = [];

//加入信号量控制
$sem_id=ftok(__FILE__,"s");
$signal= sem_get($sem_id);



//2、开3个进程 读写 该内存区域
for( $i = 0; $i < 3; $i++ ){

    $pid = pcntl_fork();
    if( $pid == -1 ){
        exit('fork fail!' . PHP_EOL);
    }else if( $pid == 0 ){
        //加入信号量 先获取，取不到进程等待
        sem_acquire($signal);

        //子进程从共享内存块中读取 写入值 +1 写回
        if ( shm_has_var($shm_id, SHARE_KEY) ){
            // 有值,加一
            $count = shm_get_var($shm_id, SHARE_KEY);
            $count ++;
            //模拟业务处理逻辑延迟
            $sec = rand( 1, 3 );
            sleep($sec);

            shm_put_var($shm_id, SHARE_KEY, $count);
        }else{
            // 无值,初始化
            $count = 0;
            //模拟业务处理逻辑延迟
            $sec = rand( 1, 3 );
            sleep($sec);

            shm_put_var($shm_id, SHARE_KEY, $count);
        }

        echo "child process " . getmypid() . " is writing ! now count is $count\n";

        //加入信号量 用完释放
        sem_release($signal);

        exit( "child process " . getmypid() . " end!\n" );
    }else{
        $childList[$pid] = 1;
    }
}

// 等待所有子进程结束
while( !empty( $childList ) ){
    $childPid = pcntl_wait( $status );
    if ( $childPid > 0 ){
        unset( $childList[$childPid] );
    }
}

//父进程读取共享内存中的值
$count = shm_get_var($shm_id, SHARE_KEY);
echo "final count is " . $count . PHP_EOL;


//3、去除内存共享区域
#从系统中移除
shm_remove($shm_id);
#关闭和共享内存的连接
shm_detach($shm_id);

//把此信号从系统中移除
sem_remove($signal);
/*
child process 73666 is writing ! now count is 0
child process 73666 end!
child process 73667 is writing ! now count is 1
child process 73667 end!
child process 73668 is writing ! now count is 2
child process 73668 end!
final count is 2
*/



