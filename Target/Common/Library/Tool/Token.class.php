<?php
namespace Common\Library\Tool;

class Token {
    public function __construct(){
        return true;
    }
    public static function buildToken($param = array() , $expire = 0 ,$key = ''){
        if($param){
            $rst = '';
            foreach ($param as $k => $v){
                $rst .= $k.'##'.$v.'@@';
            }
            return think_encrypt(trim($rst,'@@'), $key, $expire);
        }
        return false;
    }
    
    public static function getToken($token, $key = ''){
        $rst = think_decrypt($token, $key);
        if(!$rst) return false;
        $arr = explode('@@', $rst);
        
        if($arr){
            $rst = array();
            foreach($arr as $aa){
                $tmp = explode('##', $aa);
                if($tmp){
                    $rst[$tmp[0]] = $tmp[1];
                }
            }
            return $rst;
        }
        return false;
    }
    
}