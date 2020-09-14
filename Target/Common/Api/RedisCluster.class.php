<?php
/**
 * @desc redis集群驱动
 * @author 建强 
 */
namespace Common\Api;
use Think\Cache\Driver\Redis;

class RedisCluster{
    
    //节点配置
    protected $servers=array(
        '127.0.0.1:7006',
        '127.0.0.1:7001',
        '127.0.0.1:7002',
        '127.0.0.1:7003',
        '127.0.0.1:7004',
        '127.0.0.1:7005',
    );
    //配置参数
    private static $_instance = null;
    private $handler;
    
    private function __construct($servers=array(),$optionParam=array()){
        if(!empty($servers) && is_array($servers)){
            $this->servers=$servers;
        }
        
        if(!empty($optionParam) && is_array($optionParam)){
            $this->optionParam=$optionParam;
        }
        if(!$this->handler){
            $this->handler = new Redis();
//            $this->handler = new \RedisCluster(null,$this->servers,
//                $this->optionParam['timeOut'],
//                $this->optionParam['readTime'],
//                $this->optionParam['persistent']
//                );
//            $this->handler->setOption(
//                \RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_DISTRIBUTE_SLAVES
//                );
        }
    }
    /**
     * @method 获取predis对象
     * @author 建强  2018年6月25日14:50:36
     * @return object
     */
    public static function getInstance($servers=array(),$optionParam=array()){
        
        if(!(self::$_instance instanceof self)) {
            self::$_instance = new self($servers=array(),$optionParam=array());
        }
        return self::$_instance->handler;
    }
}
