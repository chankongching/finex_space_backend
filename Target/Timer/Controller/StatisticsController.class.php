<?php
namespace Timer\Controller;
/**
 * @author 建强 2018年1月25日15:08:58
 * @method 定时任务的脚本-数据统计 
 */
class  StatisticsController extends RunController
{
   protected $currencys; 
   public function  __construct(){  
       parent::__construct();
       $this->setCurrencys();
   }
   /**
    * @method 设置币种信息
    */
   protected function setCurrencys(){
       $where    = ['status'=>1];
       $currencys= M('Currency')->where($where)
            ->field('id,currency_name')->select();
       if(empty($currencys)) die('-----没有上线币种，无需数据统计');
       $this->currencys = $currencys;
   }
   /** 
     * @method 建强   P2P每天交易数据统计
    */
    public function runData(){
        $list =[];
        $list = $this->getTradeList();
        if(empty($list)){
           die('---无交易数据');
        }
        $ret = M('DataStatistic')->addAll($list);
        p($list);
        dump("-----p2p统计执行: ".$ret);
    }
    /**
     * @method C2C每天交易数据统计  
     */
    public function runDataC2C(){
        $list  = [];
        $type  = 2 ;  //c2c模式
        $field ='currency_type as currency_id,trade_num as num,trade_price as price';
        $table ='CcTrade';
        $list  = $this->getTradeList($type,$field,$table);
        if(empty($list)){
            die('---无交易数据');
        }
        $ret = M('DataStatistic')->addAll($list);
        p($list);
        dump("-----c2c统计执行: ".$ret);
    }
    /**
     * @method 获取成交交易数据
     * @param  string $field
     * @param  string $table 
     * @return array 
     */
    protected function getTradeList($type=1,$field='currency_id,num,price',$tableName='TradeTheLine'){
        $currency_list = $curr_tmp=[]; 
        $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
        $endYesterday  =mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
        $where =[
            'status'=>3,
            'end_time'=>['between',
            [$beginYesterday,$endYesterday]],
        ];
        $list = M($tableName)->where($where)
             ->field($field)->select();
        if(empty($list)){
            return $currency_list;
        }
        //组装数据 
        $curr_ids     = array_column($this->currencys,'id');
        $list_curr_id = array_unique(array_column($list,'currency_id'));
        $no_curr_ids  = array_diff($curr_ids,$list_curr_id);
        foreach($no_curr_ids as $value){
            $curr_info = [
                'currency_id'=>$value,'date'=>date('Y-m-d',$endYesterday),
                'add_time'=>date('Y-m-d H:i:s'),
                'high'=>0,'low'=>0,
                'open'=>0,'close'=>0,
                'num'=>0,'money'=>0,'type'=>$type //交易类型p2p c2c
            ];
            $currency_list[$value]=$curr_info;
        }
        //有交易数据
        foreach($list_curr_id as $value){
            foreach($list as $vv){
                $curr_tmp[$vv['currency_id']]['price'][]  = $vv['price'];
                $curr_tmp[$vv['currency_id']]['num'][]    = $vv['num'];
                $curr_tmp[$vv['currency_id']]['money'][]  = $vv['price']*$vv['num'];
            }
            $curr_info = $this->getOlhcTrade($type,$value,$curr_tmp[$value],$endYesterday);
            $currency_list[$value] = $curr_info;
        }
        //返回值
        return array_values($currency_list);
    }   
    /**
     * @method 获取币种OLHC币种价格数据
     * @param  int   $type
     * @param  int   $currency_id
     * @param  array $currs 
     * @param  int   $endYesterday
     * @return array 
    */
    protected function getOlhcTrade($type,$currency_id,$currs,$endYesterday){
        $prices = $orginal_price = $currs['price'];
        $nums   = $currs['num'];
        $moneys = $currs['money'];
        $length = count($prices);
        if($length<3){
            return [
                'currency_id'=>$currency_id,'date'=>date('Y-m-d',$endYesterday),
                'add_time'=>date('Y-m-d H:i:s'),
                'high'    =>max($prices),'low'=>min($prices),
                'open'    =>$prices[0],'close'=>$prices[$length-1],
                'num'     =>array_sum($nums),'money'=>array_sum($moneys),
                'type'=>$type            //交易类型p2p c2c
            ];
        }
        //踢掉最高价最低价
        sort($prices); 
        unset($prices[0]);unset($prices[$length-1]);
        $prices = array_values($prices);
        return [
            'currency_id'=>$currency_id,'date'=>date('Y-m-d',$endYesterday),
            'add_time'   =>date('Y-m-d H:i:s'),
            'high'       =>max($prices),'low'=>min($prices),
            'open'       =>$orginal_price[0],'close'=>$orginal_price[$length-1],
            'num'        =>array_sum($nums),'money'=>array_sum($moneys),
            'type'=>$type            //交易类型p2p c2c
        ];
    }
}