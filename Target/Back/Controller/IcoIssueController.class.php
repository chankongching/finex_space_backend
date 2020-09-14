<?php
/**
 * Created by PhpStorm.
 * User: 12531
 * Date: 2020/5/29
 * Time: 17:40
 */

namespace Back\Controller;


use Back\Tools\Page;

class IcoIssueController extends BackBaseController
{
    public function index(){

        //查詢該用戶是否是代理商
        $userInfoList = $this->getIssueInfo('Ico_issue', 'id desc');
        $this->assign('list',$userInfoList['list']);// 赋值数据集
        $this->assign('page',$userInfoList['page']);// 赋值分页输出
        $this->display();
    }

    public function add_issue()
    {
        if (IS_POST){
            $issue_num= trim(I('issue_num'));
            $rate = I('rate');
            $q_name   = trim(I('q_name'));
            $q_num     = I('q_num');
            $star_time  = strtotime(I('star_time'));
            $end_time = strtotime(I("end_time"));
            $exchange_num = I("exchange_num");
            if(empty($issue_num))  return $this->error("发行个数不能為空");
            if(empty($rate))  return $this->error("兑换汇率不能為空");
            if(empty($q_name))  return $this->error("名字不能為空");
            if(empty($q_num))  return $this->error("期数不能為空");
            if(empty($star_time))  return $this->error("开始时间");
            if(empty($end_time))  return $this->error("结束时间");
            $add_data["issue_num"]=$issue_num;
            $add_data["rate"]=$rate;
            $add_data["q_name"]=$q_name;
            $add_data["start_time"]=$star_time;
            $add_data["end_time"]=$end_time;
            $add_data["exchange_num"]=$exchange_num;
            $ret =   M('ico_issue')->add($add_data);
            if($ret) return $this->success('添加成功',U('Back/Rule/admin_user_list'));
            return  $this->error('添加失敗',U('Back/Rule/admin_user_list'));
        }
    }
    public function edit_issue()
    {
        if(IS_POST){
            $id = I('post.id');
            if( empty($id) || $id == 0 ){
                return $this->error('用户不存在');
            }
            $data['issue_num'] = trim(I('post.issue_num'));
            $data['rate'] = I('post.rate');
            $data['q_name'] = trim(I('post.q_name'));
            $data['q_num'] =  I('post.q_num');
            //$data['start_time'] =  I('post.start_time');
            //$data['end_time'] =  I('post.end');
            $data['exchange_num'] =  I('post.exchange_num');


            $r = M('ico_issue')->where(array('id'=>$id))->save($data);

            if( !$r ){
                $this->error('未做任何修改');die;
            }
            $this->success('修改成功',U('IcoIssue/edit_issue/id/'.$id));
        }else{
            //at 頁面渲染  補充備註
            $id = I('id');
            $list = D('ico_issue')->where(['id'=>$id])->find();
            if(!$list)   return $this->error('用護不存在');
            $this->assign('list',$list);
            $this->display();
        }
    }

    /*
 * 李江
 * 2017年10月12日12:17:18
 * 封装查询数据的方法
 */
    protected function getIssueInfo($tableName,$order){
        $tableModel = M($tableName);
        $count = $tableModel->count();
        $Page       = new Page($count,15);
        $show       = $Page->show();// 分页显示输出
        $list = $tableModel->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();

        return ['list'=>$list,'page'=>$show];
    }
}