flock锁是基于内核中打开文件句柄的
==
1.相同文件句柄，两次加锁是更新锁

    <?php
    $fp = fopen("demo.log", "a");
     
    if(flock($fp, LOCK_EX))
    {
        echo "加锁成功\n";
    }
     
    if(flock($fp, LOCK_EX))
    {
        echo "加锁成功\n";
    }
    //两次加锁都能成功，更新锁
2.不同文件句柄，第一次加锁未释放，第二次加锁会阻塞

    <?php
    $fp1 = fopen("demo.log", "a");
    $fp2 = fopen("demo.log", "a");
     
    if(flock($fp1, LOCK_EX))
    {
            echo "fp1加锁成功\n";
    }
     
     
    if(flock($fp2, LOCK_EX))
    {
            echo "fp2加锁成功\n";
    }
    //锁等待
   
   
  
===