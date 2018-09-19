<?php

// Get the file token key
$key = ftok(__FILE__, 'a');
$shar_key = 1;

// 创建一个共享内存
$shm_id = shm_attach($key, 1024, 0666); // resource type
if ($shm_id === false) {
    die('Unable to create the shared memory segment' . PHP_EOL);
}

#设置一个值
shm_put_var($shm_id, $shar_key, 'test');

#删除一个key
//shm_remove_var($shm_id, $shar_key);

#获取一个值
$value = shm_get_var($shm_id,  $shar_key);
var_dump($value);

#检测一个key是否存在
// var_dump(shm_has_var($shm_id,  $shar_key));

#从系统中移除
shm_remove($shm_id);

#关闭和共享内存的连接
shm_detach($shm_id);