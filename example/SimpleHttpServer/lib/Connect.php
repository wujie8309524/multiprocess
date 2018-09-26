<?php
/*
 * 处理连接类，主要功能：
 * 1.监听数据
 * 2.处理数据
 */
class Connect{
    public $_socket=null;
    public function __construct($socket,$libevent,$id)
    {
        $this->_socket=$socket;
        //添加数据读取回调
        $libevent->addBuffer($socket,[$this,"readCBBuff"],$id);

    }

     //数据到达回调函数
    public function readCBBuff($buffer,$id){
        $data=event_buffer_read($buffer,1024);
        if(substr($data,0,3) == 'EOF' || $data === false){
            //接收完成，关闭此连接
            $this->del();
            return;
        }else{
            $msg ="Receive data from client: ".$data;
            $this->_log(posix_getpid(),$id,$msg);
        }
        $response=$this->data();
        //发送数据给客户端
        event_buffer_write($buffer,$response);

    }
    public function readCB($socket,$flag,$base){
        $buffer=fread($socket,1024);
        if(substr($buffer,0,3) == 'EOF' || $buffer === false){
            if((feof($socket) || !is_resource($socket)) || $buffer === false){
                //接收完成，关闭此连接
                $this->del();
                return;
            }
            //接收完成，关闭此连接
            $this->del();
            return;
        }else{
            $msg ="Receive data from client: ".$buffer;
            $this->_log(posix_getpid(),0,$msg);

        }
        $response=$this->data();
        //发送数据给客户端

        if(is_resource($socket)){
            $written=false;
            while($written == false){
              $written = fwrite($socket,$response);
            }

        }
    }
    public function _log($pid,$conn_id,$msg){
        printf("Pid : %s , Conn_id : %s, Msg : %s \n",$pid,$conn_id,$msg);
    }
    public function del(){
        Worker::$loop->del($this->_socket);

    }
    public function data(){
        $response = "HTTP/1.1 200 OK\r\n";
        $response .= "Server: phphttpserver\r\n";
        $response .= "Content-Type: text/html\r\n";

        $response .= "Content-Length: 3\r\n\r\n";
        $response .= "ok\n";

        return $response;
    }


}