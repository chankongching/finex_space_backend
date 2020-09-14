<?php
namespace Back\Controller;
use Think\Controller;
use Common\Api\RedisIndex;
use Back\Tools\Utils;
use Back\Sms\Yunclode;
/**
 * @method 后台登录控制器  继承基类控制器
 * @author 宋建强 2017年9月26日 10：00
 */
class LoginController extends Controller 
{  
	
	CONST BACK_LOGIN_SMS='BACK_LOGIN_SMS'; //短信验证码前缀
	/** 
	 * 登陆操作
	*/
	public function showLogin()
	{ 
		$redis=RedisIndex::getInstance();
	    $back_userinfo=$redis->getSessionValue('user');
		if(empty($back_userinfo['id']))
		{   
			$this->display();
			exit;
		}
		$this->redirect('/back/index/index');
	}
	/**
	 * 提交登录表单
	 */
	public function  subLogin()
	{
	   if(IS_AJAX)  
	   {
	   	   $name=trim(I('post.username'));
	   	   $pass=trim(I('post.password'));
	   	   $code=trim(I('post.code'));
	   	   $phoneCode=trim(I('post.phoneCode'));
	   	   
	   	   $this->checkParam($name,$pass,$code,$phoneCode);
	   	   $res=M('AdminUser')->where(['username'=>$name])->find();
	   	   if(!$res)
	   	   {
	   	     	$this->Output(206,'用戶不存在');
	   	   }
	   	   if(passwordVerification($pass, $res['password'])==false)
	   	   {
	   	   	   $this->Output(205,'密碼不正確');
	   	   }
	   	   if ($res['status']==0)
	   	   {
	   	   	   $this->Output(207,'该账号禁止登陆');
	   	   }
	   	   //检验后台用户的短信验证
// 	   	   if(checkSmsCode($res['id'], $res['phone'], self::BACK_LOGIN_SMS, $phoneCode)==false)
// 	   	   {
// 	   	   	   $this->Output(207,'短信驗證碼不正確');
// 	   	   } 

           $session_data=[
               'id'=>$res['id'],
               'username'=>$res['username'],
               'back_expire'=>time(),
           ];
           //设置登录数据
	   	   $redis=RedisIndex::getInstance();
	   	   $redis->setSessionRedis('user',$session_data);
	   	   $this->setAdminLog($res['id'],$res['username']);
	   	   $this->Output(200,'登錄成功',['url'=>U('/back/index/index')]);
	   }
	}
	/**
	  * 写入登陆日志
	  * @param $uid
	 */
	private function setAdminLog($id,$name)
	{    
		 $data=[
		 		'admin_name'=>$name,
		 		'time'=>NOW_TIME,
		 		'admin_id'=>$id,
		 		'info'=>'管理員登錄',
		 		'ip'=>get_client_ip(),
		 ];
	    $ret=  M('AdminLog')->add($data);
	    return $ret;
	}
	/**
	 * 检验登录参数
	*/
	protected  function checkParam($name,$pass,$code,$phoneCode)
	{
		 if(empty($name))
		 {
		 	$this->Output(201,'用戶名不能為空');
		 }
		 if(empty($pass))
		 {
		 	$this->Output(202,'登錄密碼不能為空');
		 }
		 if(empty($code))
		 {
		 	$this->Output(203,'圖片驗證碼不能為空');
		 }
		 if (empty($phoneCode))
		 {
		 	$this->Output(204,'短信驗證碼不能為空');
		 }
		 
		 if($this->checkVerify($code)==false)
		 {
		 	$this->Output(205,'圖片驗證碼不正確');
		 }
	}
	/**
	 * 获取图形验证码
	 * @author 宋建强 2017年9月27日10:40
	 * @return string
	*/
	public function getVerify()
	{
           
		$config =    array(
				'fontSize'  =>  14,           // 验证码字体大小(px)
				'useCurve'  =>  false,        // 是否画混淆曲线
				'useNoise'  =>  true,         // 是否添加杂点
				'imageH'    =>  40,           // 验证码图片高度
				'imageW'    =>  100,          // 验证码图片宽度
				'length'    =>  1,            // 验证码位数
				'fontttf'   => '4.ttf',       // 验证码字体，不设置随机获取
		);
                
		$Verify = new \Common\Api\VerifyApi($config);
		$Verify->entry();
	}
	

	/**
	 * 检测输入的验证码是否正确
	 * @author 宋建强 2017年9月26日10:56:46
	 * @param  string $code 用户输入的验证码字符串
	 * @param  string $id 验证码标识
	 * @return boolean
	 */
	public function checkVerify($code, $id = '')
	{
		$verify = new \Common\Api\VerifyApi();
		return $verify->check($code,$id);
	}
    
	/**
	 * 根据username  发送短信的sms的接口地址
	 * @param $username 
	 */
	 public function  ApiSendSms()
	 {
	 	if (IS_AJAX)
	 	{
	 	   $username=trim(I('username'));
	 	   $pass=trim(I('password'));
	 	   if(empty($username))
	 	   {
	 	   	   $this->Output(404,'用戶名不能為空');
	 	   }
	 	   if (empty($pass))
	 	   {
	 	   	   $this->Output(404,'登錄密碼不能為空');
	 	   }
	 	   $res=M('AdminUser')->where(['username'=>$username])->find();
	 	   if (!$res)
	 	   {
	 	   	   $this->Output(404,'用戶不存在');
	 	   }
	 	   if(passwordVerification($pass, $res['password'])==false)
	 	   {
	 	   	   $this->Output(404,'密碼不正確');
	 	   }
	 	   //后台登录调用发送短信
	 	   $smsModel=new Yunclode();
	 	   $result=$smsModel->ApiSendPhoneCode($res['id'], $res['om'], $res['phone'],self::BACK_LOGIN_SMS,1);
	 	   if($result==0)
	 	   {
	 	   	   $this->Output(200,'短信發送成功，請查看手機');
	 	   } 
	 	   $this->Output(500,'系統繁忙，請稍後再試');
	 	}
	 }
	/**定义接口统一返回方法
	 * @param number $status
	 * @param string $info
	 * @param string $data
	 * return json 
	 */
	protected function Output($status=200, $info='',$data='')
	{
		$arr = array('status' => $status,'info'=> $info,'result'=>$data);
		$this->ajaxReturn($arr);
		//调用此方法后进行 退出程序
		exit(); 
	}
}