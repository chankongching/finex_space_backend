<?php
namespace Common\Library\Tool;
//curl类
class Curl
{
    public function __construct(){
        return true;
    }

    private function execute($method, $url, $fields='', $userAgent='', $httpHeaders='', $username='', $password=''){
        $ch = $this->_create();
        if(false === $ch){
            return false;
        }
        if(is_string($url) && strlen($url)){
            $ret = curl_setopt($ch, CURLOPT_URL, $url);
        }else{
            return false;
        }
        //是否显示头部信息
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if($username != ''){
            curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        }
        $method = strtolower($method);
        if('post' == $method){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        }else if('put' == $method){
            curl_setopt($ch, CURLOPT_PUT, true);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);//设置curl超时秒数
        if(strlen($userAgent)){
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        }
        if(is_array($httpHeaders)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        }
        $ret = curl_exec($ch);
        if(curl_errno($ch)){
            curl_close($ch);
            return array(curl_error($ch), curl_errno($ch));
        }else{
            curl_close($ch);
            if(!is_string($ret) || !strlen($ret)){
                return false;
            }
            return $ret;
        }
    }

    //系统API接口post方法
    public function api_post( $post_data){
        $url = C('API_DOMAIN');
        $token_obj = new \Common\Library\Tool\Token();
        //创建token
        $token_time = NOW_TIME;
        $serial_num = C('API_TOKEN_SUFFIX').$token_time;
        $data['sign'] = $serial_num;
        $sign = sys_md5(data_auth_sign($data));
        $token_arr = array(
            'time'   => $token_time,
            'sign'   => $sign,
            'os'   => 'api_interface' //区别APP的来源
        );
        $ret_token = $token_obj::buildToken($token_arr);
        $post_data['token'] =  $ret_token;
        $post_data['ver'] =  '1.0.0';
        $data_string = json_encode($post_data);
        $fields['json'] = $data_string;
        return $this->post_request($url, $fields);

    }
    //post_request
    public function post_request($url, $fields, $userAgent = '', $httpHeaders = '', $username = '', $password = ''){
        $ret = $this->execute('POST', $url, $fields, $userAgent, $httpHeaders, $username, $password);
        if(false === $ret){
            return false;
        }
        if(is_array($ret)){
            return false;
        }
        return $ret;
    }

    //get_request
    public function get_request($url, $userAgent = '', $httpHeaders = '', $username = '', $password = ''){

        $ret = $this->execute('GET', $url, '', $userAgent, $httpHeaders, $username, $password);
        if(false === $ret){
            return false;
        }
        if(is_array($ret)){
            return false;
        }
        return $ret;
    }
    
    protected function _create(){
        $ch = null;
        if(!function_exists('curl_init')){
            return false;
        }
        $ch = curl_init();
        if(!is_resource($ch)){
            return false;
        }
        return $ch;
    }

}
?>