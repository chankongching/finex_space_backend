<?php
namespace Back\Common;
/**
 *  3+1 污点计算
 * @author 宋建强 2018年3月2日11:26:54
 */
class  CCBreakOrder{
	/**
	 *@method 增加3+1 污点数  
	 *@author 建强    2018年3月2日11:17:21 
	*/
	public static function addBreakTimeNum($uid,$num=1)
    {    
          $where['uid']=$uid;
          $ret=M('CcComplete')->where($where)->find();
          if(!$ret) return false;
          $data['small_order_time']=$ret['small_order_time']+$num;
          $data['break_order_time']=$ret['break_order_time']+$num;
          $data['cc_break_num']=($ret['cc_break_num']+$num>=3)?3:($ret['cc_break_num']+$num);
          $data['cc_break_time']=NOW_TIME;
          $data['update_time']=NOW_TIME;
          $result=M('CcComplete')->where($where)->save($data);
          return $result;
    }
    
    
    /**
     * @author 卖家超时为未收款进行   买家加1次
     * @param unknown $uid
     * @param number $num
     * @return boolean
     */
    public static function addBreakTimeNumForbuyAddOneSmallOrderTime($uid,$num=1)
    {
	      $where['uid']=$uid;
	      $ret=M('CcComplete')->where($where)->find();
	      if(!$ret) return false;
	      $data['small_order_time']=$ret['small_order_time']+$num;
	      $data['update_time']=NOW_TIME;
	      $result=M('CcComplete')->where($where)->save($data);
	      return $result;
    }
    
    
    
}