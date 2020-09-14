<?php
/**
 * 公共函数封装类
 * @author lirunqing 2017年10月9日10:29:57
 */
namespace Back\Tools;


class Score {

    /**
     * 计算用户积分及用户等级
     * @author lirunqing 2017-11-02T14:11:03+0800
     * @param  int     $userId      用户id
     * @param  float   $integral    加/减积分值
     * @param  array   $extArr      拓展数组
     *         string  $extArr['operationType']  必传   运算符,inc表示加;dec表示减
     *         string  $extArr['scoreInfo']      必传   积分日志场景；例：首次登录增加积分
     *         string  $extArr['status']         必传   积分日志场类型；
     *                                           1绑定电话号码,2绑定邮箱,3绑定充值地址,4绑定转出地址,5绑定APP令牌,6交易密码,
     *                                           7银行卡账户,8每天首次登陆,9订单交易,10充值钱,11充值币,12vip充值资产额  13 实名认证
     *         string  $extArr['isOverTime']     非必传 失信计算标记；0表示不计算;1表示计算
     *         string  $extArr['remarkInfo']     非必传 线下交易的订单号
     * @return bool
     */
    public function calUserIntegralAndLeavl($userId, $integral, $extArr=array()){

        $operationType = $extArr['operationType'];
        $isOverTime    = !empty($extArr['isOverTime']) ? $extArr['isOverTime'] : 0;
        $scoreInfo     = $extArr['scoreInfo'];
        $status        = $extArr['status'];
        $remarkInfo    = !empty($extArr['remarkInfo']) ? $extArr['remarkInfo'] : 0;

        if (empty($userId) || empty($integral) || empty($scoreInfo) || empty($status)
            || !in_array($operationType , array('inc', 'dec')) ) {
            return false;
        }

        $whereUser['uid'] = $userId;
        $userLevelInfo    = M('User')->field('level,credit_level,overtime_num')->where($whereUser)->find();

        //开启事务
        $flag = false;

        $integral1 = 0;
        // 暂时只开放到vip3，用户积分只能是3000
        if ( ($userLevelInfo['credit_level'] + $integral)  > 3000) {
            $integral1 = 3000 - $userLevelInfo['credit_level'];
        }

        // vip5，用户积分只能是16000
        if (($userLevelInfo['credit_level'] + $integral)  > 16000) {
            $integral1 = 16000 - $userLevelInfo['credit_level'];
        }

        //对应加积分,暂时升级只升到vip3
        if($operationType == 'inc' && $userLevelInfo['level'] < 3){
            $type = 2;
            $flag = true;
            $point = ($integral1 > 0) ? $integral1 : $integral;
            $r[] = M('User')->where($whereUser)->setInc('credit_level', $point);
        }

        //对应减积分
        if($operationType == 'dec'){
            $type = 1;
            $flag = true;
            $r[] =  M('User')->where($whereUser)->setDec('credit_level', $integral);
        }

        //交易超时失信次数增加一次并设置失信时间
        if ($operationType == 'dec' && $isOverTime == 1) {
            $r[] = M('User')->where($whereUser)->setInc('overtime_num', 1);
            $r[] = M('User')->where($whereUser)->setField('overtime_time',time());

            // 失信超过3次则封号
            $overtimeNum = $userLevelInfo['overtime_num']+1;
            if ($overtimeNum > 3) {
                $userWhere = array(
                    'uid' => $userId
                );
                $r[] = M('User')->where($userWhere)->setField('status','-2');
            }
        }

        $userInfo  = M('User')->where($whereUser)->find();
        $userLevel = $this->getUserLevel($userInfo['credit_level']);// 获取用户积分变化后的用户等级
        // 积分变更后，添加积分日志
        if (!empty($flag)) {
            $logData = array(
                'uid'         => $userId,
                'level'       => $userLevel,
                'integral'    => $integral,
                'total_score' => $userInfo['credit_level'],
                'info'        => $scoreInfo,
                'remark_info' => $remarkInfo,
                'type'        => $type,
                'status'      => $status,
            );
            $r[] = $this->addScoreLog($userId, $logData);
        }

        // 判断用户等级是否发生变更
        if ($userInfo['level'] != $userLevel) {
            $r[] = M('User')->where($whereUser)->setField('level', $userLevel);
        }

        //返回结果
        if(in_array(false, $r)){
            return false;
        }
        return true;

    }

    /**
     * 根据积分获取用户等级
     * @author 2017-11-02T12:23:05+0800
     * @param  [type] $integral [description]
     * @return [type]           [description]
     */
    public function getUserLevel($integral){
        switch ($integral) {
            case $integral >= 100 && $integral < 1000:
                $level = 1;
                break;
            case $integral >= 1000 && $integral < 3000:
                $level = 2;
                break;
            case $integral >= 3000 && $integral < 6000:
                $level = 3;
                break;
            case $integral >= 6000 && $integral < 16000:
                $level = 4;
                break;
            case $integral >= 16000:
                $level = 5;
                break;
            default:
                $level = 0;
                break;
        }

        return $level;
    }

    /**
     * 添加用户积分加减日志
     * @author lirunqing 2017-11-03T11:34:15+0800
     * @param  int   $userId 用户id
     * @param  array $data   日志信息数组
     * @return bool
     */
    public function addScoreLog($userId, $data){

        $data['uid']         = (int)$userId;
        $data['level']    = (int)$data['level'];
        $data['integral']    = $data['integral'];
        $data['total_score'] = $data['total_score'];
        $data['info']        = $data['info'];
        $data['status']      =$data['status'];
        $data['type']        = $data['type'];
        $data['add_time']    = time();
        $table               = 'UserScoreLog';
        $tableName           = getTbl($table, $userId);

        return M($tableName)->add($data);
    }

    /**
     * 根据用户填写的银行id获取获取银行信息
     * @author 2017-10-13T15:44:15+0800
     * @param  int $id 用户填写的银行卡信息的id
     * @return string
     */
    public function getBankTypeByBankId($id){
        $id       = (int)$id;
        $bankInfo = M('UserBank')->where(array('id'=>$id))->find();
        return $bankInfo['bank_type'];
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

}