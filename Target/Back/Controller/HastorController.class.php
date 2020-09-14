<?php
/**
 * Created by PhpStorm.
 * User: 李江
 * Date: 2017/8/11
 * Time: 14:22
 * 用户中心控制器
 */
namespace Back\Controller;
use Back\Tools\Page;
class HastorController extends BackBaseController{
    
    /**
     * @method  获取组gid
     * @param   int $uid
     * @return  int
     */
    protected function getGroupId($uid)
    {
        $groupId=M('AuthGroupAccess')->where(['uid'=>$uid])->getField('group_id');
        return $groupId;
    }
    /**
     * @author 建强  2018年9月11日18:13:29
     * @method 获取管理员名称
     */
    protected function getAdminGroupNames()
    {
        $uids= M('AuthGroupAccess')->where(['group_id'=>1])->field('uid')->select();
        $arrUids=array_filter(array_column($uids, 'uid'));
        $where=[
            'id'=> ['IN',$arrUids]
        ];
        $names=M('AdminUser')->where($where)->field('username')->select();
        return array_column($names, 'username');
    }
    /**
     * @author 建强  2018年9月12日10:02:37 
     * @method 修改用户日志   超级管理员 
    */
    public function changeUser() 
    {
    	$this->DelLog('ChangeUserLog',1,'add_time',3);
        $where      = [];
        $username   = trim(I('username'));
        $admin_user = trim(I('admin_user'));
        $user_id    = trim(I('uid'));
        $type       = intval(I('type'));
        
        if(!empty($username))
        {
           $where['username'] = $username;
        }
        if(!empty($admin_user))
        {
            $where['admin_user'] = $admin_user; 
        }
        if(!empty($user_id))
        {
            $where['user_id'] = $user_id;
        }
        if($type>0) 
        {
            $where['type'] = $type;
        }
        //业务修改  2018年9月12日10:09:16  
        $uid       = $this->back_userinfo['id'];
        $groupId   = $this->getGroupId($uid);
        $adminNames= $this->getAdminGroupNames();
        //走默认不带搜索条件 
        if($groupId>1  && !in_array($admin_user, $adminNames) && !empty($admin_user))
        {   
            $where['admin_user'] = $admin_user;  //覆盖默认值
        }
        //默认条件 不带adminUserNames
        if($groupId>1 && empty($admin_user))
        {
             $where['admin_user']=['NOT IN',$adminNames];
        }
        $type_list = $this->get_user_type_list();
        //参数带管理员名称 直接过滤return
        if($groupId>1 && in_array($admin_user, $adminNames))
        {    
             $this->assign('type_list',$type_list);
             $this->display();
             exit;
        }
        $datainfo = $this->page($where);  //获取数据
        $this->assign('type',$type);
        $this->assign('list', $datainfo['list']);
        $this->assign('page', $datainfo['show']);
        $this->assign('type_list',$type_list);
        $this->display();
    }
    /**
     * @param array $where
     * @return string
    */
    public function page($where=array())
    {
        $datainfo=[];
        $num=15; 
        $order = 'add_time desc';
        $count = M('ChangeUserLog')->where($where)->count("id");
        $Page = new Page($count,$num);
        $datainfo['show'] = $Page->show(); 
        $datainfo['list'] = M('ChangeUserLog')
                            ->where($where)
                            ->order($order)
                            ->limit($Page->firstRow.','.$Page->listRows)
                            ->select();
        return $datainfo;
    }
     
    /**
     * @author 建强 2018年9月12日10:39:22 
     * @method 修改用户资金
     */
    public function changeUserMoney()
    {
    	$this->DelLog('ChangeUserMoneyLog',1,'add_time',3); //删除日志记录
        $where         = array();
        $username      = trim(I('username'));
        $admin_user    = trim(I('admin_user'));
        $user_id       = trim(I('uid'));
        $type          = (int)I('type');

        if(!empty($username))
        {
           $where['username']=$username;
        }
        if(!empty($admin_user))
        {
           $where['admin_user']=$admin_user;
        }
        if(!empty($user_id)) 
        {
            $where['user_id']=$user_id;
        }
        if($type>0) 
        {
            $where['type'] =$type;
        }
        
        //增加业务 2018年9月12日10:44:13 管理员普通用户组可见
        $uid       = $this->back_userinfo['id'];
        $groupId   = $this->getGroupId($uid);
        $adminNames= $this->getAdminGroupNames();
        //走默认不带搜索条件
        if($groupId>1  && !in_array($admin_user, $adminNames) && !empty($admin_user))
        {
            $where['admin_user'] = $admin_user;  //覆盖默认值
        }
        //默认条件 不带adminUserNames
        if($groupId>1 && empty($admin_user))
        {
            $where['admin_user']=['NOT IN',$adminNames];
        }
        $type_list = $this->get_user_money_type_list();  //userMoneyType
        //参数带管理员名称 直接过滤return
        if($groupId>1 && in_array($admin_user, $adminNames))
        {
            $this->assign('type_list',$type_list);
            $this->display();
            exit;
        }
        $datainfo = $this->pagetwo($where);
        $this->assign('list', $datainfo['list']);
        $this->assign('page', $datainfo['show']);
        $this->assign('type_list',$type_list);
        $this->display();
    }

    /**
     * @method 建强     2018年9月12日10:50:48
     * @param  array $where
     * @return array
     */
    public function pagetwo($where=array())
    {
        $datainfo = [];
        $num      = 15;
        $order    = 'add_time desc';
        $count    = M('ChangeUserMoneyLog')->where($where)->count("id");
        $Page     = new Page($count,$num);
        $datainfo['show'] = $Page->show();
        $datainfo['list'] = M('ChangeUserMoneyLog')
                            ->where($where)
                            ->order($order)
                            ->limit($Page->firstRow.','.$Page->listRows)
                            ->select();
        return $datainfo;
    }
    /**
     * @author  李江 2017年8月21日17:32:29  fix by 建强 2018年9月12日
     * @method  修改订单信息日志P2P 
    */
    public function changeOrderInfo()
    {
    	$this->DelLog('OrderStatusLog',1,'add_time',3); //删除日志记录
        $where=$this->searchParam();
        if(isset($where['dis']) && $where['dis']==1)
        {
             $this->display();exit;  //直接过滤
        }
        if(count($where))
        {
            $count = M('OrderStatusLog')->where($where)->count("id");
            $Page  = new Page($count,15);
            $list  = M('OrderStatusLog')
                     ->where($where)
                     ->order('add_time desc')
                     ->limit($Page->firstRow.','.$Page->listRows)
                     ->select();
        }
        else
        {
        	$count = M('OrderStatusLog')->count("id");
        	$Page  = new Page($count,15);
        	$list  = M('OrderStatusLog')
        	         ->order('add_time desc')
        	         ->limit($Page->firstRow.','.$Page->listRows)
        	         ->select();
        }
        $show= $Page->show();
        $this->assign('page',$show);
        $this->assign('list',$list);
        $this->display();
    }
    
    //p2p订单日志搜索参数
    private function searchParam()
    {    
    	   $data=[];
           $sell_name=trim(I('sell_name')); 
           $order_num=trim(I('order_num'));
           $admin_user=trim(I('admin_user'));
           
           if(!empty($order_num))
           {
           	  $data['order_num']=['like','%'.$order_num.'%'];
           }
           if (!empty($sell_name))
           {
           	  $data['sell_name']=$sell_name;
           }
           if(!empty($admin_user))
           {
           	  $data['admin_user']=$admin_user;
           }
           
           //增加业务 2018年9月12日10:44:13 管理员普通用户组可见
           $uid       = $this->back_userinfo['id'];
           $groupId   = $this->getGroupId($uid);
           $adminNames= $this->getAdminGroupNames();
           //走默认不带搜索条件
           if($groupId>1  && !in_array($admin_user, $adminNames) && !empty($admin_user))
           {
               $data['admin_user'] = $admin_user;  //覆盖默认值
           }
           //默认条件 不带adminUserNames
           if($groupId>1 && empty($admin_user))
           {
               $data['admin_user']=['NOT IN',$adminNames];
           }
           //参数带管理员名称 直接过滤return
           if($groupId>1 && in_array($admin_user, $adminNames))
           {
               $data['dis']=1;  
           }
           return $data;
    }
    //修改用户资料记录管理员操作日志
    public function get_user_type_list(){
        return [
            //断开6的状态  6为修改实名认证  只能在userReal表中进行 实名认证 
            '1' => '修改登錄密碼',
            '2' => '修改資金密碼',
            '3' => '修改手機號碼',
            '4' => '修改郵箱',
            '5' => '修改信譽積分',
            '7' => '激活狀態',
            '8' => '修改銀行卡信息',
            '9' => '修改護照認證狀態',
            '11'=> '修改用戶美金余額',
            '12'=> '修改用戶失信次數',
        ];
    }

    //获取操作类型
    public function get_user_money_type_list(){
        return [
            '1' => '修改個人錢包地址',
            '2' => '增加金額',
            '3' => '減少金額',
        ];
    }

   /*
    * 管理员操作订单日志记录
    * @author 建强  2017年11月20日 
    * tableName trade_order_admin_run_log
   */    
   public  function  OrderLogList()
   {  
   	   $this->DelLog('OrderAdminRunLog',1,'add_time',3); //删除日志记录
   	   $param=$this->getSearchParam();
   	   if(count($param)>0)
   	   {   
   	   	    //存在搜索条件
   	        $count = M('OrderAdminRunLog')->alias('o')->join('__TRADE_THE_LINE__ as t ON  o.order_id=t.id','left')->where($param)->count();
	   	   	$Page  = new Page($count,15);
	   	   	$list  = M('OrderAdminRunLog')
	   	   	         ->alias('o')
	   	   	         ->field('o.*,u.username,t.order_num')
	   	   	         ->join('__USER__  as u ON  o.sealed_id=u.uid','left')
	   	   	         ->join('__TRADE_THE_LINE__ as t ON  o.order_id=t.id','left')
	   	   	         ->where($param)
	   	   	         ->order('o.add_time desc')
	   	   	         ->limit($Page->firstRow.','.$Page->listRows)
	   	   	         ->select();
   	   }
   	   else 
   	   {    
   	   	    $count = M('OrderAdminRunLog')->count();
   	   	    $Page  = new Page($count,15);
            $list  = M('OrderAdminRunLog')
                    ->alias('o')
                    ->field('o.*,u.username,t.order_num')
                    ->join('__USER__  as u ON  o.sealed_id=u.uid','left')
                    ->join('__TRADE_THE_LINE__ as t ON  o.order_id=t.id','left')
                    ->order('o.add_time desc')
                    ->limit($Page->firstRow.','.$Page->listRows)
                    ->select();     
   	   }
   	   $show= $Page->show();
   	   $this->assign('page',$show);
   	   $this->assign('list',$list);
   	   $this->display();
   }
   /*
    *获取查询的搜索的参数 
    *@author 建强
    *@params GET 
   */   
   private function getSearchParam()
   {
   	     $where=[];      
   	     $time=trim(I('timePeriod'));
   	     $orderNum=trim(I('order_num'));
        
   	     if (!empty($time))
   	     {
   	     	 $where['o.add_time']=['gt',$this->getTimes($time)];
   	     }
   	     if(!empty($orderNum))
   	     {   
   	     	$where['t.order_num']=['like','%'.$orderNum.'%'];
   	     }
   	     return $where;
   }
   /**
    * @method  获取用户id 
    * @param   Username
    * @return  int Uid 
   */
   private function getUidByName($name)
   {
        $ret=M('User')->where(['username'=>$name])->field('uid')->find();   	    
        return  isset($ret['uid'])?$ret['uid']:-1;
   }
   /**
    * @param string $orderNum
    * @return int ID
    */
   private function getorderIdByorderNum($orderNum)
   {
   	   $ret=M('TradeTheLine')->field('id')->where(['order_num'=>$orderNum])->find();
   	   return $ret['id']?$ret['id']:0;
   }
   
   /**
    * @param  int $period
    * @return int 时间戳
    */
   private function getTimes($period)
   {
         $date=date('Y-m-d',strtotime("-90 days"));
	   	 switch ($period) {
	   		case 1:
	   			//最近一个星期
	   			$date=date('Y-m-d',strtotime("-7 days"));
	   			break;
	   		case 2:
	   			//最近一个星期
	   			$date=date('Y-m-d',strtotime("-30 days"));
	   			break;
	   		case 3:
	   			//最近一个星期
	   			$date=date('Y-m-d',strtotime("-90 days"));
	   			break;
	   	}
	   	return strtotime($date);
   }
}