<?php
namespace Timer\Controller;
/**
 * @author 建强 2018年1月25日15:08:58
 * @desc   定时任务的脚本-解封用户P2P失信次数处理  
 * @desc   如果满足条件的用户 ：失信三次的重置次数和时间  ,失信1次2次的只能重置时间 
 */
class  DeblockingController extends RunController
{
	/**
	 * @method 定时任务P2P交易模式 解封失信次数重置
	*/
	public function run(){
		$time  = time();
		$limit = 1000; 
		$field = 'uid,overtime_num,overtime_time';
		$where = [
		    'status'=>1,'overtime_num'=>['GT',0],
		    'overtime_time'=>['GT',0],
		];
		$users = M('User')->field($field)->where($where)->limit($limit)->select();
		if(empty($users)) die("-----没有符合失信的用户数据");
		
		$three_uids = [];
		$other_uids = [];
		foreach($users as $value){
		    $num = $value['overtime_num'];
		    $flag= $this->canDeblocking($num,$value['overtime_time'],$time);
		    if($num>=3 && $flag==true) $three_uids[]=$value['uid'];
		    if(in_array($num, [1,2]) && $flag==true) $other_uids[]=$value['uid'];
		}
		$ret_three = $this->resetUserBreakNumByuids($three_uids, 0);
		$ret_other = $this->resetUserBreakNumByuids($other_uids, 1);
		
		//处理结果：
		dump($ret_three);
		dump($ret_other);
	}
	//更新字段
	protected $upFields=[
	    '0'=>['overtime_time'=>0,'overtime_num'=>0],
		'1'=>['overtime_time'=>0,],	
	];
	/**
	 * @method 重置失信次数
	 * @param array   $uids 
	 * @param int     $type 更新字段
	*/
	protected function resetUserBreakNumByuids($uids,$type){   
	    if(empty($uids)) return "-------用户解封时间未到，本次不处理".$type;
		$where_uid  = ['uid'=>['IN',$uids]];
		$upFields   = $this->upFields[$type];
		$result     = M('User')->where($where_uid)->save($upFields);
		if(count($uids)==$result) return "-------解封成功 ，解封用户账号uid：". implode(',', $uids).$type;
		return "--------解封失败，数据更新失败".$type;
	}
	/**
	 * @param int num       $overtime_num   失信次数
	 * @param int timestamp $overtime_time  失信时候的时间戳
	 * @param int timestamp $time   当前时间戳
	 * @return bool
	 */
	private function canDeblocking($overtime_num,$overtime_time,$time){
		$timeArr=[
				'1'=>24*60*60,       //1天
				'2'=>7*24*60*60,     //7天
				'3'=>30*24*60*60,    //30天
		];
		$expire=$overtime_time+$timeArr[$overtime_num];
		$flag=false; 
		if($expire<=$time) $flag=true;
		return $flag;
	}
}