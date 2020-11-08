<?php
namespace Back\Controller;
use Think\Controller;
use Common\Api\RedisIndex;
/**
 * @method 后台基础类控制器  继承框架基类
 * @author 宋建强  2017年9月25日 17：37
 */
class BackBaseController extends Controller
{
	CONST EXPIRE_TIME=14400;     //两次操作间隔时间
	//保存后台用户登录信息
	public $back_userinfo;
	//定义无需要分配权限即可操作
	public  $arrRoute=[
		'/Back/Index/Logout',
		'/UserInfo/UserInfoAbout'
	];
	/**
	 * 初始化方法
	*/
	public function _initialize()
	{
		parent::_initialize();
	    $this->checkUserLogin();
	    $this->getAuth();
		$nav_data=D('AdminNav')->getTreeData('level','order_number,id',$this->back_userinfo);
        $this->assign("userinfo",$this->back_userinfo);
		$this->assign('back_data',$nav_data);
	}

    protected function checkUserLogin()
    {
	   	$obj_redis=RedisIndex::getInstance();
	   	$back_userinfo=$obj_redis->getSessionValue('user');
	   	if(!isset($back_userinfo['id']) ||  empty($back_userinfo['id']))
	   	{
	   		$this->redirect('/Back/Login/showLogin');
	   	}

	   	$intevalTime  = $back_userinfo['back_expire'];
	   	$twoActionTime= self::EXPIRE_TIME;
	   	if(time()-$intevalTime>$twoActionTime)
	   	{
	   		$obj_redis->delSessionRedis('user');
	   		return  $this->error('您長時間未操作','/Back/Login/showLogin');
	   		exit;
	   	}

	   	//判斷賬號狀態
	   	$ret = M('AdminUser')->where(['id'=>$back_userinfo['id']])->find();
	   	if(empty($ret) || $ret['status']==0)
	   	{
	   	    $obj_redis->delSessionRedis('user');
	   	    return  $this->error('該賬號已刪除','/Back/Login/showLogin');
	   	}
	    //重设登陆信息
	    $back_userinfo['back_expire']=time();
	    $obj_redis->setSessionRedis('user', $back_userinfo);
	    $this->back_userinfo=$back_userinfo;
	    return true;
    }
  /**
    * 获取用户操作菜单
   */
   protected function getAuth()
   {
	   	$auth=new \Think\Auth();
	   	$rule_name=MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
	   	//不检验的权限
	   	if (!in_array($rule_name, $this->arrRoute))
	   	{
	   		$result=$auth->check($rule_name,$this->back_userinfo['id']);
	   		if(!$result)
	   		{
	   			if(IS_AJAX)
	   			{
	   				return $this->ajaxReturn(['info'=>'您沒有訪問權限','status'=>403]);
	   			}
	   			return  $this->error('您沒有權限訪問');
	   		}
	   	}
	   	return true;
   }

   /** 定义ajax返回数据的格式
	 * @author 宋建强 2017年9月27日
	*/
    protected function ajaxReturnApi($status=200, $info='',$data='')
    {
	  	 $arr = array('status' => $status,'info'=> $info,'result'=>$data);
	  	 $this->ajaxReturn($arr);
    }


   /**
    * 日志记录保留最近半年的记录 日志会触发这个方法
    * @author 建强   2017年12月8日12:18:06
    * @param string  $tableName  表名注意正确写法  User UserFinance
    * @param string  $field      数据库字段名称
    * @param int     $month
    * @param count   分表尾数              分表请传3
    */
    public  function DelLog($tableName,$count=1,$field='add_time',$month=6)
    {
         $where=[];
    	 $ret=rand(1,10);
    	 if ($ret<6)
    	 {
    	 	return true ;
    	 }

    	 if($count==3)
    	 {
    	 	//分表的日志删除
    	 	for($i=0;$i<=$count;$i++)
    	 	{
    	 		$ret=M($tableName.$i)->field($field)->order('id desc')->find();
    	 		if($ret[$field])
    	 		{
    	 			$add_time=$ret[$field]-($month*30*24*60*60);
    	 			$where[$field]=['lt',$add_time];
    	 			M($tableName.$i)->where($where)->delete();
    	 		}
    	 	}
    	 }
    	 else
    	 {
    	    $ret=M($tableName)->field($field)->order('id desc')->find();
    	    if ($ret[$field])
    	    {
    	    	$add_time=$ret[$field]-($month*30*24*60*60);
    	    	$where[$field]=['lt',$add_time];
    	    	M($tableName)->where($where)->delete();
    	    }
    	 }
    	return true;
    }

    /**
     * 用户money 日志
     * @author zhangxiwen
     * @return boolean   true false
     */
    public function addUserMoneyLog($data){
        $user = M('User')->where(['uid'=>$data['user_id']])->field('username')->find();
        $data['username'] = $user['username'];
        $obj_redis = RedisIndex::getInstance();
        $adminInfo = $obj_redis->getSessionValue('user');
        $data['admin_user'] = $adminInfo['username'];
        $data['add_time'] = time();
        return M('Change_user_money_log')->add($data);
    }

    /**
     * 用户币种
    */
    protected function getCurrencyById($id){
        $data = M('Currency')->where(['id'=>$id])->field('currency_name')->find();
        if(isset($data['currency_name']))
            return $data['currency_name'];
        return '';
    }
}
