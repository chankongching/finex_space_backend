<?php
namespace Timer\Controller;
use Back\Common\EntrustType;
/**
 * @author 建强 2018年1月25日15:08:58
 * @desc   定时任务的脚本-刷单配置 目前只有 BTC交易区
 */
class BrushController extends RunController{
    /**
     * @desc 刷单数据起始时间
     * @var $time_tag bool  
     * @var $entrust_type array  LTC/BTC ...
     * @var $brush_config array  price num
     */
    protected $time_tag,$entrust_type,$brush_config; 
    
    public function __construct(){
        parent::__construct();
        $this->checkConfig();
        $this->setBrushConfig();
        $this->setTradeEntrustType();
    }
	/**
	 * @method 檢測定時任務是否開啓  
	 */
	protected  function checkConfig(){   
	    $ret = M("InterfaceConfig")->select();
		$ret = array_column($ret, 'value','key');
		if($ret['AUTO_BRUSH_ORDER']==0) die('-------网站未开启刷单程序');
		$this->time_tag=$ret['AUTO_BRUSH_TIME_IS_NOW'];
	}
	/**
	 *@method 生成币种交易对信息
	*/
	protected function setTradeEntrustType(){
	    $entrust_type = EntrustType::getEntrustTypeList();
	    if(empty($entrust_type)) die('----币币交易对未配置');
	    $this->entrust_type  = $entrust_type;
	}
	/**
	 * @method 设置刷单配置价格 数量
	 */
	protected function setBrushConfig(){
	    $config_arr = [];
	    $config     = M("BibiBrushConfig")->select();
	    if(empty($config)) die('------未配置刷单数据');
	    foreach($config as $value){
	         $price_tag = strpos($value['price'], '-');
	         $num_tag   = strpos($value['num'],'-');
	         if($price_tag==false || $num_tag==false) die('----刷单配置表价格或数量区间配置错误');
	         $price = explode('-', $value['price']);
	         $num   = explode('-', $value['num']);
	         if(max($price)<=0 || max($num)<=0) die('----刷单配置表价格或数量配置错误为0');
	         $config_arr[$value['entrust_type']]['s_price']= $price[0];
	         $config_arr[$value['entrust_type']]['e_price']= $price[1];
	         $config_arr[$value['entrust_type']]['s_num']  = $num[0];
	         $config_arr[$value['entrust_type']]['e_num']  = $num[1];
	    }
	    $this->brush_config =$config_arr;
	}
	/**
	 * @method work 定时脚本入口
	*/
    public function autoRunInsertRecords(){ 
          $this->DeleteBtcRecord();
	      //批量生成时间数据
          $time_points= $this->getTrdeTimePoints();
          //组装data数据
          $ret    = $this->addtoDB($time_points);
          dump($ret);
	}
	/**
	 * @method 生成时间点坐标 
	 * @return array Timearray
	 */
	protected function getTrdeTimePoints(){
	     $timeStamp  = time();
	     $timeArr    = [];
	     if($this->time_tag==true){
	         $resTime=M('BtcMachineBrush')->order('id desc')->getField('trade_time');
	         $timeStamp=$resTime?$resTime:$timeStamp;
	     }
	     //生成数据点时间十分钟内
	     $endTime= $timeStamp+600; 
	     for($i=$timeStamp;$i<$endTime;$i=$i+2) $timeArr[]=$i;
	     return $timeArr;
	 }
	 /**
	  * @method 组装价格数据点
	  * @param  array 
	  * @return array 
	  */
	 protected function addtoDB($timeArr){
	     $btcArr= $insert_ret = [];
	     $i     = 0;
	     //$en_type 为 entrust_type
	     foreach($this->brush_config as $en_type=>$value){
	         foreach($timeArr as $vv){
	             $btcArr[$i]['entrust_type']= $en_type;
	             $btcArr[$i]['trade_price'] = rand($value['s_price'],$value['e_price']);
	             $btcArr[$i]['trade_num']   = rand($value['s_num'],$value['e_num']);
	             $btcArr[$i]['trade_money'] = $btcArr[$i]['trade_price']* $btcArr[$i]['trade_money'];
	             $btcArr[$i]['trade_time']  = $vv;
	             $uid                       = (rand(1,10)>5)?'sell_id':'buy_id';
	             $btcArr[$i][$uid]          = 1;
	             $i++;
	         }
	         $insert_ret[] = M('BtcMachineBrush')->addAll($btcArr);
	         $btcArr=[];
	     }
	     return $insert_ret;
	}
   /**
    * @method 防止表中的数据太多进行删除 超过一定阈值进行删除
    * @return bool 
	*/
	protected function DeleteBtcRecord(){
	    $rand      = rand(1,10);
	    if($rand>5) return true;
	    $keepRows  = 10000;
	    $rows      = 500000;
	    $count     = M('BtcMachineBrush')->count();
	    if($count<$rows) return true;
	    //删除记录
	    $maxId=M('BtcMachineBrush')->order('id desc')->getField('id');
	    $where=['id'=>['ELT',$maxId-$keepRows]];
	    return M('BtcMachineBrush')->where($where)->delete();
	}	
}