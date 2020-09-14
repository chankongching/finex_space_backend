<?php

namespace Back\Controller;
use Back\Tools\Page;
use Common\Api\RedisIndex;

class IcoFinancesController extends BackBaseController
{
    public function showIcoFinances()
    {
        $where = $this->getParams();
        $redis=RedisIndex::getInstance();
        $redis_data = $redis->getSessionValue("user");
        //查詢該用戶是否是代理商
        $agent =D("auth_group_access")->where("uid = {$redis_data['id']} and group_id = 11")->find();
        if ($agent){
            $admin_data = D("admin_user")->where("id = {$redis_data['id']} ")->find();
            if (empty($where)){
                $array["u.invite_code"] = $admin_data["invite_code"];
                $where = $array ;
            }else{
                $where["u.invite_code"] = $admin_data["invite_code"];
            }
        }

        //总记录数
        $Finance = M('user_ico_finance');
        $count =$Finance
            ->alias('a')
            ->join(' left join trade_user as u on  a.user_id = u.uid')
            ->where($where)
            ->count();// 查询满足要求的总记录数
        $Page = new Page($count, 15);// 实例化分页类 传入总记录数和每页显示的记录数(25)



        //分页查询
        $data = M("user_ico_finance")
            ->alias('a')
            ->field("id,user_id,u.username,u.invite_code,q_name,q_num,currency_id,dh_num,ico_user_num,add_time,a.status")
            ->join(' left join trade_user as u on u.uid = a.user_id')
            ->where($where)
            ->order(" add_time desc ")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();

        //dump($data);


        $where['a.id'] = array('gt', 0);
        $invite_code = I('get.invite_code');///邀请码
        if (!empty($invite_code)) {
            $where['u.invite_code'] = $invite_code;
        }

        $jiaoyi = M('user_ico_finance')
            ->alias('a')
            ->field('a.currency_id,c.currency_name,Sum(a.dh_num)as c_num')
            ->join('left join trade_user as u on a.user_id = u.uid right join trade_currency as c on a.currency_id = c.id')
            ->group('c.id')
            ->where($where)
            ->select();
        foreach ($jiaoyi as $key => $value) {
            if (empty($value['currency_id'])) {
                unset($jiaoyi[$key]);
            }
        }
        $jiaoyi = array_merge($jiaoyi);///重置索引
        $show = $Page->show();// 分页显示输出
        // dump($jiaoyi);
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('jiaoyi', $jiaoyi);
        $this->assign('agent',count($agent));// 传入前端判断是否是代理商
        $this->assign('datatable',$data);
        $this->display();
    }

    protected function getParams()
    {
        $where = [];
        $user_id = I('uid');
        if (!empty($user_id)){
            $where['a.user_id'] = $user_id;
        }
        $invite_code = I('get.invite_code');
        if (!empty($invite_code)){
            $where['a.invite_code'] = $invite_code;
        }
        $star_time = strtotime(trim(I('start_time')));
        $end_time = strtotime(trim(I('end_time')));
        if (!empty($star_time)&&!empty($end_time)){
            $where["add_time"] = ["between",[$star_time,$end_time]];
        }

        return $where;
    }
}