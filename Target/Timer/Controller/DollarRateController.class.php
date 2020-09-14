<?php
namespace Timer\Controller;
use Back\Tools\HttpCurl;
/**
 * @author yangpeng 2018-3-2
 * @desc   定时任务的脚本-自动获取人民币兑美元的汇率
 */
class  DollarRateController extends RunController{  
    protected $currencyRateArr=[
        'RMB_HUILV'=>'CNY',
        'HK_HUILV' =>'HKD',
        'TW_HUILV' =>'TWD',
    ];
    /**
     * @var string $url 聚合接口地址 
    */
    protected  $url = "http://op.juhe.cn/onebox/exchange/currency?";
    /**
     * @method 定时任务 请求聚合接口获取美金汇率 并写入网站配置config 
    */
    public function getRate(){
        $params = [
           'key'=>'3111a34bf503f9863eed93ff8fa400a8', //固定key
           'from'=>'USD'                              //固定美金汇率
       ];
        $data =$configUP=[];
       //获取最新的汇率
       foreach($this->currencyRateArr as $key=>$val){
           $params['to'] =$val;
           $url = $this->url.http_build_query($params);
           $res =HttpCurl::postRequest($url);
           if(empty($res)) continue;
           $res =json_decode($res,true);
           if(empty($res)  || count($res['result'])<1) continue;
           $data[$key]=$res['result'][0]['exchange'];
           //更新配置表
           $configUP[$key] =M('Config')->where(['key'=>$key])->setField('value',$res['result'][0]['exchange']);
       }
       //插入后者更新当天的汇率
       $ret = $this->addOrUpdateRate($data['RMB_HUILV']);
       //更新config 配置汇率表
       dump($ret);
       dump($configUP);
    }
    /**
     * @method 更新或者是插入最新的汇率
     * @param  int $usd_rate当天美金汇率
     * @return string
    */
    protected function addOrUpdateRate($usd_rate){
        $time =time();
        $rate = [
            'rate'=>$usd_rate,
            'add_time'=>time(),
            'rate_avg'=>$this->getRateAvg($usd_rate),
        ];
        //检测是否存在当天数据
        $time = strtotime(date('Y-m-d',time()));
        $map=[
            '_complex'=>[
                'update_time'=>array('gt',$time),
                'add_time'=>array('gt',$time),
                '_logic'=>'OR',
            ]
        ];
        $ret = M('Rate')->field("id,rate")->where($map)->order('id desc')->find();
        if($ret && $ret['rate']!=$usd_rate){
            unset($rate['add_time']);
            $rate['update_time'] = time();
            $update = M('Rate')->where(['id'=>$ret['id']])->save($rate);
            return $update ?'更新成功':'更新失败';
        }
        $insert = M('Rate')->add($rate);
        return $insert ?'添加成功':'添加失败';
    }
    
    /**
     * @method 后台ajax更新汇率数据  static 静态方法
     * @return int 美金->人民币汇率 
    */
    public static function getUsdTodayRate(){
        $params = [
            'key'=>'3111a34bf503f9863eed93ff8fa400a8', //固定key
            'from'=>'USD',                             //固定美金汇率
            'to'=>'CNY'                               //固定美金汇率
        ];
        $url ='http://op.juhe.cn/onebox/exchange/currency?'.http_build_query($params);
        $res =HttpCurl::postRequest($url);
        if(empty($res)) return 0;
        $res =json_decode($res,true);
        if(empty($res)  || count($res['result'])<1) return 0;
        return $res['result'][0]['exchange'];
    }
    /**
     * @method 查询两周的平均汇率
     * @return float 
    */
    protected function getRateAvg($usd_rate){
        //查询两周前的数据
        $time=time();
        $where =[
            'add_time'=>array('gt',strtotime('-2 weeks',$time))
        ];
        $count =M('Rate')->where($where)->count();
        if(empty($count)) return $usd_rate;
        $res = M('Rate')->field('sum(rate) as sum')->where($where)->select(); 
        return number_format($res[0]['sum']/$count,6);
    }
}