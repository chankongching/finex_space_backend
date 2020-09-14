<?php
namespace Back\Controller;
use Back\Tools\Page;
use Back\Common\OrderUserMoney;
use Back\Common\EntrustType;
use Common\Api\RedisIndex;

/**
 * @desc   BTC交易区  撮合式交易区
 * @author 建强  2017年11月22日
 */
class  BitCoinController extends BackBaseController
{    
    
    /**
     * @var array  交易币种对  LTC/BTC
    */
    protected $entrust_type = [];
    
	//订单状态
	public  $order_status=[
			""=>'請選擇',
			"1"=>"買",
			"2"=>"賣",
			"3"=>"完成",
			"4"=>"撤銷",
	];
	
	public function _initialize(){    
		 parent::_initialize();
		 $entrust_type  = $this->getEntrustTypeArr();
		 $this->assign("entrust_type",$entrust_type);
		 $this->assign("status",$this->order_status);
	}
	/**
	 * @method 获取比特幣交易币种对信息 
	 * @return array  LTC/BTC ....
	 */
	protected function getEntrustTypeArr(){
	     $entrust_type = EntrustType::getBtcEntrustTypeList();
	     $this->entrust_type= $entrust_type;
	     $entrust_type[''] = '請選擇';
	     ksort($entrust_type);
	     return $entrust_type;
	}
	/**
	 * @method 订单列表
	*/
	public function  orderList(){    
		 $param=$this->getParam();
        //查詢該用戶是否是代理商
        $redis=RedisIndex::getInstance();
        $redis_data = $redis->getSessionValue("user");
        $agent =D("auth_group_access")->where("uid = {$redis_data['id']} and group_id = 11")->find();
        if ($agent){
            $admin_data = D("admin_user")->where("id = {$redis_data['id']} ")->find();
            if (empty($param)){
                $array["a.invite_code"] = $admin_data["invite_code"];
                $param = $array ;
            }else{
                $param["a.invite_code"] = $admin_data["invite_code"];
            }
        }
		 if(count($param)>0)
		 {
		 	  //带搜索条件
		 	  $count=M('UsdtAreaOrder')
                  ->join("left join trade_user as a on trade_usdt_area_order.sell_id = a.uid")
                  ->join("left join trade_user as b on trade_usdt_area_order.buy_id = b.uid")
                  ->where($param)->count();
		 	  $Page=new Page($count,15);

		 	  $list=M('UsdtAreaOrder')
                  ->join("left join trade_user as a on trade_usdt_area_order.sell_id = a.uid")
                  ->join("left join trade_user as b on trade_usdt_area_order.buy_id = b.uid")
                  ->where($param)
                  ->order('id desc')
                  ->limit($Page->firstRow,15)
                  ->select();
		 }
		 else
		 {  
		 	 //带搜索条件
		 	 $count=M('UsdtAreaOrder')->count();
		 	 $Page=new Page($count,15);
		 	 $list=M('UsdtAreaOrder')->order('id desc')->limit($Page->firstRow,15)->select();
		 }
		 
		 //转换用户的uid到姓名
		 $ret=$this->TransferUidToName($list);
		 $show=$Page->show();
		 $this->assign("page",$show);   //分页栏
        $this->assign('agent',count($agent));// 传入前端判断是否是代理商
		 $this->assign("list",$ret);    //数据
		 $this->display();
	}
	/**
	 * @method 获取参数 GET params
	 * @return array k=>v
	*/
	private function getParam(){
		$buyname=trim(I('buyname'));          //买家姓名
		$sellname=trim(I('sellname'));        //卖家姓名
		$order_num=trim(I('order_num'));      //订单号   
		$status=trim(I('status'));            //订单状态
		$entrust_type=trim(I('entrust_type'));//委托类型
		$invite_code=trim(I('invite_code'));//代理商邀请码

		$where=[];
		if(!empty($buyname))  $where['trade_usdt_area_order.buy_id']=$this->getUidByName($buyname);
		if(!empty($sellname)) $where['trade_usdt_area_order.sell_id']=$this->getUidByName($sellname);
		if (!empty($order_num))$where['trade_usdt_area_order.order_num']=$order_num;
		if (!empty($invite_code))$where['a.invite_code']=$invite_code;
		if (!empty($status) && $status>0)$where['trade_usdt_area_order.status']=$status;
		if (!empty($entrust_type) && $entrust_type>0)$where['entrust_type']=$entrust_type;
		
		return $where;
	}
	/**
	 * @method 撤销订单 -后台管理员撤销订单
     */
	public  function  revokeOrder()
	{
		 $id=trim(I('post.id'));
		 if ($id*1<0) return $this->error("參數錯誤");
		 $ret=M('UsdtAreaOrder')->find($id);
		 if(empty($ret)) return $this->error("訂單不存在");
		 if ($ret['status']>2)return $this->error("該訂單不能處理");
		 $uid=0;
		 //退回手续费
		 if ($ret['sell_id']>0)  $uid=$ret['sell_id'];
		 if ($ret['buy_id']>0)   $uid=$ret['buy_id'];
		 
		 //确认退币的币种信息
		 $currecny_id=$this->getCurrencyId($ret['entrust_type'],$ret['status']);
		 //获取正确的币种数量
		 $num=$this->getRightNum($ret['status'],$ret['leave_num'],$ret['entrust_price']);
		 //根据买卖的数据量级不一样
		 $result=$this->Singelrevoke($ret['id'],$uid,$num,$ret['order_num'],$currecny_id);
		 if($result) return $this->success("撤銷訂單成功"); 
	     
		 return $this->error('撤銷失敗');
	}
	/**
	 * @method  获取正确的币种数量
	 * @param int $status
	 * @param float $leave_num
	 * @param float $entrust_price
	 */
	private function getRightNum($status,$leave_num,$entrust_price){  
	    if(!in_array($status, [1,2])) return 0;
	    //买的
	    if($status==1)  return  big_digital_mul($leave_num, $entrust_price);
	    //卖的话直接是退余额
	    return $leave_num;
	}
	/**
	 * @method 根据买卖的不同的决定退币的币种
	 * @param int  $entrust_type   
	 * @param int  $status
	 */
	 private function getCurrencyId($entrust_type,$status){   
	     //买退比特币
	     if($status==1) return 1;
	     //否则退对应的币种 
	     return $entrust_type;
	 }
	 
	/**
	 * @method 撤销用户订单
	 * @param int  $orderId
	 * @param int  $uid
	 * @param float $num
	 * @param int $status  分成买家卖家  退币的不一样
	 * @return bool
	*/
    private function Singelrevoke($orderId,$uid,$num,$orderNum,$currency_id)
    {
    	 if($uid>0 && $num>0)
    	 {
    	   
    	 	  $res=M('UserCurrency')->where(['uid'=>$uid,'currency_id'=>$currency_id])->field('num')->find();
    	 	  if ($res)
    	 	  {
    	 	  	 $data=[
    	 	  	 	 'update_time'=>NOW_TIME, 
    	 	  	     'status'     =>4,        //(撤销订单)
    	 	  	 ];
    	 	  	 $after_money= bcadd($res['num'],$num, 8);
    	 	  	 $userObj= new OrderUserMoney();
    	 	  	 
    	 	  	 $trans=M();
    	 	  	 $trans->startTrans();
    	 	  	 //币币撤销退币类型为16
    	 	  	 $r= [];
    	 	  	 $r[]=$userObj->setUserMoney($uid, $currency_id, $num);   
    	 	  	 $r[]=$userObj->AddFinanceLog($uid, $currency_id, 16,"币币交易(管理员)撤销返款", 1, $num, $after_money,$orderNum);

    	 	  	 $r[]=M('UsdtAreaOrder')->where(['id'=>$orderId])->save($data);
    	 	  	 if (in_array(false, $r))
    	 	  	 {
    	 	  	 	$trans->rollback();
    	 	  	 	return false;
    	 	  	 }
    	 	  	 else 
    	 	  	 {
    	 	  	 	$trans->commit();
    	 	  	 	return true;
    	 	  	 }
    	 	  }
    	 	  return false;
    	 }
    	 return  false;
    }
	/**
	 * @param string  $name
	 * @return int  Uid
	*/
	private function getUidByName($name)
	{
		$ret=M('User')->field('uid')->where(['username'=>$name])->find();
		return $ret?$ret['uid']:-1;
	}
   /**
     * @method 转换uid- 用户名
     * @param array $list
     * @return array
     */
    private function TransferUidToName($list){
    	if(count($list)>0){
    		$ids=[];
    		foreach($list as $k=>$v){
    		    if($v['sell_id']>0)	$ids[]=$v['sell_id'];
    			if ($v['buy_id']>0) $ids[]=$v['buy_id'];
    		}
    		
    		$arrIds=(array_unique($ids));
    		if(count($arrIds)>0)
    		{
    		    $where=[];
    			$where['uid']=['IN', $arrIds];
    			$userInfo=M('User')->field('uid,username')->where($where)->select();
    			$user=array_column($userInfo, 'username','uid');

    			foreach ($list as $k=>$v)
    			{
                    $typeName = '';
    				$userId   = isset($v['sell_id']) && $v['sell_id'] > 0 ?  $v['sell_id'] : $v['buy_id'];
                    $typeName = isset($v['sell_id']) && $v['sell_id'] > 0 ?  '挂卖单' : '挂买单';
    				$list[$k]['username'] = $user[$userId];
    				$list[$k]['typeName'] = $typeName;
    				$list[$k]['entrust_type'] =$this->entrust_type[$v['entrust_type']];
    			}
    		}
    	}
    	return $list;
    }
	/**
	 * @method 获取匹配的撮合式交易的数据
	 * @param int $pid   买家或者卖家的pid  
	 * @param int $type  买家还是卖家类型     
	*/
	public function getMatchInfo(){
		$pid=trim(I('pid'));
		$type=strtolower(trim(I('type')));
		if ($pid*1<0)  return $this->error("參數錯誤");
		
	    $buyOrSell='pid_'.strtolower($type);
	    $ret=M('UsdtAreaOrder')->find($pid);
	    if(!$ret)  return $this->error("訂單不存在");
	    $where=[
	        "s.{$buyOrSell}"=>$pid,"s.entrust_type"=>$ret['entrust_type'],
	    ];
	    
	    //获取匹配卖家还是买家的uid值
	    $sellOrBuy=$type.'_id';
	    $where['s.'.$sellOrBuy]=$ret[$sellOrBuy];
	    //查询成交的记录表
	    $list=M('BtcSuccessRecord')
	        ->alias('s')
	        ->field('s.*,u.username as sell_username,user.username as buy_username')
	        ->join('LEFT JOIN __USER__ as u ON s.sell_id=u.uid LEFT JOIN __USER__  as user ON s.buy_id=user.uid')
	        ->where($where)
	        ->order('s.trade_time desc')
	        ->select();   
	   //匹配交易对     
	   if(!empty($list)){
	        foreach($list as $key=>$value){
	            $list[$key]['entrust_type'] = $this->entrust_type[$value['entrust_type']];
	        }
	    }
	    $this->assign("list",$list);
	    $this->display("matchBox");
    }
    //****************BTC成交记录表*************************//
	/**
	 * BCC交易区 成交记录的列表
	*/
	public function  recordList()
	{
		$param=$this->getWhereParam();	
		if(count($param)>0)
		{
		      //带搜索条件
		 	  $count=M('BtcSuccessRecord')->alias('s')->where($param)->count();
		 	  $Page=new Page($count,15);
		 	  $list=M('BtcSuccessRecord')
		 	        ->alias('s')
			 	    ->field('s.*,u.username as sell_username,user.username as buy_username')
			 	    ->join('LEFT JOIN __USER__ as u ON s.sell_id=u.uid LEFT JOIN __USER__  as user ON s.buy_id=user.uid')
			 	    ->where($param)
			 	    ->order('s.trade_time desc')
			 	    ->limit($Page->firstRow,15)
			 	    ->select();
		} 
		else
		{  
		 	 $count=M('BtcSuccessRecord')->count();
		 	 $Page=new Page($count,15);
		 	 $list=M('BtcSuccessRecord')
		 	 ->alias('s')
		 	 ->field('s.*,u.username as sell_username,user.username as buy_username')
		 	 ->join('LEFT JOIN __USER__ as u ON s.sell_id=u.uid LEFT JOIN __USER__  as user ON s.buy_id=user.uid')
		 	 ->order('s.trade_time desc')
		 	 ->limit($Page->firstRow,15)
		 	 ->select();
		}
		
		if(!empty($list)){
		    foreach($list as $key=>$value){
		        $list[$key]['entrust_type'] = $this->entrust_type[$value['entrust_type']];
		    }
		}
		
		$show=$Page->show();
		$this->assign("page",$show);    //分页栏
		$this->assign("list",$list);    //数据
		$this->display();
	}
	/**
	 * @param GET param
	 * @return array k=>v
	*/
	private function getWhereParam()
	{
		$buyname=trim(I('buyname'));          //买家姓名
		$sellname=trim(I('sellname'));        //卖家姓名
		$entrust_type=trim(I('entrust_type'));//委托类型
		
		$where=[];
		if(!empty($buyname))$where['s.buy_id']   = $this->getUidByName($buyname);
		if(!empty($sellname)) $where['s.sell_id']= $this->getUidByName($sellname);
		if (!empty($entrust_type) && $entrust_type>0) $where['s.entrust_type']= $entrust_type;
		return $where;
	}
}