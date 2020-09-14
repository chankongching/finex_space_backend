<?php
namespace Back\Common;

/**
 * @author 建强  2019年3月13日 
 * @desc   生成交易对（币币交易）
 */
class EntrustType{
 
    /**
     * @method 获取交易币种对信息   币币交易
     * @param  number $currency_id 交易主币
     * @return array  LTC/USDT ....
     */
    public static function getEntrustTypeList($currency_id=8){
        try{
            $entrust_type = [];
            $where        = ['entrust_currency_id'=>$currency_id];
            $currs        = M('CanExchangeConfig')->where($where)->find();
            
            if(empty($currs)) return $entrust_type;
            $curr_ids     = explode(',', $currs['can_exchange_currencys']);
            array_push($curr_ids, $currency_id);
            $where_ids    = ['ID'=>['in',$curr_ids]];
            $curr_list    = M('Currency')->where($where_ids)
                ->field('id,currency_name')->select();
            if(empty($curr_list)) return $entrust_type;
            
            //组装交易对数据 
            $curr_list    = array_column($curr_list, 'currency_name','id');
            $main_name    = $curr_list[$currency_id];  
            foreach($curr_ids as $value){
                if($currency_id==$value) continue;
                $entrust_type[$value] = $curr_list[$value].'/'.$main_name;
            }
            ksort($entrust_type);
            return $entrust_type;
        }catch(\Think\Exception $e){
            return $entrust_type;
        }
    }
    /**
     * @method  获取完整交易对 
     * @param   int currency_id
     * @return  array LTC/USDT ....
     */
    public static function getBtcEntrustTypeList($currency_id=8){
        $entrust_type=[];
        $curr_list = M('Currency')->field('id,currency_name')->select();
        $curr_list = array_column($curr_list, 'currency_name','id');
        $curr_ids =array_keys($curr_list);
        
        $main_name = $curr_list[$currency_id];
        foreach($curr_ids as $value){
            if($currency_id==$value) continue;
            $entrust_type[$value] = $curr_list[$value].'/'.$main_name;
        }
        return $entrust_type;
    }
}