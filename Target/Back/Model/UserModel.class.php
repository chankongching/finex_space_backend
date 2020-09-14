<?php
/**
 * Created by PhpStorm.
 * User: 李江
 * Date: 2017/10/11
 * Time: 15:22
 * 用户模型类
 */

namespace Back\Model;
use Think\Model;

class UserModel extends Model{
   
	
    public function checkUserFiledExist($key,$value){
        $r = $this->where(array($key=>$value))->find();
        return $r?true:false;
    }

    public function getUserByUid( $uid ){
        return $this->where( ['uid'=>$uid] )->find();
    }

    public function getUidByUname($uname)
    {
          $ret=$this->where( ['username'=>$uname] )->find();
          return $ret['uid']?$ret['uid']:0;
    }
    public function getUserNameByUid($uid){
            $info = M('User')->where(array('uid'=>$uid))->find();
            return $info['username'];
    }
}