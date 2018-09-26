<?php
/*
 * 正确使用场景
 * 使用不同文件句柄
 *
 */
$pid = pcntl_fork();
$fp = fopen("log.txt", "a");

if ($pid == 0)
{
    for($i = 0; $i < 1000; $i++)
    {
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, "黄河远上白云间，");
            fflush($fp);
            fwrite($fp, "一片孤城万仞山。");
            fflush($fp);
            fwrite($fp, "羌笛何须怨杨柳，");
            fflush($fp);
            fwrite($fp, "春风不度玉门关。\n");
            fflush($fp);
            flock($fp, LOCK_UN);
        }
    }
}
else if ($pid > 0)
{
    for($i = 0; $i < 1000; $i++)
    {
        if(flock($fp,LOCK_EX)){
            fwrite($fp, "葡萄美酒夜光杯，");
            fflush($fp);
            fwrite($fp, "欲饮琵琶马上催。");
            fflush($fp);
            fwrite($fp, "醉卧沙场君莫笑，");
            fflush($fp);
            fwrite($fp, "古来征战几人回。\n");
            fflush($fp);
            flock($fp,LOCK_UN);
        }

    }
}