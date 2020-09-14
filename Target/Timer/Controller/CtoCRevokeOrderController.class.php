<?php
namespace Timer\Controller;
use Back\Common\CCBreakOrder;
use Think\Controller;
use Back\Tools\SceneCode;
/**
 * @author liruniqng 2018年3月1日16:48:30
 * @method 定时任务的脚本  自动撤销订单 
 *
 */
class  CtoCRevokeOrderController extends RunController{

    private $overTime = 48;// 超时48小时
    private $configCurrency=null;
    
     public function __construct()
     { 
     	  parent::__construct();
     	  $res=M('CcConfig')->select();
     	  $this->configCurrency=array_column($res,'min_trade_money','currency_id');
     	  
     }
    /**
     * 定时任务执行
     * @author lirunqing 2018-03-06T16:57:04+0800
     * @return [type] [description]
     */
	public function run(){
        // 超出48h撤销买入主订单
		// $this->revokeOrderBy48hFromBuy();
        // 超出48h撤销卖出主订单
        // $this->revokeOrderBy48hFromSell();
        // 撤销超时未打款的订单
        $this->revokeChildOrderFromOverTime();
	}

    /**
     * 超时未打款的订单撤销
     * @author lirunqing 2018-03-06T16:56:47+0800
     * @return bool|string
     */
    public function revokeChildOrderFromOverTime(){
        $time  = time() - 15 * 60;// 超时15分钟
        $where = array(
            'trade_time' => array('lt', $time),
            'status'   => 1,
        );
        $res = M('CcTrade')->where($where)->limit(30)->select();

        
        if (empty($res)) {
            echo "暂无超时子订单1";
            echo "<br/>";
            return false;
        }

        $idArr       = array();
        $pidArr      = array();
        $orderList   = array();
        $parentIdArr = array();
        foreach ($res as $value) {
            $idArr[]                 = $value['id'];
            $temp['pid']             = $value['pid'];
            $temp['id']              = $value['id'];
            $temp['currency_type']   = $value['currency_type'];
            $temp['sell_id']         = $value['sell_id'];
            $temp['buy_id']          = $value['buy_id'];
            $temp['sell_fee']        = $value['sell_fee'];
            $temp['type']            = $value['type'];
            $temp['trade_num']       = $value['trade_num'];
            $temp['order_num']       = $value['order_num'];
            $temp['trade_price']     = $value['trade_price'];
            $temp['rate_total_money']     = $value['rate_total_money'];
            $orderList[$value['id']] = $temp;
            $parentIdArr[]           = $value['pid'];

            // 子单是卖出，主订单则是买入
            if ($value['type'] == 2) { 
                $pidArr[] = $value['pid'];
            }
        }

        // 判断是否有主订单需要扣除保证金,0表示没有扣保证金，1表示已经扣了保证金
        $parentList = array();
        if (!empty($pidArr)) {
            $pidArr   = array_unique($pidArr);
            $pidWhere = array(
                'id'       => array('in', $pidArr),
                'is_break' => 0,
            );
            $parentArr = M('CcOrder')->where($pidWhere)->select();
            foreach ($parentArr as $key => $value) {
                $parentList[] = $value['id'];
            }
        }

        //开启事务
        M()->startTrans();

        $upArr = array(
            'status'      => 4,
            'update_time' => time()
        );
        $upWhere = array(
            'id' => array('in', $idArr),
        );
        $idCpunt = count($idArr);
        $upRes = M('CcTrade')->where($upWhere)->save($upArr);

        if (empty($upRes) || $upRes != $idCpunt) {
            M()->rollback(); // 事务回退
            echo "撤销子订单失败1";
            echo "<br/>";
            return false;
        }

        // 子订单违规，主订单要扣除保证金
        if (!empty($parentList)) {
            $pArr = array(
                'is_break' => 2
            );
            $pWhere = array(
                'id' => array('in', $parentList),
            );
            $parentListCount = count($parentList);
            $pRes = M('CcOrder')->where($pWhere)->save($pArr);
            if (empty($pRes) || $parentListCount != $pRes) {
                M()->rollback(); // 事务回退
                echo "撤销子订单失败2";
                echo "<br/>";
                return false;
            }
        }

        $logRes  = array();
        $showArr = array();
        foreach ($orderList as $key => $value) {

            // 超时未打款3+1惩罚
            CCBreakOrder::addBreakTimeNum($value['buy_id']);

            $extArr['financeType'] = 34;
            $extArr['content']     = 'C2C挂单返还币(系统)';
            $extArr['type']        = 1;
            $extArr['money']       = $value['trade_num'];
            $extArr['remarkInfo']  = $value['order_num'];
            $logRes[] = $this->setUserMoneyAndAddLog($value['sell_id'], $value['currency_type'], $extArr);

            $showTemp['id']  = '修改id'.$value['id'];
            $showTemp['uid'] = '退回用户id'.$value['sell_id'].'--财务表'.$value['sell_id']%4;
            $showTemp['num'] = '返回币:'.$value['trade_num'];

            // 如果有手续费则退手续费
            if ($value['sell_fee'] > 0) {

                $showTemp['sell_fee']     = '手续费:'.$value['sell_fee'];
                $showTemp['sell_fee_str'] = '退回用户id:'.$value['sell_id'].'--财务表'.$value['sell_id']%4;
                $feeArr['financeType']    = 35;
                $feeArr['content']        = 'C2C挂单返还手续费(系统)';
                $feeArr['type']           = 1;
                $feeArr['money']          = $value['sell_fee'];
                $feeArr['remarkInfo']     = $value['order_num'];
                $logRes[]                 = $this->setUserMoneyAndAddLog($value['sell_id'], $value['currency_type'], $feeArr);
            }

            $showArr[] = $showTemp;
        }

        if (in_array(false, $logRes)) {
            M()->rollback(); // 事务回退
            echo "撤销子订单失败3";
            echo "<br/>";
            return false;
        }

        M()->commit(); // 事务回退

        // 极光推送信息到APP
        $currArr = $this->getCurrencyList();
        $this->pushAppInfo($orderList, $parentIdArr, $currArr);

        echo "撤销子订单成功";
        echo "<Pre>";
        var_dump($showArr);
        echo "<br/>";
        return true;
    }

    /**
     * 获取币种信息
     * @author 2018-03-29T21:18:49+0800
     * @return [type] [description]
     */
    protected function getCurrencyList(){
        $currList = M('Currency')->select();

        $currArr = array();
        foreach ($currList as $key => $value) {
            $currArr[$value['id']] = $value;
        }

        return $currArr;
    }

    /**
     * 极光推送信息到APP
     * @author liruqing 2018-03-29T21:23:17+0800
     * @param  [type] $orderList [description]
     * @param  [type] $parentIdArr   [description]
     * @param  [type] $currArr   [description]
     * @return [type]            [description]
     */
    protected function pushAppInfo($orderList, $parentIdArr, $currArr){

        $orderWhere = ['id' => ['in', $parentIdArr]];
        $ccOrderList  = M('CcOrder')->field('id,om')->where($orderWhere)->select();

        $orderTempArr = array();
        foreach ($ccOrderList as $value) {
            $orderTempArr[$value['id']] = '+'.$value['om'];
        }

        $extras['send_modle']        = 'C2C';
        $extras['new_order_penging'] = '1';

        $pushArr = array();        
        foreach ($orderList as $key => $value) {

            $orderInfo = [
                'orderNum'     => $value['order_num'],
                'currencyName' => $currArr[$value['currency_type']]['currency_name'],
                'rate_total_money'        => $value['rate_total_money'],
                'num'          => $value['trade_num'],
                'total'        => $value['trade_money'],
            ];

            $contentStr      = SceneCode::getC2CTradeTemplate(2, $orderTempArr[$value['pid']], $orderInfo);
            $contentArr      = explode('&&&', $contentStr);
            $title           = $contentArr[0];
            $content         = $contentArr[1];
            $temp['uid']     = $value['sell_id'];
            $temp['title']   = $title;
            $temp['content'] = $content;
            $temp['extras']  = $extras;
            $pushArr[]       = $temp;
        }
        $postData['server']                = 'SendMsgToPersonList'; //接口名称
        $postData['data']['send_msg_list'] = $pushArr;
        $rs                                = curl_api_post($postData);
    }
     
    /**
     * @param unknown $order_id
     */
    public function checkCanRevokTradeOrder($order_id,$is_break)
    {  
    	  if($is_break!=0)
    	  {
    	  	 return false;
    	  }
    	  $whereTradeOrder=[
    	  	 "pid"=>$order_id,    	  	
    	  	 "status"=>['in',[1,2,5]]	
    	  ];
    	  $count=M('CcTrade')->where($whereTradeOrder)->count();
    	  if($count>0)
    	  {
    	  	 return false;
    	  }
    	  return true;
    }
    
    /**
     * 48h超时撤销卖出订单
     * @author lirunqing 2018-03-06T12:01:46+0800
     * @return bool
     */
    public function revokeOrderBy48hFromSell(){
        $time  = time() - $this->overTime * 3600;
        $where = array(
            'add_time' => array('lt', $time),
            'status'   => 1,
            'type'     => 2
        );
        $res = M('CcOrder')->where($where)->limit(30)->select();

        if (empty($res)) {
            echo "暂无超时卖出订单1";
            echo "<br/>";
            return false;
        }

        $idArr       = array();
        $leaveFeeArr = array();
        foreach ($res as $value) {
            $idArr[]                   = $value['id'];
            $temp['leave_fee']         = $value['leave_fee'];
            $temp['leave_num']         = $value['leave_num'];
            $temp['type']              = $value['type'];
            $temp['price']             = $value['price'];
            $temp['num']               = $value['num'];
            $temp['uid']               = $value['uid'];
            $temp['om']                = $value['om'];
            $temp['currency_type']     = $value['currency_type'];
            $temp['order_num']         = $value['order_num'];
            $leaveFeeArr[$value['id']] = $temp;
        }

        //开启事务
        M()->startTrans();

        $upArr = array(
            'status'      => 4,
            'update_time' => time()
        );
        $upWhere = array(
            'id' => array('in', $idArr),
        );
        $idArrCount = count($idArr);
        $upRes = M('CcOrder')->where($upWhere)->save($upArr);

        if (empty($upRes) || $idArrCount != $upRes) {
            M()->rollback(); // 事务回退
            echo "撤销卖出订单失败1";
            echo "<br/>";
            return false;
        }

        $logRes = array();
        $showArr = array();
        foreach ($leaveFeeArr as $key => $value) {

            $extArr['financeType'] = 34;
            $extArr['content']     = 'C2C挂单返还币(系统)';
            $extArr['type']        = 1;
            $extArr['money']       = $value['leave_num'];
            $extArr['remarkInfo']  = $value['order_num'];
            $logRes[] = $this->setUserMoneyAndAddLog($value['uid'], $value['currency_type'], $extArr);

            $showTemp['id']  = '修改id'.$value['id'];
            $showTemp['uid'] = '退回用户id'.$value['uid'].'--财务表'.$value['uid']%4;
            $showTemp['num'] = '返回币:'.$value['leave_num'];

            // 如果手续费小于等于0，则不退手续费
            if ($value['leave_fee'] <= 0) {
                continue;
            }else{
                $showTemp['sell_fee']     = '手续费:'.$value['leave_fee'];
                $showTemp['sell_fee_str'] = '退回用户id:'.$value['uid'].'--财务表'.$value['uid']%4;

                $bondArr['financeType'] = 35;
                $bondArr['content']     = 'C2C挂单返还手续费(系统)';
                $bondArr['type']        = 1;
                $bondArr['money']       = $value['leave_fee'];
                $bondArr['remarkInfo']  = $value['order_num'];
                $logRes[] = $this->setUserMoneyAndAddLog($value['uid'], $value['currency_type'], $bondArr);
            }

          $showArr[] = $showTemp;
        }

        if (in_array(false, $logRes)) {
            M()->rollback(); // 事务回退
            echo "撤销卖出订单失败2";
            echo "<br/>";
            return false;
        }

        // 推送信息到APP
        $currArr = $this->getCurrencyList();
        $this->pushAppInfoByRevoke($leaveFeeArr, $currArr, 6);

        M()->commit(); // 事务回退
        echo "撤销卖出订单成功";
        echo "<Pre>";
        var_dump($showArr);
        echo "<br/>";
        return true;
    }

    /**
     * 挂单48H撤销推送信息到用户APP
     * @author lirunqing 2018-04-09T14:32:09+0800
     * @param  array  $orderList 订单信息列表
     * @param  array  $currArr   币种列表
     * @param  integer $type     推送信息类型
     * @return null
     * todo 这方法已经废弃，如果要恢复使用，getC2CTradeTemplate这模板不能用，
     * todo 因为这里面的价格用的是子单的参考总价：rate_total_money ， 需要改 ，刘富国 2019-05-23
     */

    private function pushAppInfoByRevoke($orderList, $currArr, $type=6){

        $pushArr = array();        
        foreach ($orderList as $key => $value) {

            // 买入退还保证金，卖出退还剩余数量
            $num = ($value['type'] == 1) ? $value['bond_num'] : $value['leave_num'];

            if ($value['type'] == 1 && $value['is_return_bond_num'] == 1) {
                $type = 10;
            }else if ($value['type'] == 1 && $value['is_return_bond_num'] == 0) {
                $type = 7;
            }

            $orderInfo = [
                'orderNum'     => $value['order_num'],
                'currencyName' => $currArr[$value['currency_type']]['currency_name'],
                'price'        => $value['price'],
                'num'          => $num,
            ];
            $om = '+'.$value['om'];
            $contentStr      = SceneCode::getC2CTradeTemplate($type, $om, $orderInfo);
            $contentArr      = explode('&&&', $contentStr);
            $title           = $contentArr[0];
            $content         = $contentArr[1];
            $temp['uid']     = $value['uid'];
            $temp['title']   = $title;
            $temp['content'] = $content;
            $pushArr[]       = $temp;
        }
        $postData['server']                = 'SendMsgToPersonList'; //接口名称
        $postData['data']['send_msg_list'] = $pushArr;
        $rs                                = curl_api_post($postData);
    }

	/**
	 * 超出48h撤销买入主订单
	 * @author 2018-03-01T16:53:55+0800
	 * @return bool
	 */
	public function revokeOrderBy48hFromBuy(){

		$time = time() - $this->overTime * 3600;
	  
		$where = array(
			'add_time' => array('lt', $time),
			'status'   => 1,
			'type'     => 1
		);
		
		$res = M('CcOrder')->where($where)->limit(30)->select();
		if (empty($res)) {
			echo "暂无超时买入订单1";
			echo "<br/>";
			return false;
		}

		$idArr      = array();
		$bondNumArr = array();
        $userArr    = array();
		foreach ($res as $value) {
            $idArr[]                  = $value['id'];
            $temp['bond_num']         = $value['bond_num'];
            $temp['num']              = $value['num'];
            $temp['type']             = $value['type'];
            $temp['price']            = $value['price'];
            $temp['is_break']         = $value['is_break'];
            $temp['uid']              = $value['uid'];
            $temp['om']               = $value['om'];
            $temp['id']               = $value['id'];
            $temp['currency_type']    = $value['currency_type'];
            $temp['order_num']        = $value['order_num'];
            $bondNumArr[$value['id']] = $temp;
            $userArr[]                = $value['uid'];
		}

		if (empty($idArr) || empty($bondNumArr)) {
			echo "暂无超时买入订单2";
			echo "<br/>";
			return false;
		}

        // 获取主订单是否有已经全部完成子单或者没有子单，判断是否需要退还保证金
        $childWhere = array(
            'pid' => array('in', $idArr)
        );

        $childRes = M('CcTrade')->field('pid,status')->where($childWhere)->select();
        $childTemp = array();
        foreach ($childRes as $key => $value) {
            $childTemp[$value['pid']][] = $value['status'];
        }

        $childArr = array();
        foreach ($childTemp as $key => $status_value) {
            foreach ($status_value as $value) {
                $childArr[$key]['is_bond_num'] = 0;
                // 有未完成的子订单，则不退还保证金，等待子订单完成才退还
                if (in_array($value, array(1,2,5))) {
                    $childArr[$key]['is_bond_num'] = 1;
                }
            }
        }

		//开启事务
		M()->startTrans();

		$upArr = array(
			'status'      => 4,
            'update_time' => time()
		);
		$upWhere = array(
			'id' => array('in', $idArr),
		);
        $idArrCount = count($idArr);
		$upRes = M('CcOrder')->where($upWhere)->save($upArr);

		if (empty($upRes) || $idArrCount != $upRes) {
			M()->rollback(); // 事务回退
			echo "撤销买入订单失败1";
			echo "<br/>";
			return false;
		}

        $logRes       = array();
        $idIsBreakArr = array();
        $showArr      = array();
		foreach ($bondNumArr as $key => $value) {

            $value['is_return_bond_num'] = 0;
            // 已退保证金或者违规已扣除保证金则不用退保证金
            if ($value['is_break'] == 1 || $value['is_break'] == 2) {
                $value['is_return_bond_num'] = 1; 
                $bondNumArr[$key] = $value;
                continue;
            }

            // 如果存在未完成的子订单，则不退保证金
            if (!empty($childArr[$key]['is_bond_num']) && $childArr[$key]['is_bond_num'] == 1) {
                $value['is_return_bond_num'] = 1;
                $bondNumArr[$key] = $value;
                continue;
            }

            if ($value['bond_num'] <= 0) {
                continue;
            }

            $showTemp['id']  = '修改id'.$value['id'];
            $showTemp['uid'] = '退回用户id'.$value['uid'].'--财务表'.$value['uid']%4;
            $showTemp['num'] = '返回保证金:'.$value['bond_num'];

            $bondArr['financeType'] = 36;
            $bondArr['content']     = 'C2C挂单返还保证金(系统)';
            $bondArr['type']        = 1;
            $bondArr['money']       = $value['bond_num'];
            $bondArr['remarkInfo']  = $value['order_num'];
            $logRes[] = $this->setUserMoneyAndAddLog($value['uid'], $value['currency_type'], $bondArr);
            $idIsBreakArr[] = $value['id'];
            $bondNumArr[$key] = $value;
		}

        if (in_array(false, $logRes)) {
            M()->rollback(); // 事务回退
            echo "撤销买入订单失败2";
            echo "<br/>";
            return false;
        }

        // 如果退换保证金，需要更改is_break状态，
        if (!empty($idIsBreakArr)) {
            $breakArr = array(
                'is_break' => 1
            );
            $breakWhere = array(
                'id' => array('in', $idIsBreakArr),
            );
            $idIsBreakArrCount = count($idIsBreakArr);
            $breakRes = M('CcOrder')->where($breakWhere)->save($breakArr);
            if (empty($breakRes) || $breakRes != $idIsBreakArrCount) {
                M()->rollback(); // 事务回退
                echo "撤销买入订单失败2";
                echo "<br/>";
                return false;
            }
        }

        // 推送信息到APP
        $currArr = $this->getCurrencyList();
        $this->pushAppInfoByRevoke($bondNumArr, $currArr, 7);
		M()->commit(); // 事务回退
        echo "撤销买入订单成功";
        echo "<br/>";
        return true;
	}

    /**
     * 设置币数量及添加财务日志
     * @author liruqing 2018-03-05T15:31:29+0800
     * @param  [type] $uid        [description]
     * @param  [type] $currencyId [description]
     * @param  [type] $num        [description]
     * @param  [type] $data       [description]
     */
    private function setUserMoneyAndAddLog($uid, $currencyId, $data, $oper='inc'){

        $logRes   = array();
        $logRes[] = $this->setUserMoney($uid, $currencyId, $data['money'], 'num', $oper);
        $data['afterMoney'] = $this->getUserBalance($uid, $currencyId);
        $logRes[] = $this->AddFinanceLog($uid, $currencyId, $data);

        return $logRes;
    }

    /**
     * 获取用户某币种余额
     * @author lirunqing 2018年3月5日15:47:29
     * @param  int $userId     用户id
     * @param  int $currencyId 币种id
     * @return float
     */
    public function getUserBalance($userId, $currencyId) {

        $currencyWhere['uid']         = $userId;
        $currencyWhere['currency_id'] = $currencyId;
        $curRes                       = M('UserCurrency')->where($currencyWhere)->find();

        return !empty($curRes['num']) ? $curRes['num'] : 0.0000;
    }

	 /**
     * 添加财务日志
     * @author lirunqing 2017-10-13T12:20:43+0800
     * @param  int $uid        用户id
     * @param  int $currencyId 币种id
     * @param  array   $dataArr 扩展数组
     *         string  $dataArr['financeType'];日志类型 必传
     *         string  $dataArr['content'];内容 必传
     *         int     $dataArr['type'];类型(收入=1/支出=2) 必传
     *         float   $dataArr['money'];金钱 必传
     *         float   $dataArr['afterMoney'];操作之后的余额 必传
     *         float   $dataArr['remarkInfo'];线下交易的订单号 非必传
     * @return bool
     */
    private function AddFinanceLog($uid, $currencyId, $dataArr=array()) {

        $financeType = $dataArr['financeType'];
        $content     = $dataArr['content'];
        $type        = $dataArr['type'];
        $money       = $dataArr['money'];
        $afterMoney  = $dataArr['afterMoney'];
        $remarkInfo  = !empty($dataArr['remarkInfo']) ? $dataArr['remarkInfo'] : 0;

        if (empty($financeType) || empty($content) || empty($type)
            || empty($money) || empty($afterMoney) ) {
            return false;
        }

        $data = array (
            'uid'          => $uid,
            'currency_id'  => $currencyId,
            'finance_type' => $financeType,
            'content'      => $content,
            'type'         => $type,
            'remark_info'  => $remarkInfo,
            'money'        => $money,
            'add_time'     => NOW_TIME,
        );

        if (!empty($afterMoney)) {
            $data['after_money'] = $afterMoney;
        }

        $table     = 'UserFinance';
        $tableName = getTbl($table,$uid);
        $res       = M($tableName)->lock(true)->add($data);
        return $res;
    }

	/**
     * 增加修改个人币种资金信息(缓存)
     * @author lirunqing 2018年3月5日10:08:54
     * @param int $uid	用户id
     * @param int $currencyId	币种id
     * @param string $num		数量
     * @param string $field		类型 num/forzen_num
     * @param string $operationType	运算类型	inc/dec
     * @return boolean
     */
    private function setUserMoney($uid, $currencyId, $num, $field='num', $operationType='inc'){

        if ($field != 'num' && $field != 'forzen_num'){
            return false;
        }

        if ($operationType != 'inc' && $operationType != 'dec'){
            return false;
        }

        $userCurrency = M('UserCurrency')->lock(true)->where(array('uid'=> $uid, 'currency_id'=> $currencyId))->find();
        if( $num == 0 ){
            return true;
        }
        if($operationType == 'inc'){
            $newNum = bcadd($userCurrency['num'], $num, 8);
            return  M('UserCurrency')->lock(true)->where(array('uid'=> $uid, 'currency_id'=> $currencyId))->setField($field, $newNum); // 加
        }

        // 不能让余额为负数  改为可以让余额为负数
        $newNum = bcsub($userCurrency['num'], $num, 8);
        return M('UserCurrency')->lock(true)->where(array('uid'=> $uid, 'currency_id'=> $currencyId))->setField($field, $newNum); //减
    }
}  