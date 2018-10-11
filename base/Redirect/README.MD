php输入输出流<br>
```php
STDIN = fopen("php://stdin");
STDOUT = fopen("php://stdout");
STDERR = fopen("php://stderr");

//修改默认输出stdout 为写入文件
var_dump(STDOUT);
fclose(STDOUT);
$STDOUT= fopen("test.txt","a");
var_dump("123");
var_dump('345');
```