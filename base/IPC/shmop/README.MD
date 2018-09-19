信号量与共享内存<br>
===
https://www.cnblogs.com/nickbai/articles/6133354.html<br>
特点：
---
    IPC最快
> 一、php提供两种扩展
  >>  1.shmop

  >>  2.Semaphore扩展中的sem函数
        类似 key-value 格式
        注意：$shar_key 只能是 int 型的参数。

> 二、信号量控制进程同步
 >>   类似于一把锁，访问前获取锁（获取不到则等待），访问后释放锁。
```php
<?php
$key=ftok(__FILE__,'t');

/**
 * 获取一个信号量资源
 *int $key [, int $max_acquire = 1 [, int $perm = 0666 [, int $auto_release = 1 ]]]
 *$max_acquire:最多可以多少个进程同时获取信号
 *$perm:权限 默认 0666
 *$auto_release：是否自动释放信号量
 */
$sem_id=sem_get($key);

#获取信号
sem_acquire($seg_id);

//do something 这里是一个原子性操作

//释放信号量
sem_release($seg_id);

//把次信号从系统中移除
sem_remove($sem_id);


//可能出现的问题
$fp = sem_get(fileinode(__DIR__), 100);
sem_acquire($fp);

$fp2 = sem_get(fileinode(__DIR__), 1));
sem_acquire($fp2);

```
