<?php
namespace Common\Api;
/**
 * @author 宋建强 2017年10月13日
 * sesssion 存储机制修改  -sesion用string的储存在redis
 * 单例模式
 */
class RedisIndex{
	//避免纯数字key 加上前缀
    protected $prefix="str_"  ;  
    //垃圾回收机制的时间
    const SECOND=14400; //4h

    private static $_instance = null;
	 
	private function __construct(){}
	private function __clone(){}
	
	/**
	 * @method 修改session 操作类   2018年6月25日15:03:17
	 */
	public static function getInstance()
	{
	    if(!self::$_instance)
	    {
	        self::$_instance = new self ();
	        $redis  = RedisCluster::getInstance();
	        $handler = new  Handler($redis,array('gc_maxlifetime' => self::SECOND)); //注册redis句柄的配置
	        $handler->register();     //注册seesion存储的机制句柄
	        session_start();           //手动开启session
	    }
	    return self::$_instance;
	}
	
	/**
	 * 读取session 数据
	 * @param key  
	 * return mix
	 */
	 public function getSessionValue($key)
	 {    
	 	 $key=$this->prefix.$key;
	 	 return $_SESSION[$key]?$_SESSION[$key]:NULL;
	 }
	 /**
	     * 设置session 数据
	  * @param key 
	  * @param value  
	     * 无返回值判断  注意调用实参
	  */
	  public function setSessionRedis($key, $value)
	  {  
	  	 if($key!='' && $value!='')
	  	 {   
	  	 	$key=$this->prefix.$key;
	  	 	$_SESSION[$key]=$value;
	  	 }
	  }
	 
	  /**
	   * 删除seesion的值
	   * @param $key
	   */
	   public function delSessionRedis($key)
	   {
	   	    if($key!='')
	   	    {   
	   	    	$key=$this->prefix.$key;
	   	    	//unset($_SESSION[$key]); 2018年12月13日 处理空值写入问题  
	   	    	$_SESSION[$key]='';
	   	    }
	   }
	   
}