<?php
namespace Back\Controller;
use Back\Tools\Page;
use Back\Sms\Yunclode;
/**
 * @author 建强  2018年5月21日16:45:08 
 * @method 用户身份验证类 -发送短信验证码验证本人
 */
class UserAuthController extends BackBaseController
{
	//用户列表 
    public function index()
    {
    	$email   = trim(I('email'));
    	$username= trim(I('username'));
    	$phone   = trim(I('phone'));
    	$uid     = trim(I('uid'));
    	if(!empty($email))
    	{
    		$where['email'] = array('like','%'.$email.'%');
    	}
    	if(!empty($username))
    	{
    		$where['username'] = array('like','%'.$username.'%');
    	}
    	if(!empty($phone) ){
    		$where['phone'] = array('like','%'.$phone.'%');
    		$this->assign('phone',$phone);
    	}
    	if(!empty($uid))
    	{
    		$where['uid'] = $uid;
    	}
    	
    	$count  =  M('User')->where($where)->count();// 查询满足要求的总记录数
    	$Page   = new Page($count,20);// 实例化分页类 传入总记录数和每页显示的记录数(25)
    	$show   = $Page->show();// 分页显示输出
    	$list   =  M('User')
		    	  ->where($where)
		    	  ->order("uid desc")
		    	  ->limit($Page->firstRow.','.$Page->listRows)
    	          ->select();
    	$this->assign('list',$list);
    	$this->assign('page',$show);
    	$this->display();
    }
    
    /**
     * @author 建强  2018年5月21日17:39:28 
     * @method 短信发送 api 
     */
    public function AjaxgetPhoneCode()
    {
         if(IS_AJAX)	
         {
         	 $uid=trim(I('uid'));
         	 $code=trim(I('code'));
         	 if(!is_numeric($uid) || empty($code))
         	 {
         	     return $this->ajaxReturn(['info'=>'參數錯誤','code'=>201]);
         	 }
         	 $res=M('User')->find($uid);
         	 if(!$res)
         	 {
         	 	return $this->ajaxReturn(['info'=>'用戶不存在','code'=>203]);
         	 }
         	 $smsModel=new Yunclode();
         	 $result=$smsModel->authUserApi($res['om'].$res['phone'],$code);
         	 
         	 if(!$result)
         	 {
         	     return $this->ajaxReturn(['info'=>'短信發送失败','code'=>202]);
         	 }
         	 
         	 if(is_array($result) && $result['code']==0)
         	 { 
         	 	return $this->ajaxReturn(['info'=>'短信發送成功','code'=>200]);
         	 }
         	 else 
         	 {
         	    return $this->ajaxReturn(['info'=>'請勿頻繁發送','code'=>204,'data'=>$result]);
         	 }
         }
    }
}