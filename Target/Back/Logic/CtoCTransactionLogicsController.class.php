<?php
/**
 * C2C交易逻辑业务
 * @author lirunqing 2017年10月9日10:20:20
 */
namespace Back\Logic;
use Back\Controller\BackBaseController;
use Think\Controller;
use Common\Api\RedisIndex;
use Common\Api\redisKeyNameLibrary;

class CtoCTransactionLogicsController extends Controller {


    public $msgArr = array(
        'code' => 200,
        'msg'  => '',
        'data' => array()
    );
    /**
     * 计算用户币种金额
     * @author lirunqing 2018年2月27日14:32:26
     * @param  int     $currencyId  币种id
     * @param  string  $financeType 日志类型
     * @param  array   $dataArr     扩展数组
     *         string  $dataArr['content'];内容 必传
     *         float   $dataArr['money'];金额 必传
     *         int     $dataArr['type'];类型(收入=1/支出=2) 必传
     * @return array
     */
    public function operationUserCoin($currencyId, $financeType, $dataArr=array(),$uid){

        $returnArr              = array();
        $dataArr['financeType'] = $financeType;

        $afterMoney            = $this->getUserBalance($uid, $currencyId);// 获取用户余额
        $dataArr['afterMoney'] = $afterMoney;
        $moneyRes              = $this->AddFinanceLog($uid, $currencyId, $dataArr);// 记录用户财务日志

        if (empty($moneyRes)) {
            $this->msgArr['code'] = 207;
            return $this->msgArr;
        }

        return $this->msgArr;
    }
    /**
     * 获取用户某币种余额
     * @author lirunqing 2017-10-13T14:49:39+0800
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
    public function AddFinanceLog($uid, $currencyId, $dataArr=array()) {

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
    /*
    * 李江 2018年2月27日18:09:58
    * 撤销订单业务逻辑层 
     * orderId 主订单id
     * flag 1 管理员点击撤销按钮撤销 0 检测剩余量不足自动撤销
     */
    public function revokeBigOrder($orderId,$flag){
        $resArr = [];
        $status = 4;
        if( $flag  ){
            $status = 5;
        }
        $saveData = [
            'status' => $status,
            'update_time' =>time(),
        ];

        $bigOrder = M('CcOrder')->where(['id'=>$orderId])->find();
        $uid = $bigOrder['uid'];
        $orderNum = $bigOrder['order_num'];
        if( !$bigOrder ){
            return false;
        }

        $resArr[] = M('CcOrder')->where(['id'=>$orderId])->save($saveData); //改变状态为撤销
        $currencyId = $bigOrder['currency_type'];
        $extArr = [
            'type'   => 1,
            'remarkInfo'=>$orderNum,
            'opera'  => 'inc'
        ];
        $log = "系统自动撤销";
        if( $status ){
            $log = "后台管理员撤销订单";
        }
        //分挂买单和挂卖单退还
        //挂买单
        if( $bigOrder['type'] == 1 ){
            if( $bigOrder['is_break'] == 0 ){
                //收取保证金到平台
                $subOrders= M('CcTrade')->where(['p_order_num'=>$orderNum])->select();
                $res = $this->checkSubOrders($subOrders);
                if( $res && $bigOrder['bond_num'] > 0){
                    $bond_num = $bigOrder['bond_num'];
                    $extArr['content'] = 'C2C挂买单保证金返还';
                    $extArr['money']   = $bond_num;
                    //退还保证金
                    $res = $this->calCurrencyNumAndAddLog($currencyId,22,$extArr,$uid);//返还保证金
                    if( $res['code'] != 200 ){
                        $resArr[] = 0;
                    }else{
                        $resArr[] = 1;
                    }
                    //加管理员操作日志
                    $backBaseObj = new BackBaseController;

                    $resArr[] = $backBaseObj->addUserMoneyLog(['user_id'=>$uid,'type'=>10,'log'=>$log]);
                }
            }
        }else{
            //挂卖单
            $leaveNum = $bigOrder['leave_num'];
            $leaveFee = $bigOrder['leave_fee'];
            //1、退手续费
            $extArr['content'] = 'C2C交易手续费返还';
            $extArr['money'] = $leaveFee;
            $res = $this->calCurrencyNumAndAddLog($currencyId,24,$extArr,$uid);
            if( $res['code'] != 200 ){
                $resArr[] = 0;
            }else{
                $resArr[] = 1;
            }
            //2、退剩余的数量
            $extArr['content'] = 'C2C挂单撤销返还币';
            $extArr['money'] = $leaveNum;
            $res = $this->calCurrencyNumAndAddLog($currencyId,20,$extArr,$uid);
            if( $res['code'] != 200 ){
                $resArr[] = 0;
            }else{
                $resArr[] = 1;
            }
            //加管理员操作日志
            $backBaseObj = new BackBaseController;
            $resArr[] = $backBaseObj->addUserMoneyLog(['user_id'=>$uid,'type'=>10,'log'=>$log]);
        }
        if( !in_array(false,$resArr) ){
            return true;
        }else{
            return false;
        }
    }


    /*
     * 李江 检查子订单是否都已经完成
     */
    public function checkSubOrders($subOrders){
        if( count($subOrders) == 0 ){
            return 1;//没有子订单 退还保证金
        }

        foreach ($subOrders as $order){
            if( in_array($order['status'],[1,2,5]) ){
                return false;//有未完成的订单 不退
            }else{
                continue;
            }
        }
        return true;
    }
    /**
     * 扣除币种数量及添加财务日志
     * @author lirunqing 2018-02-27T16:13:46+0800
     * @param  int $currencyType    币种id
     * @param  int $financeType 日志类型
     * @param  array $extArr      扩展数组
     *                $extArr['content']    内容说明 例如:线下交易挂售人扣除 必传
     *			      $extArr['type']       类型(收入=1/支出=2) 必传
     *				  $extArr['money']      金额 必传
     *				  $extArr['remarkInfo'] 订单号    必传
     *				  $extArr['opera']      运算符号,inc加，dec扣除   必传
     * @return array
     */
    public function calCurrencyNumAndAddLog($currencyType, $financeType, $extArr,$uid){

        $currencyRes  = $this->setUserMoney($uid, $currencyType, $extArr['money'], 'num', $extArr['opera']);

        if (empty($currencyRes)) {
            $this->msgArr['code'] = 208;
            return $this->msgArr;
        }

        $finLogRes = $this->operationUserCoin($currencyType, $financeType, $extArr,$uid);// 添加财务日志

        if (empty($finLogRes) || $finLogRes['code'] != 200) {
            $this->msgArr['code'] = 209;
            return $this->msgArr;
        }

        return $this->msgArr;
    }
    /**
     * 增加修改个人币种资金信息(缓存)
     * @author lirunqing 2017-10-13 15:32:29
     * @param int $uid	用户id
     * @param int $currencyId	币种id
     * @param string $num		数量
     * @param string $field		类型 num/forzen_num
     * @param string $operationType	运算类型	inc/dec
     * @return boolean
     */
    public function setUserMoney($uid, $currencyId, $num, $field='num', $operationType='inc'){

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
