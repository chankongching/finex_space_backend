<?php

namespace Back\Controller;

use Common\Api\RedisCluster;
use Think\Controller;
use Common\Api\RedisIndex;
use Back\Tools\Utils;
use Back\Sms\Yunclode;

/**
 * @method 后台登录控制器  继承基类控制器
 *
 * $obj_redis=RedisIndex::getInstance();
 * $back_userinfo=$obj_redis->getSessionValue('user');
 *  $redis->setSessionRedis('user',$session_data);
 * @author
 */
class KlineController extends Controller
{
    private $rate; ////每日涨幅
    private $nowprice;////现在的价格

    /**
     * 初始化方法
     */
    public function _initialize()
    {
        parent::_initialize();

        $this->rate = M('PetcLv')->where(array('id' => 1))->getField('petc_lv');
        $this->nowprice = M('IcoIssue')->where(array('id' => 1))->getField('rate');
    }


    /*
     * 1分钟K线图生成
     */
    public function oneMin()
    {
        $new_24h_low = 0;
        $new_24h_high = 0;//当前24小时最高价
        $now_vom = 0;//当前1分钟成交量
        $now_rate = 0;//当前//涨幅
        $sjs = 0;

        //取上一分钟的数据
        $last_min = M('PetcKline')->where(array('type' => 1))->order('add_time desc')->find();
        //昨日收盘价
        $yesterday = M('PetcKline')->where(array('type' => 7))->order('add_time desc')->find();

        // 最大涨跌幅
        if ($this->rate >= 0) {
            $Increase = $this->rate * $yesterday["shoupan_price"] + $yesterday["shoupan_price"];
            $Decrease = -0.05 * $yesterday["shoupan_price"] + $yesterday["shoupan_price"];
        } else {
            $Decrease = $this->rate * $yesterday["shoupan_price"] + $yesterday["shoupan_price"];
            $Increase = ($this->rate * 0.5) * $yesterday["shoupan_price"];
        }


        // 价格波动
        $sjs = $this->randomFloat(1, 6, 10000);

        if ($this->rate < 0) {
            $aa = -1;
        } else {
            $aa = 1;
        }

        $format = rand(1, 3);
        $in_de = rand(1, 10);
        if ($format == 1) {
            if ($in_de > 3) {
                $new_price = $last_min['last'] * (1 + $sjs * $aa);
            } else {
                $new_price = $last_min['last'] * (1 - $sjs * $aa);
            }
        } elseif ($format == 2) {
            if ($in_de > 4) {
                $new_price = $last_min['last'] * (1 + $sjs * $aa);
            } else {
                $new_price = $last_min['last'] * (1 - $sjs * $aa);
            }
        } else {
            if ($in_de > 6) {
                $new_price = $last_min['last'] * (1 + $sjs * $aa);
            } else {
                $new_price = $last_min['last'] * (1 - $sjs * $aa);
            }
        }

        $now_vom = rand(50, 80);//当前1分钟成交量
        ///过了晚上12点一点点
//        $H = date('H', $last_min['add_time']);
//        $I = date('i', $last_min['add_time']);


        if ($last_min["add_time"] >= $yesterday["add_time"]+86400 && $last_min["add_time"] <= $yesterday["add_time"]+86400+120){
            $new_24h_low = $new_price;////24小时最高价
            $new_24h_high = $new_price;////24小时最低价
            $volume_24h = $now_vom;////24小时成交量
        }else{
            ///当前24小时最低价
            $new_24h_low = min($last_min['low_24h'], $new_price);
            ///当前24小时最高价
            $new_24h_high = max($last_min['high_24h'], $new_price);
            $volume_24h = $last_min['volume_24h'] + $now_vom;
        }
       // if (($H == 24) && ($I > 1) && ($I < 2)) {



        //判断当天最后三十分钟必须在涨幅率之间 相差5%
        $star_time = strtotime(date("Y-m-d 23:30:00"));
        $end_time = strtotime(date("Y-m-d 23:59:00"));
        if ($star_time < time() && $end_time > time()) {
            //如果收盘价小于昨天收盘价+涨幅率的一半，收盘价上涨
            if ($this->rate > 0 && $new_price < ($this->rate * .5) * $yesterday["shoupan_price"] + $yesterday["shoupan_price"]) {
                $new_price = $last_min['last'] * (1 + $sjs);
            }
            if ($this->rate < 0 && $new_price > ($this->rate * .5) * $yesterday["shoupan_price"] + $yesterday["shoupan_price"]) {
                $new_price = $last_min['last'] * (1 - $sjs);
            }
        }

        //如果涨的收盘大于15%
//        if ($this->rate > 0 && $new_price > $Increase*(1+$this->randomFloat(1, 4, 10000))) {
//            $new_price = $last_min['last'] * (1 - $sjs);
//        }
//        if ($this->rate < 0 && $new_price < $Decrease*(1-$this->randomFloat(1, 4, 10000))) {
//            $new_price = $last_min['last'] * (1 + $sjs);
//        }

        $inser_data['high'] = $new_price * (1 + $this->randomFloat(1, 4, 10000));////当前类型时间段内  最高价
        $inser_data['low'] = $last_min['last'] * (1 - $this->randomFloat(1, 3, 10000));////当前类型时间段内  最低价
        $inser_data['kaipan_price'] = $last_min['shoupan_price'];////开盘价 === 上一个的收盘价
        $inser_data['shoupan_price'] = $new_price;////收盘价--最新成交价
        $inser_data['high_24h'] = $new_24h_high;////24小时最高价
        $inser_data['low_24h'] = $new_24h_low;////24小时最低价
        $inser_data['volume_24h'] = $volume_24h;////24小时成交量
        $inser_data['vom_now'] = $now_vom;
        $inser_data['rate'] = ($new_price - $last_min['last']) / $last_min['last'];////涨幅
        $inser_data['type'] = 1;////1. 1分钟  2. 5分钟  3. 15分钟  4. 30分钟  5. 1小时 6. 4小时 7-1天 8.-1周
        $inser_data['add_time'] = $last_min['add_time'] + 60;////添加时间
        $inser_data['last'] = $new_price;////添加时间
        ////跌的情况
        M('PetcKline')->add($inser_data);
    }

    /*
     * PHP生成随机小数
     */
    function randomFloat($min = 0, $max = 1, $times)
    {
        $num = big_digital_div(($min + mt_rand() / mt_getrandmax() * ($max - $min)), $times, 6);
        return $num;
    }


    // 其余数据计算
    protected function addData($flag)
    {
        $time_type = array(
            '5min' => 2,
            '15min' => 3,
            '30min' => 4,
            '1hour' => 5,
            '4hour' => 6,
            '1day' => 7,
            '1week' => 8
        );
        $time_range = array(
            '5min' => 5,
            '15min' => 15,
            '30min' => 30,
            '1hour' => 60,
            '4hour' => 240,
            '1day' => 1440,
            '1week' => 10080,
        );
        //取上一分钟的数据
        $model = M('PetcKline');
        $last_min = $model->where(array('type' => 1))->order('add_time desc')->find();
        // 获取最新成交价
        $insert_data['last'] = $last_min['last'];
        // 获取收盘价
        $insert_data['shoupan_price'] = $last_min['shoupan_price'];
        // 获取开盘价
        $insert_data['kaipan_price'] = $model->where(array('type' => $time_type[$flag]))->order('add_time desc')->getField('shoupan_price');
        // 获取本类型最低价
        $lowHigh = $model->where(array('type' => 1))->order('add_time desc')->limit($time_range[$flag])->field('low,high')->select();
        $lowHighValue = $this->getLowHigh($lowHigh);
        //dump($lowHigh);die;
        $insert_data['low'] = $lowHighValue['low'];
        // 获取本类型的最高价
        $insert_data['high'] = $lowHighValue['high'];
        // 获取24小时最高价
        $insert_data['low_24h'] = $last_min['low_24h'];
        // 获取24小时最低价
        $insert_data['high_24h'] = $last_min['high_24h'];
        // 获取24小时成交量
        $insert_data['volume_24h'] = $last_min['volume_24h'];
        // 获取本类型成交量
        $insert_data['vom_now'] = rand(50 * $time_range[$flag], 80 * $time_range[$flag]);
        // 涨幅
        $last = $model->where(array('type' => $time_type[$flag]))->order('add_time desc')->getField('last');
        $insert_data['rate'] = ($insert_data['last'] - $last) / $last;
        // 类型
        $insert_data['type'] = $time_type[$flag];
        // 添加时间
        $insert_data['add_time'] = $model->where(array('type' => $time_type[$flag]))->order('add_time desc')->getField('add_time') + $time_range[$flag] * 60;

        M('PetcKline')->add($insert_data);

    }

    /*
     * 从数组中取当前最小值和最大值
     */
    protected function getLowHigh($arr)
    {
        $low = $arr[0]['low'];
        $high = $arr[0]['high'];
        foreach ($arr as &$i) {
            $low = min($low, $i['low']);
            $high = max($high, $i['high']);
        }

        $out['low'] = $low;
        $out['high'] = $high;
        return $out;
    }

    /*
     * 5分钟k线图
     */
    public function fiveMin()
    {
        $this->addData('5min');
    }

    /*
     * 15分钟k线图
     */
    public function fifteenMin()
    {
        $this->addData('15min');
    }

    /*
    * 30分钟k线图
    */
    public function thirtyMin()
    {
        $this->addData('30min');
    }

    /*
    * 一小时k线图
    */
    public function oneHour()
    {
        $this->addData('1hour');
    }

    /*
   * 四小时k线图
   */
    public function fourHour()
    {
        $this->addData('4hour');
    }

    /*
   * 一天k线图
   */
    public function oneDay()
    {
        $this->addData('1day');
    }

    /*
   * 一周k线图
   */
    public function oneWeek()
    {
        $this->addData('1week');
    }
}
