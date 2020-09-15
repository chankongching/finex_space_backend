<?php
namespace Back\Controller;
use think\Exception;
/**
 * 后台权限管理 | 管理员列表
 * @author 宋建强
 * 
 */
class RuleController extends BackBaseController{

//******************权限***********************//
    /**
     *@method  显示权限列表
    */
    public function index(){
        $data = D('AuthRule')->getTreeData('tree','id','title');
        $assign = array(
            'datatable'=>$data
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * @method 添加权限    AuthRule   添加顶级权限和子级权限
     */
    public function add()
    {
    	if(IS_POST)
    	{
    		$data=I('post.');
    		if(!empty(trim($data['title']))  && !empty(trim($data['name'])))
    		{
    			 $res=M('AuthRule')->where(['name'=>trim($data['name'])])->find();
    			 if($res)
    			 {
    			 	 $this->error('該權限的名稱存在，請更換');
    			 	 exit();
    			 }
    			 D('AuthRule')->addData($data);
    			return  $this->success('添加成功',U('Back/Rule/index'));
    		}
    	} 
    	$this->redirect('Back/Rule/index');
    }

    /**
     * 修改权限
     */
    public function edit(){
        $data=I('post.');
        $map=array(
            'id'=>$data['id']
        );
        D('AuthRule')->editData($map,$data);
        $this->success('修改成功',U('Back/Rule/index'));
    }

    /**
     * @method 删除权限
     */
    public function delete(){
        $id=I('get.id');
        $map=array(
            'id'=>$id
            );
        $result=D('AuthRule')->deleteData($map);
        if($result)
        {
            $this->success('刪除成功',U('Back/Rule/index'));
        }
        else
        {
            $this->error('請先刪除子權限');
        }
    }
	
	/**
	 * @method js 部分获取json权限数据修改
	 */
	public function getAuthAjax(){
		$id=I('post.id');
		$data = D('AuthRule')->where(array('id'=>$id))->find();
		die(json_encode($data));
	}
	/**
	 * js部分获取json导航菜单数据
	 */
	public function getNavAjax(){
		$id=I('post.id');
		$data = D('AdminNav')->where(array('id'=>$id))->find();
		die(json_encode($data));
	}
	
	/**
	 * js部分获取json权限组的数据进行的修改
	 */
	public function getGroupAjax(){
		$rule_data=D('AuthGroup')->where(array('id'=>$_POST['id']))->find();
		 die(json_encode($rule_data));
	}
	
//*******************管理员列表**********************//
    /**
     * @method 添加用户组 
     */
    public function add_group(){
             
        if(IS_POST)	
        {  	
	    	$data=I('post.');
	        if(!empty($data['title'])) 
	        {   
	        	 //如果该组名已经存在则不能添加
	        	 $res=M('AuthGroup')->where(['title'=>trim($data['title'])])->find();
	        	 if($res)
	        	 {
	        	 	$this->error('該組名已經存在，請更換組名添加');
	        	 	exit();
	        	 }
	        	 D('AuthGroup')->addData($data);
	        	 $this->success('添加成功',U('Back/Rule/admin_user_list'));
	        	 exit();
	        }
        }
        $this->redirect('Back/Rule/admin_user_list');
    }

    /**
     *@method  修改用户组
     */
    public function edit_group(){
        $data=I('post.');
        $map=array(
            'id'=>$data['id']
            );
        D('AuthGroup')->editData($map,$data);
        $this->success('修改成功',U('Back/Rule/admin_user_list'));
    }

    /**
     * @method 删除用户组   注意该组下面存在用户  则不能删除 
     * @desc   刪除權限組  暫時不能刪除  2018年11月9日12:27:44 (注釋掉)
     * 
     */
    public function delete_group()
    {  
//         exit();
//         $id=I('get.id');
//         $map=array(
//             'id'=>$id
//         );
//         $result =D('AuthGroup')->deleteData($map);
//         if($result)
//         {
//         	$this->success('刪除成功',U('Back/Rule/admin_user_list'));
//         	exit();
//         }
//         else
//         {
//         	$this->error('請先刪除組成員');
//         }
    }
  
//*****************权限-用户组*****************//
    /**
     * @author 宋建强  
     * @method 分配权限  给组分配进行权限进行分配
    */
    public function rule_group(){
        if(IS_POST)
        {
            $data=I('post.');
            $map=array(
                'id'=>$data['id']
            );
            $data['rules']=implode(',', $data['rule_ids']);
            D('AuthGroup')->editData($map,$data);
            $info_data['info'] = "操作成功";
            $info_data['id'] = $data['id'];
            $info_data['status'] = 1;
            $this->ajaxReturn($info_data);
        }
        else
        {
            $id=I('get.id');//ID是角色id
            // 获取用户组数据
            $group_data=M('Auth_group')->where(array('id'=>$id))->find();
            $group_data['rules']=explode(',', $group_data['rules']); //该角色的所有权限id的分割
            // 获取规则数据
            $rule_data=D('AuthRule')->getTreeData('level','id','title');
            
            $auth_id_data = M('AuthRule')->getField('id',true);///获取ID字段 一维数组
            //获取用户权限，转换为一维数组
            $auth_group_data = M('AuthGroup')->where(array('id'=>$id))->getField('rules');
            $auth_data = explode(",", $auth_group_data);//将字符串转换为数组
            $assign=array(
                'group_data'=>$group_data,
                'rule_data'=>$rule_data,
                'auth_rule_id'=>$auth_id_data,
                'user_auth_data' => $auth_data,
                'group_id' =>$id
            );
            $this->assign($assign);
            $this->display();
        }
    }

    /*
     * @author 宋建强
     * 获取用户的信息 ajax
     * @param  id
     * return json
     */
    public function getUserInfoAjax()
    {
    	$id=I('post.id');
    	$user = D('AdminUser');
    	$sql = "INNER JOIN trade_auth_group_access ON trade_admin_user.id = trade_auth_group_access.uid AND trade_auth_group_access.uid ='".$id."'";
    	$list = $user->join($sql)->find();
    	$this->ajaxReturn($list);
    }
    
   /**
    *@author 建强   2018年9月20日12:34:44
    *@method 删除管理员账号 超级管理员不允许删除 
    *@desc   修改成禁用賬號  2018年11月9日12:28:48 
    *
   */
   public  function delete_admin_user()
   {
   	   $uid   =(int)I('id'); 
   	   $status=(int)I('status'); 
   	   if($uid<0 || $uid==88)
   	   {
   	      return $this->error('操作失敗',U('Back/Rule/admin_user_list'));
   	   }
   	   //如果现在是禁用的  准备开启账号
   	   if($status==0)
   	   {
   	       $ret = $this->startUserAccoutn($uid);
   	       if($ret) return $this->success('操作成功',U('Back/Rule/admin_user_list'));
   	       return $this->error('操作失敗',U('Back/Rule/admin_user_list'));
   	   }
   	   //账号是正常 status =1
   	   $ret=$this->delUserInfo($uid);
   	   if($ret) return $this->success('操作成功',U('Back/Rule/admin_user_list'));
   	   return $this->error('操作失敗',U('Back/Rule/admin_user_list'));
   }
   /**
    * @author 建强 2018年11月9日14:59:26
    * @method 启用账号 
    * @param int $uid
    * @return bool 
    */
   protected function startUserAccoutn($uid)
   {
       $data =[
           'status'=>1,
           'register_time'=>time(),
       ];
       //btcs后台
       M()->startTrans();
       $ret = M('AdminUser')->where(['id'=>$uid])->save($data); 
       if($ret) $sysRet =M('AdminUser', 'work_', 'DB_SYS')->where(['user_id'=>$uid])->save($data);
       if($sysRet) 
       {
           M()->commit();
           return true;
       }
       M()->rollback();
       return false;
   }
   /**
    * @method 删除用户信息
    * @param int $uid
    * @return boolean
   */
   protected function  delUserInfo($uid)
   {
       $data =[
           'status'=>0,
           'register_time'=>time(),
       ];
       try{
           //btcs 賬號禁用
           $ret =  M('AdminUser')->where(['id'=>$uid])->save($data);  
           
           $data['duty'] = 0; //工單系統下班
           if($ret==false)  return false;
           //工單系統賬號禁用
           M('AdminUser', 'work_', 'DB_SYS')->where(['user_id'=>$uid])->save($data);
           //工單系統權限不刪   問題分組的status =0
           M('ProblemUser', 'work_', 'DB_SYS')->where(['user_id'=>$uid,'status'=>0])->save(['status'=>1]);
           //如果存在待處理的訂單全部放到搶單池
           $this->checkDealOrderbyUid($uid);
           return true;
       } 
       catch (Exception $e) 
       {
           return false;
       }
   }
   
   /**
    * @author 建强 2018年11月9日14:08:52
    * @method 禁用账号  转接整在处理的订单  
    * @return boolean
   */
   protected function checkDealOrderbyUid($user_id) 
   {
       $where = [
           'custom_uid'=>$user_id,
           'status'    =>2
       ];
       $orders  = M('Feedback', 'work_', 'DB_SYS')->where($where)->select();
       if(empty($orders)) return true;  
       
       //获取工单问题组
       $feedTitle = M('Feed', 'work_', 'DB_SYS')->field('id,tw_title')->where(['pid'=>0])->select();
       $feedTitle = array_column($feedTitle, 'tw_title','id');
       //获取用户名
       $userInfo  = M('AdminUser', 'work_', 'DB_SYS')->field('username')->where(['user_id'=>$user_id])->find();  
       //订单批量进行修改  
       $feedIds = array_column($orders, 'id');
       //组装工单系统转接日志 
       $logData = [];
       foreach ($orders as $k=> $order)
       {
           $logData[$k]['feedback_id'] = $order['id'];
           $logData[$k]['problem_gid'] = $order['f_pid'];
           $logData[$k]['level_id']    = $order['level_id'];
           $logData[$k]['from_uid']    = $order['custom_uid'];
           $logData[$k]['describe']    = $userInfo['username'].'指派至 '.$feedTitle[$order['f_pid']].'小組';
           $logData[$k]['add_time']    = time();
       }       
       $whereUpdate = [
           'id'=> ['IN',$feedIds]
       ]; 
       $whereData =[
           'custom_uid'=>0,        //重置 
           'status'    =>4,        //转介中
           'assign_uid'=>$user_id, //注明谁转介
       ];
       //执行sql
       $ret = [];
       M('Feedback', 'work_', 'DB_SYS')->startTrans();
       $ret[] = M('Feedback', 'work_', 'DB_SYS')->where($whereUpdate)->save($whereData);
       $ret[] = M('FeedbackLog', 'work_', 'DB_SYS')->addAll($logData);
       if(in_array(false, $ret)) 
       {
           M('Feedback', 'work_', 'DB_SYS')->rollback();
           return false;
       }
       M('Feedback', 'work_', 'DB_SYS')->commit();
       return true;
   }
    /**
     * @method  管理员列表展示页面
     * @access admin_user_list
     * @author 建强 2018年9月11日17:23:12 
    */
    public function admin_user_list()
    {
        $uid=$this->back_userinfo['id'];
        $groupId=M('AuthGroupAccess')->where(['uid'=>$uid])->getField('group_id');
        $data=D('AuthGroup')->select();
        if($groupId>1)
        {
            unset($data[0]);
            $this->assign('disp',1);
        }
        //后台登录用户  newtrade_auth_group_access用户用户组对应关系表
        $user = D('AdminUser');
        // 获取用户组和用户
        foreach( $data as $k=>$d ){
            $list = M('AdminUser')
                ->alias('au')
                ->join('__AUTH_GROUP_ACCESS__ ag ON au.id=ag.uid')
                ->where(array('ag.group_id'=>$d['id']))
                ->select();
            $data[$k]['son'] = $list;
        }
        
        
        $assign=array(
            'datatable'=>$data
        );
        $this->assign($assign);
        $this->display();
    }
    /**
     * @author 建强  2018年9月20日11:25:40 添加管理员
     * @method 添加管理员 
    */
    public function add_admin()
    {
           $username= trim(I('username'));
           $groupId = I('group_ids');
           $phone   = trim(I('phone'));
           $om      = I('om');
           $status  = I('status');
           
           if(!is_numeric($phone))  return $this->error("電話號碼格式不正確");
           if(empty($username))  return $this->error("用戶名不能為空");
           $ret = M('AdminUser')->where(['username'=>$username])->find();
           if($ret) return $this->error('用戶名被佔用');
           $data=[
               'username'    =>$username,
               'phone'       =>$phone,
               'om'          =>$om,
               'status'      =>$status,
              'register_time'=>time(),
           ]; 
         $ret= $this->addAdminUser($data,$groupId);   
         if($ret) return $this->success('添加成功',U('Back/Rule/admin_user_list'));
         return  $this->error('添加失敗',U('Back/Rule/admin_user_list'));
    }
    
    /**
     * @param array $data
     * @param int   $groupId
     * @method 添加管理員
     */
    protected function addAdminUser($data,$groupId)
    {  
         $res = []; 
         M()->startTrans();
         $uid  =  D('AdminUser')->addData($data);
         $res[]=  $uid;
         $groupData=[
             'uid'=>$uid,
             'group_id' =>$groupId
         ];
         $res[]= D('AuthGroupAccess')->addData($groupData);
         $this->addSysOrder($data, $uid);
         if(in_array(false, $res))
         {
             M()->rollback();
             return false;
         }
         M()->commit();
         return true;
    }
    /**
     * @author 建强  2018年9月20日11:18:51 
     * @method 后台账号同步
    */
    protected function addSysOrder($data,$uid)
    {
        $sysData=$data;
        $sysData['user_id']=$uid;
        M('AdminUser', 'work_', 'DB_SYS')->add($sysData);
    }
    
    /**
     * @method 修改管理员  修改管理員密碼  System同步修改
     * @author 建強  2018年9月20日12:04:50
     * @desc   去掉修改管理员状态
     */
    public function edit_admin()
    {
            $uid     = trim(I('id'));
            $username= trim(I('username'));
            $groupId = I('group_ids');
            $phone   = trim(I('phone'));
            $password= trim(I('password'));
            $om      = I('om');
            if(!empty($phone) && !is_numeric($phone))   return $this->error("電話號碼格式不正確");
            if(empty($username)) return $this->error("用戶名不能為空");
            if(empty($uid)) return $this->error('用户id不存在');
            //查看用戶名是否佔用
            $where = [
                'username'=>$username,
                'id'      =>['NEQ',$uid],
            ];
            $ret =M('AdminUser')->where($where)->find();
            if($ret)  return $this->error('該用戶名已經存在');
            
            $data=[
                'username'=>$username,
                'phone'   =>$phone,
                'om'      =>$om,
            ]; 
            if(!empty($password)) $data['password']=passwordEncryption($password);
            $group=[
                'group_id'=>$groupId[0],
            ];
            $map=array(
                'id'=>$uid
            );

            $ret = $this->editUserInfo($data, $group, $map);
            if($ret)  return $this->success('編輯成功',U('Back/Rule/admin_user_list'));
            return $this->error('編輯失敗',U('Back/Rule/admin_user_list'));
       }
       //头部修改管理员密码
        public function update_pass()
    {
            $pass = I("password");
            if (empty($pass)){
                return $this->error("密码不能为空");
            }
            $res = M('AdminUser')->where(['id'=>$this->back_userinfo['id']])->save(["password"=>passwordEncryption($pass)]);
            if ($res){
                return $this->success("修改成功");
            }
    }
       /**
        * @param array $data
        * @param array $group
        * @param array $map
        * @method 修改用户信息
        */
       protected function editUserInfo($data,$group,$map)
       {  
           $res=[];
           $where=[
               'uid'=>$map['id']
           ];
           try{
               M()->startTrans();
               D('AuthGroupAccess')->where($where)->save($group);
               $res[] = D('AdminUser')->editData($map,$data);
           } 
           catch (Exception $e) 
           {
               $res[]=false;
           }
           if(in_array(false, $res))
           {
               M()->rollback();
               return false;
           }
           M()->commit();
//           $this->editSysOrder($data,$map);
           return true
               ;
       }
       /**
        * @method 修改system库
        * @param  array $data
        * @param  array  $map
        */
       protected function editSysOrder($data,$map)
       {
           $sysData=[
              'user_id'=>$map['id']
           ];
           $res= M('AdminUser', 'work_', 'DB_SYS')->where($sysData)->find();
           if(empty($res)) return true; 
           $ret=M('AdminUser', 'work_', 'DB_SYS')->where($sysData)->save($data);
           return $ret;
       }
}