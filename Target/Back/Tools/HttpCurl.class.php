<?php
namespace Back\Tools;
/**前台工具类- curl请求
 * @author 宋建强 2017年9月26日 14:54
 */
class HttpCurl
{
    /**
     * @param string $url        post所要提交的网址
     * @param array  $data       所要提交的数据
     * @param integer $expire    所用的时间限制
     * @return string
     */
    public static function postRequest($url, $data = null, $charset= 'UTF-8', $type = 'array', $expire = 10)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $expire);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
    
    /**
     * 发送GET请求
     * @param string $url 请求地址
     * @param array $post_data post键值对数据
     * @return string
     */
    public static function HttpGet($url, $data='') {
    	$options = array(
    			'http' => array(
    					'method' => 'GET',
    					'timeout' => 8   // 超时时间（单位:s）
    			)
    	);
    	
        //携带参数进行请求
    	if ($data!='')    
        {
        	$string = http_build_query($data);
        	$options['header']='Content-type:application/x-www-form-urlencoded';
        	$options['content']=$string;
        }
        
    	$context = stream_context_create($options);
    	$result  = file_get_contents($url, false, $context);
    	return $result;
    }
}