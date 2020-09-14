<?php
/**
 * Created by PhpStorm.
 * User: wangfuw
 * Date: 2020/5/11
 * Time: 0:14
 */

namespace Back\Controller;

use \Back\Tools\Page;
class TeamController extends BackBaseController
{
    //todo -- 团队列表
    public function index(){
        $where = $this->getParams();
        $team_list = $this->getTeamList($where);
        foreach ($team_list['list'] as &$v){
            $v['s_username'] =  D('User')->getUserNameByUid($v['master_id']);
        }
        $this->assign('team_list',$team_list['list']);
        $this->assign('page',$team_list['page']);
        $this->assign('active_time',$this->getQueryTimeStr());
        $this->display();
    }

    protected function getParams(){
        $where = [];
        $uid = trim(I('uid'));
        $username = trim(I('username'));
        $s_username = trim(I('s_username'));
        $active_time = I('active_time')?trim(I('active_time')):1;//默认查询过去一周的数据
        if($uid){
            $where['m.user_id'] = $uid;
        }
        if($active_time>0){
            $where['m.active_time'] = ['gt',$this->getQueryTime($active_time)];
        }

        if($username){
            $where['m.user_id'] =  D('User')->getUidByUname($username);
        }
        if($s_username){
            $where['m.master_id'] = D('User')->getUidByUname($s_username);
        }
        return $where;
    }
    protected function getTeamList($where){
        $count = M('UserDetail')->alias('m')->where($where)->count();
        $Page  = new Page($count,20);
        $show  = $Page->show();// 分页显示输出
        $list = M('UserDetail')->alias('m')->where($where)
            ->join('left join __USER__ as u on u.uid = m.user_id')
            ->field('u.username,m.*')
            ->order("m.active_time desc ")
            ->limit($Page->firstRow.','.$Page->listRows)->select();

        return ['list'=>$list,'page'=>$show];
    }

    private function getQueryTimeStr(){
        $str = [
            ['id'=>'1','name'=>'過去一周'],
            ['id'=>'2','name'=>'過去两周'],
            ['id'=>'3','name'=>'過去一个月'],
            ['id'=>'4','name'=>'過去半年'],
            ['id'=>'5','name'=>'過去一年'],
            ['id'=>'6','name'=>'所有數據'],
        ];
        return $str;
    }

    private function getQueryTime($str){
        switch ($str){
            case 1:
                $time=strtotime('-7 day');//过去一周
                break;
            case 2:
                $time=strtotime('-14 day');//过去一周
                break;
            case 3:
                $time=strtotime('-1 month');//过去一周
                break;
            case 4:
                $time=strtotime('-6 month');//过去一周
                break;
            case 5:
                $time=strtotime('-1 year');//过去一周
                break;
            case 6:
                $time=0;
                break;
            default:
                $time=strtotime('-7 day');//过去一周
        }
        return $time;
    }
}