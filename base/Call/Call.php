<?php
  
function a($b,$c){   
   echo $b.$c.PHP_EOL;     
}   
call_user_func('a', "李","晓亮");   

call_user_func('a', "php","程序员");   
//显示 李晓亮 
//     php程序员     
  
//调用类内部的方法比较奇怪，用的是array，当然省去了new，也是满有新意的:   
class a {   
function b($c){   
  echo $c.PHP_EOL;   
	}   
}   
//严格模式下，虽然能输出但是会报错，静态方法才能这样调用
//call_user_func(array("a", "b"),"李晓亮");   
call_user_func(array(new a(),"b","test"));


//call_user_func_array函数和call_user_func相似,不过是换了一种方式传递了参数,让参数的结构更清晰   
function m($b, $c){   
	echo $b.$c.PHP_EOL;    
}   
call_user_func_array('m', array("李", "晓亮"));   

//call_user_func_array函数也可以调用类内部的方法,这时传的参数为数组  
Class ClassA {     
	static function bc($b, $c) {   
     $bc = $b.$c;   
     echo $bc.PHP_EOL;   
    }   
}   
call_user_func_array(array('ClassA','bc'), array("php", "程序员"));   
  
//显示  php程序员     
