<?php
/**
 * APP接口公用函数
 * author: 刘富国
 */


/**
 * 后台调用前台API接口公共方法
 * @param string $is_record
 * 劉富國
 * 2017-11-07
 */
if (!function_exists('curl_api_post')) {
    function curl_api_post($post_data){
        if(empty($post_data) or empty($post_data['server'])) return false;
    
        $curl_obj = new \Common\Library\Tool\Curl();
        $ret = $curl_obj->api_post($post_data);
        return json_decode($ret,true);
    }
}

/**
 * 记录APP post过来的数据
 * @param string $is_record
 * 劉富國
 * 2017-10-25
 */
if (!function_exists('post_log')) {
    function post_log($is_record = false){
        if($is_record || C('IS_DEBUG_VER')){
            $PostLog = M('PostLog');
            $post = json_encode($_POST);
            $file = json_encode($_FILES);
            $url = $_SERVER['REQUEST_URI'];
            $data['plog_data']  = $post;
            $data['plog_ip']    = get_client_ip();
            $data['plog_time']  = time();
            $data['url'] = $url;
            $PostLog->data($data)->add();
            if(!empty($_FILES)){
                $data['plog_data'] = $file;
                $PostLog->data($data)->add();
            }
        }
    }
}



/**
 * 数据签名认证
 *
 * @param array $data
 *        	被认证的数据
 * @return string 签名
 * @author 刘富国
 * * 2017-10-19
 */
if (!function_exists('data_auth_sign')) {
    function data_auth_sign($data) {
        // 数据类型检测
        if (! is_array ( $data )) {
            $data = ( array ) $data;
        }
        ksort ( $data ); // 排序
        $code = http_build_query ( $data ); // url编码并生成query字符串
        $sign = sha1 ( $code ); // 生成签名
        return $sign;
    }
}
/**
 * 系统非常规MD5加密方法
 * @param  string $str  要加密的字符串
 * @param  string $salt 加密盐值
 * @return string
 * @author 刘富国
 * * 2017-10-19
 */
if (!function_exists('sys_md5')) {
    function sys_md5($str, $salt = ''){
        return $str === '' ? '' : md5( C('SYS_AUTO_SECRET_KEY').sha1($str.$salt));
    }
}

/**
 * 系统加密方法
 *
 * @param string $data
 *        	要加密的字符串
 * @param string $key
 *        	加密密钥
 * @param int $expire
 *        	过期时间 单位 秒
 * @return string
 * @author 刘富国
 * * 2017-10-19
 */
if (!function_exists('think_encrypt')) {
    function think_encrypt($data, $key = '', $expire = 0) {
        $key = md5 ( empty ( $key ) ? C ( 'SYS_AUTO_SECRET_KEY' ) : $key );
        $expire = sprintf ( '%010d', $expire ? $expire + time () : 0 );
        $data = base64_encode ( $expire.$data );
        $x = 0;
        $len = strlen ( $data );
        $l = strlen ( $key );
        $char = '';

        for($i = 0; $i < $len; $i ++) {
            if ($x == $l)
                $x = 0;
            $char .= substr ( $key, $x, 1 );
            $x ++;
        }

        $str = '';
        for($i = 0; $i < $len; $i ++) {
            $str .= chr ( ord ( substr ( $data, $i, 1 ) ) + (ord ( substr ( $char, $i, 1 ) )) % 256 );
        }
        return str_replace ( array (
            '+',
            '/',
            '='
        ), array (
            '-',
            '_',
            ''
        ), base64_encode ( $str ) );
    }
}

/**
 * 系统解密方法
 *
 * @param string $data
 *        	要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param string $key
 *        	加密密钥
 * @return string
 * @author 刘富国
 * * 2017-10-19
 */

if (!function_exists('think_decrypt')) {
    function think_decrypt($data, $key = '') {
        $key = md5 ( empty ( $key ) ? C ( 'SYS_AUTO_SECRET_KEY' ) : $key );
        $data = str_replace ( array (
            '-',
            '_'
        ), array (
            '+',
            '/'
        ), $data );
        $mod4 = strlen ( $data ) % 4;
        if ($mod4) {
            $data .= substr ( '====', $mod4 );
        }
        $data = base64_decode ( $data );
        $x = 0;
        $len = strlen ( $data );
        $l = strlen ( $key );
        $char = $str = '';

        for($i = 0; $i < $len; $i ++) {
            if ($x == $l)
                $x = 0;
            $char .= substr ( $key, $x, 1 );
            $x ++;
        }

        for($i = 0; $i < $len; $i ++) {
            if (ord ( substr ( $data, $i, 1 ) ) < ord ( substr ( $char, $i, 1 ) )) {
                $str .= chr ( (ord ( substr ( $data, $i, 1 ) ) + 256) - ord ( substr ( $char, $i, 1 ) ) );
            } else {
                $str .= chr ( ord ( substr ( $data, $i, 1 ) ) - ord ( substr ( $char, $i, 1 ) ) );
            }
        }
        // 解密后得到的加密时第一次的base64_encode
        $str = base64_decode ( $str );
        $expire = substr ( $str, 0, 10 );
        $rst = substr ( $str, 10 );

        if ($expire > 0 && $expire < time ()) {
            return '';
        }
        return $rst;
    }
}



/**
 * 随机生成一个字符串
 * @param number $length
 * @param string $type 类型：num、返回数字，char：返回可能有相似的字符串，chars:返回大小写有相似的字母和数字，all：返回没有相似的字符串
 * @return string
 * 刘富国
 * * 2017-10-19
 */
if (!function_exists('build_rand_str')) {
    function build_rand_str($length = 6, $type = 'all'){
        if($length < 0)
            return '';

        $nums   = '0123456789';
        $chars  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $chars2 = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
        $chars3 = '+-*/&!<>=@#$%^[]{}~()abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $need   = '';
        $rst = '';
        switch($type){
            case 'num':
                $need = $nums;
                break;
            case 'char':
                $need = $chars;
                break;
            case 'chars':
                $need = $chars2;
                break;
            case 'all':
                $need = $chars3;
                break;
            default:
                $need = $chars2;
        }

        for($i = 0; $i < $length; $i++){
            $rst .= $need[mt_rand(0, strlen($need)-1)];
        }

        return $rst;
    }
}

/**
 * 文件全路径
 * @param unknown $file_path
 * @return unknown|string
 * 刘富国
 * * 2017-10-19
 */

if (!function_exists('www_path')) {
    function www_path($file_path)
    {
        if (strpos($file_path, 'http://') === 0 || strpos($file_path, 'https://') === 0)
            return $file_path;
        $domain = C('DOMAIN');
        $file_path = ltrim(str_replace('\\', '/', $file_path), '/');
        if ($file_path)
            return $domain . $file_path;
        return '';
    }
}


/**
 * 安全过滤post的json数据
 * 刘富国
 * * 2017-10-19
 */
if (!function_exists('fliter_post')) {
    function fliter_post(){
        if(!empty($_POST['json'])){
            if (get_magic_quotes_gpc()) {
                $_POST['json'] = stripslashes($_POST['json']);
            }
            $_POST = json_decode($_POST['json'], true);
            $_POST['parse_data'] = I('post.');

        }else{
            $_POST = '';
        }
    }
}


/**
 * 根据json的key值获取json数据，并组装成数组
 * @param $value
 * @param $default
 * @return array
 * 刘富国
 * * 2017-10-19
 */
if (!function_exists('get_value_by_default')) {
    function get_value_by_default($value, $default){
        if(!is_array($value)){
            $white_list = array();
            if(is_array($default)){
                $white_list = $default;
                $default = isset($default[0]) ? $default[0] : $default;
            }elseif($value == ''){
                return $default;
            }

            if(is_string($default)){
                $value = trim($value);
            }elseif(is_int($value)){
                $value = intval($value);
            }elseif(is_array($default)){
                if($value == ''){
                    return $default;
                }
                $value = (array)$value;
            }else{
                $value = floatval($value);
            }

            if($white_list && !in_array($value, $white_list)){
                $value = $default;
            }
        }else{
            foreach ($value as $key => $val){
                $t = isset($default[$key]) && $default[$key] ? $default[$key] : '';
                $value[$key] = get_value_by_default($value[$key], $t);
            }

            if(is_array($default)){
                $value += $default;
            }
        }
        return $value;
    }
}
/**
 * 根据key值获取相应的post值
 * @param string $key
 * @param string $default
 * @return
 * 刘富国
 * * 2017-10-19
 */
if (!function_exists('get_post')) {
    function get_post($key = '', $default = ''){
        if(empty($key)){
            return $_POST;
        }
        if(!isset($_POST[$key])){
            $_POST[$key] = '';
        }
        $value = get_value_by_default($_POST[$key], $default);

        return $value;
    }
}



