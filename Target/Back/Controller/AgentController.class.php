<?php
/**
 * Created by PhpStorm.
 * User: 12531
 * Date: 2020/5/28
 * Time: 14:09
 */

namespace Back\Controller;


class AgentController extends BackBaseController
{
    public function index(){
        $data = D("admin_user")->where("invite_code is not null")->field("id,status,username,nickname,phone,invite_code,register_time")->select();
        $agent_data = D("auth_group")->where("status = 1")->select();
        $assign=array(
            'datatable'=>$data,
            'agent'=>$agent_data
        );
        $this->assign($assign);
        $this->display();
    }

    public function disable()
    {
        if (IS_GET){
            $uid = I('get.id');
            $status = I("get.status");
            if ($status ==1){
                $status = 0;
            }else{
                $status = 1;
            }
            $res = D("admin_user")->where("id = $uid")->save(["status"=>$status]);
            if ($res){
                $this->success("修改成功");
            }
            $this->error("修改失敗");
        }
    }
    //添加代理商
    public function add_agent()
    {
        if (IS_POST){
            $username= trim(I('username'));
            $groupId = I('group_ids');
            $phone   = trim(I('phone'));
            $om      = I('om');
            $status  = I('status');
            $password = I("password");
            if(!is_numeric($phone))  return $this->error("電話號碼格式不正確");
            if(empty($username))  return $this->error("用戶名不能為空");
            if(empty($password))  return $this->error("密碼不能為空");
            $ret = M('AdminUser')->where(['username'=>$username])->find();
            if($ret) return $this->error('用戶名被佔用');
            $data=[
                'username'    =>$username,
                'phone'       =>$phone,
                'om'          =>$om,
                'status'      =>$status,
                'password' => passwordEncryption($password),
                'register_time'=>time(),

            ];
            //如果是代理商
            if ($groupId ==11){
                $data["invite_code"]  = $this->create_invite_code();
            }
            $ret =  $this->addAdminUser($data,$groupId);
            if($ret) return $this->success('添加成功',U('Back/Rule/admin_user_list'));
            return  $this->error('添加失敗',U('Back/Rule/admin_user_list'));
        }
    }
    //修改代理商备注
    public function updateNickname()
    {
        if (IS_POST){
           $save['nickname']  = I("post.nickname");
           $id = I("post.id");
           $res = M("AdminUser")->where("id = $id")->save($save);
           if ($res){
             return   $this->success("修改成功",U("Back/Agent/index"));
           }
           return $this->error("添加失败",U("Back/Agent/index"));
        }
    }
    //添加代理商
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
        //$this->addSysOrder($data, $uid);
        if(in_array(false, $res))
        {
            M()->rollback();
            return false;
        }
        M()->commit();
        return true;
    }
    /**
     * js部分获取json权限组的数据进行的修改
     */
    public function getGroupAjax(){
        $rule_data=D('AdminUser')->where(array('id'=>$_POST['id']))->find();
        die(json_encode($rule_data));
    }


    //生成固定長度邀請碼
    public function create_invite_code() {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0,25)]
            .strtoupper(dechex(date('m')))
            .date('d')
            .substr(time(),-5)
            .substr(microtime(),2,5)
            .sprintf('%02d',rand(0,99));
        for(
            $a = md5( $rand, true ),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            $d = '',
            $f = 0;
            $f < 6;
            $g = ord( $a[ $f ] ),
            $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
            $f++
        );
        return $d;
    }
}