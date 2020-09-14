<?php
namespace Back\Common;
class OrderUserMoney
{
	/**
	 * 添加财务日志
	 * @param int $uid	用户id
	 * @param int $currency_id	币种id
	 * @param int $finance_type	日志类型
	 * @param string $content	内容
	 * @param int $type			类型(收入=1/支出=2)
	 * @param string $money		金钱
	 * @return Ambigous <\Think\mixed, boolean, unknown, string>
	 */
	public function AddFinanceLog($uid, $currency_id, $finance_type, $content,
                                  $type, $money,$after_money,$remark_info='') {

        $remark_info    = !empty($remark_info) ? $remark_info : 0;
		$data = array (
			'uid' => $uid,
			'currency_id' => $currency_id,
			'finance_type' => $finance_type,
			'content' => $content,
			'type' => $type,
			'money' => $money,
			//曾加一个操作之后的余额	
			'after_money'=>$after_money,
			'add_time' => NOW_TIME,
            'remark_info' => $remark_info
		);
		//注意插入分表数据
//		$table=getTbl('UserFinance', $uid);
		$res = M('UserFinance'.$uid%4)->lock(true)->add($data);
		return $res;
	}
	/**
	 * 增加修改个人币种资金信息(缓存)
	 * @param int $uid	用户id
	 * @param int $currency_id	币种id
	 * @param string $num		数量
	 * @param string $type		类型 num/forzen_num
	 * @param string $operationType	运算类型	inc/dec
	 * @return boolean
	 */
	public function setUserMoney($uid,$currency_id,$num,$field='num',$operationType='inc')
	{
		
		if ($field!='num' && $field!='forzen_num')
		{
			return false;
		}
		if ($operationType!='inc' &&  $operationType!='dec')
		{
			return false;
		}  
		
		$where=array('uid'=>$uid,'currency_id'=>$currency_id);
        $userCurrency = M('UserCurrency')->lock(true)->where($where)->find();
		//注意这个地方不能进行取整
		if($operationType == 'inc')
		{   
			//加上金额
            $newNum = bcadd($userCurrency['num'], $num, 8);
            return  M('UserCurrency')->lock(true)
                ->where($where)
                ->setField($field, $newNum);
		}
		else
		{
            $newNum = bcsub($userCurrency['num'], $num, 8);
             //减去
            return  M('UserCurrency')->lock(true)
                    ->where($where)
                    ->setField($field, $newNum);
		}
	}
}

