<?php
namespace Timer\Controller;
/**
 * @author 建强 2018年1月25日15:08:58
 * @desc   定时任务的脚本-解封用户
 */
class  C2CblockingController extends RunController
{
	/**
	 * @method 定时任务 解封c2c失信次数达3次的记录
	*/
	public function run(){
	    $time  = time();
	    $where = ['cc_break_num'=>['EGT',3]];
	    $field = 'uid,cc_break_num,cc_break_time';
	    $uids  = [];
	    
	    $break_users = M('CcComplete')->field($field)->where($where)->select();
	    if(empty($break_users)) die('------没有符合要求的用户需要解封');
	    foreach($break_users as $value){
	        $flag=$this->canDeblocking($value['cc_break_time'],$time);
	        if($flag) $uids[]=$value['uid'];
	        unset($flag);
	    }
	    if(empty($uids)) die('------用户解封时间未到,本次不处理');
	    $where_uid = ['uid'=>['IN',$uids]];
	    $break_num = ['cc_break_num'=>0,'cc_break_time'=>0];
	    $ret=M('CcComplete')->where($where_uid)->save($break_num);
	    if(empty($ret)) die('------数据库操作失败 ，解封失败');
	    dump("重置用户的uid：".implode(',',$uids));
	}
	/**
	 * @method 判断是否可以解封
	 * @param int  $overtime_num   失信次数
	 * @param int  $overtime_time  失信时候的时间戳
	 * @param int  $time  当前时间戳
	 * @return bool
	 */
	private function canDeblocking($overtime_time,$time,$overtime_num=3){
		$timeArr=[
			'3'=>24*60*60,    //24h
		];
		$expire=$overtime_time+$timeArr[$overtime_num];
		$flag=false;
		if($expire<=$time) $flag=true;
		return $flag;
	}
}