<?php
namespace Back\Controller;
use Common\Api\RedisIndex;
/**
 *  后台登出控制器 登出操作必须是用户登录之后才可以操作
 *  @athor 宋建强  2017年10月13日 16：07
*/
class  LogoutController  extends BackBaseController
{
       
       /**
        * @author 建强  2018年9月18日15:26:06
        * @method  退出登录操作   
       */
	   public function logout()
	   {    
	       $redis         = RedisIndex::getInstance();
	       $userInfo      = $redis->getSessionValue('user');
	       $userInfo['id']= 0;
	       $redis->setSessionRedis('user', $userInfo);
	       return $this->success('退出成功', U('/Back/Login/showLogin'));
	   }
}