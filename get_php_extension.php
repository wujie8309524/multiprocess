<?php
$ext_info = array();

$loaded_extension = get_loaded_extensions();

foreach ($loaded_extension as $ext) {
    $funs = get_extension_funcs($ext);
    if (!empty($funs) && is_array($funs)) {
        foreach ($funs as $fun) {
            $reflect = new ReflectionFunction($fun);
            //获取函数参数信息
            $params = $reflect->getParameters();
            $param_str = '';
            if(!empty($params) && is_array($params)) {
                foreach($params as $param) {
                    if($param->getName() != '') {
                        $param_str .= '$'.$param->getName().',';
                    }
                }
                $param_str = substr($param_str, 0, -1);
            }
            $ext_info[$ext][] = $fun.'('.$param_str.')';
        }
    }
}

echo '<pre>';
print_r ($ext_info);