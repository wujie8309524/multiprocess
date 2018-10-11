<?php
function redirectSTD(){
    global $STDOUT,$STDERR;
    fclose(STDOUT);
    fclose(STDERR);
    $STDOUT = fopen("out.txt","a");
    $STDERR = fopen("error.txt","a");
}
redirectSTD();
echo "123";
$data = [
    "name" => "test",
    "age" => "20",
];
print_r($data);