<?php
namespace Timer\Controller;
use Common\Api\RedisCluster;
use Back\Tools\SceneCode; 
/**
 * @author 建强 2018年11月14日12:28:46   
 * @desc   立即推送消息给买家  5分钟后再次推送消息给买家   (通知付款)
 */
class  UnPaidPushMsgController extends RunController
{  
    //redis 对象
    protected $redis ;
    //并非立即 定时任务执行频率
    protected $immediatelyOrder;  
    //5分钟后进行判断订单状态是否为付款
    protected $fiveMinutesOrder;   
    //全局数组 保存第二次需要推送的订单id 和订单类型
    protected $secondTimePush =  [];
    //每次队列的数据取出长度
    CONST PUSH_LENGTH         =  100; 
    //立即推送消息的订单 redis key 
    CONST TIME_ZERO_MIN_ORDER = 'TIME_ZERO_MIN_ORDER';
    //5min 后的订单redis key
    CONST TIME_FIVE_MIN_ORDER = 'TIME_FIVE_MIN_ORDER';
    
    public function __construct()
    {
        ini_set('memory_limit','256M');
        set_time_limit(0);                  
        parent::__construct();
        $this->redis = RedisCluster::getInstance();
        $currNames = M('Currency')->field('id,currency_name')->select();
        $this->CurrencyNames=array_column($currNames, 'currency_name','id');
    }
    
    
    /**
     * @导入测试数据 redis 队列key  
     * 
    */
    public function testPush()
    {
        $t1 = microtime(true);
        $ret = [];
        for($i = 1; $i <= 100000; $i++) 
        {
            $tmp =[
                'id'  =>$i,   
                'type'=> rand(1,0)?'p2p':'c2c',   
              ];
            //$ret[] = $this->redis ->rPush(self::TIME_ZERO_MIN_ORDER,json_encode($tmp));
        }
        $t2 = microtime(true);
        echo '耗时'.round($t2-$t1,4).'秒'.PHP_EOL;
        echo '消耗内存'.$this->useMemory(memory_get_usage(true));
        dump($ret);
    }
    /**
     * @author 建强  2018年11月14日12:40:50  
     * @desc   1.立即进行推送    立即推送的数据放列表   
     *         2.(立即推完后数据放redis)等待五分钟后进行再次判断 是否需要推送
     *         3.记录第一次推送的时间戳
     *         4.脚本每次最多出队列100次 最多处理100个订单推送
    */
    public function run() 
    {
        $t1     = microtime(true); 
        $orderList = [];
        $length = self::PUSH_LENGTH;
        for($i = 1;$i <=$length;$i++) 
        {
            $res= $this->redis->Lpop(self::TIME_ZERO_MIN_ORDER);
            if(empty($res)) break;
            $orderList[] = $res;
        }
        if(count($orderList)<=0) die('----------没有订单需要推送'.PHP_EOL);
        
        //处理两部分数据进行分类 c2c订单 p2p订单 并且进行数据组装
        $pendingPushArr = $this->orderTriage($orderList);
        if(empty($pendingPushArr)) die('-----数据状态在数据库已发生改变 ,无需推送');
        $pushData = [
            'server' =>'SendMsgToPersonList',
            'data'   =>[
                'send_msg_list' => $pendingPushArr
            ]
        ];
        $ret = curl_api_post($pushData);
        //二次推送 
        $this->wirteRedisForNext();
        //数据输出
        $t2 = microtime(true); 
        echo '耗时'.round($t2-$t1,4).'秒'.PHP_EOL;
        echo '消耗内存'.$this->useMemory(memory_get_usage(true));
        dump('-------推送结果'.PHP_EOL);
        dump($ret);
    }
    /**
     * @author 建强  2018年11月14日15:48:05
     * @desc 1.等待5分钟后   
     *       2.进行redis 数据获取 符合要求的进行推送  
     *       3.不符合要求的数据放到右边继续入队列
     */
    public function runAfter5Min()
    {
        $t1           = microtime(true); 
        $orderList    = $pendingJSON = [];
        $length       = self::PUSH_LENGTH;
        $time         = time();
        for($i = 1;$i <=$length;$i++)
        {
            $res  = $this->redis->Lpop(self::TIME_FIVE_MIN_ORDER);
            if(empty($res)) break; 
            $tmp  = json_decode($res,true);
            if((strtolower($tmp['type'])=='c2c' &&  $tmp['time']+300 <=$time) ||
                (strtolower($tmp['type'])=='p2p' && $tmp['time']+900 <=$time)
               )
            {
                $orderList[]=$tmp;
                continue;
            }
            //否则不符合要求继续推到队列排队(避免重复推送同一个不符合要求的数据 先推送到数组保存)  
            $pendingJSON[] = $res;
         }
         // 不符合时间要求的
         if(count($pendingJSON)>0) $this->pushJSONToRedis($pendingJSON);
         $t2 = microtime(true);
         if(count($orderList)<=0) die('-----没有符合时间要求的推送数据');
         $pendingPushArr = $this->orderTriage($orderList);
         if(empty($pendingPushArr)) die('-----数据状态在数据库已发生改变 ,无需推送');       
         $pushData = [
             'server' =>'SendMsgToPersonList',
             'data'   =>[
                 'send_msg_list' => $pendingPushArr
             ]
         ];
         $ret = curl_api_post($pushData);
         //输出
         echo '耗时'.round($t2-$t1,4).'秒<br>';
         echo '消耗内存'.$this->useMemory(memory_get_usage(true));
         dump('-------推送结果'.PHP_EOL);
         dump($ret);
    }
    //=============================如下是受保护公共方法体=====================   
    /**
     * @author 建强  2018年11月14日14:40:06 
     * @method redis 队列订单数据分类处理p2p /c2c
     * @param  array $ordersArr
     * @return array
     */
    protected function orderTriage($ordersArr)
    {
        $c2cIds = [];
        $p2pIds = [];
        //订单数据分类
        foreach($ordersArr as $value) 
        { 
            if(!is_array($value)) $value  = json_decode($value,true);
            if(strtolower($value['type']) == 'c2c') 
            {
                $c2cIds[] = $value['id'];
                continue;
            }
            $p2pIds[] = $value['id'];
        }
        //获取订单数据 组组装成待发送信息数组
        $c2cPushArr = $this->getC2COrderByIds($c2cIds);
        $p2pPushArr = $this->getP2POrderByIds($p2pIds);
        return array_merge($c2cPushArr,$p2pPushArr);
    }
    /**
     * @author 建强 2018年11月14日16:56:05
     * @method 订单数据插入对列继续下一次进行
     * @return boolean
    */
    protected function wirteRedisForNext()
    {
        if(count($this->secondTimePush)<=0) return true;
        $time = time();
        foreach($this->secondTimePush as $value)
        {
            $value['time'] = $time;
            $this->redis->rPush(self::TIME_FIVE_MIN_ORDER,json_encode($value));
        }
        return true;
    }
    /**
     * @author 建强 2018年11月14日14:54:22 
     * @method 数据库查询执行订单  c2c数据
     * @return array
    */
    protected function getC2COrderByIds($ids)
    { 
        $ids  = array_unique($ids);
        if(empty($ids)) return  [];
        $where=[
            'id' => ['IN',$ids],  
            'status' =>1   //订单状态为1  买入成功
        ];
        $field ='id,order_num_buy,currency_type,trade_price,trade_num,om,buy_id,rate_total_money';
        $orders = M('CcTrade')->field($field)->where($where)->select();
        if(empty($orders)) return [];
        $this->P2pOrder = $orders;   
        $sendList = [];
        //注意如果100个订单id 查询mysql结果集最多100 可能定时脚本延迟 买家已经付款
        foreach($orders as $key=>$value)
        {  
            $tempOrderInfo =[];
            $tempOrderInfo['orderNum']     = $value['order_num_buy'];
            $tempOrderInfo['currencyName'] = $this->CurrencyNames[$value['currency_type']];
            $tempOrderInfo['rate_total_money']        = $value['rate_total_money'];
            $tempOrderInfo['num']          = $value['trade_num'];  
            $contentStr = SceneCode::getC2CTradeTemplate(5,'+'.$value['om'],$tempOrderInfo);
            $contentArr = explode('&&&', $contentStr);
            //组装推送数据
            $sendList[$key]['uid']     = $value['buy_id']; //买家
            $sendList[$key]['title']   = $contentArr[0];
            $sendList[$key]['content'] = $contentArr[1];
            $sendList[$key]['extras']['send_modle'] = 'C2C';
            $sendList[$key]['extras']['new_order_penging'] = 1;
            
            //赋值到全局数组 5min后再次进行推送
            $nextPush = [
                'id'  =>$value['id'],
                'type'=>'c2c',
            ];   
            $this->secondTimePush[] = $nextPush;
        }
        return $sendList;
    }
    /**
     * @author 建强 2018年11月14日14:54:22
     * @method 数据库查询执行订单  p2p数据
     * @return array
     */
    protected function getP2POrderByIds($ids)
    {
        $ids  = array_unique($ids);
        if(empty($ids)) return  [];
        $where=[
            'id'=>['IN',$ids],
            'status'=>1      //订单状态为1  买入成功
        ];
        $field  = 'id,order_num,currency_id,price,num,buy_id,om,rate_total_money';
        $orders = M('TradeTheLine')->field($field)->where($where)->select();
        if(empty($orders)) return []; 
        
        $sendList =[];
        foreach($orders as $key=>$value)
        {
            $tempOrderInfo =[];
            $tempOrderInfo['orderNum']     = explode('-',$value['order_num'])[0];
            $tempOrderInfo['currencyName'] = $this->CurrencyNames[$value['currency_id']];
            $tempOrderInfo['rate_total_money']        = $value['rate_total_money']  ;
            $tempOrderInfo['num']          = $value['num'];       //数量
            $contentStr = SceneCode::getP2PTradeTemplate(5,'+'.$value['om'],$tempOrderInfo);
            $contentArr = explode('&&&', $contentStr);
            
            //组装推送数据
            $sendList[$key]['uid']     = $value['buy_id']; //买家
            $sendList[$key]['title']   = $contentArr[0];
            $sendList[$key]['content'] = $contentArr[1];
            $sendList[$key]['extras']['send_modle'] = 'P2P';
            $sendList[$key]['extras']['new_order_penging'] = 1;
            
            //赋值到全局数组 15min后再次进行推送
            $nextPush = [
                'id'  =>$value['id'],
                'type'=>'p2p',
            ];
            $this->secondTimePush[] = $nextPush;
        }
        return $sendList;
    }
    /**
     * @author 建强 2018年11月21日11:20:10 
     * @method 批量循环保存 不符合时间要求的推送json数据
     * @param  array $pendingJSON  
     * @return bool
     */
    protected function pushJSONToRedis($pendingJSON) 
    {   
        if(empty($pendingJSON)) return true;
        foreach($pendingJSON as $value) $this->redis->rPush(self::TIME_FIVE_MIN_ORDER,$value);
        return true;
    }
    /***
     * @method 转换内存占用大小
     * @param  int $size
     * @return string
     */
    protected function useMemory($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),4).' '.$unit[$i].PHP_EOL; 
    }
}