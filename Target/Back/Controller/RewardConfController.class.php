<?php

namespace Back\Controller;

use Back\Model\RewardLogModel;
use Back\Tools\Page;
use Think\Model;

class RewardConfController extends BackBaseController
{
    public function index()
    {
        $model = M('RewardConfig');

        $count = $model->count();
        $Page = new Page($count, 10);
        $show = $Page->show();

        //改成按照 id倒序
        $list = (clone $model)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function create()
    {
        if ($_POST) {
            $data['pro_name'] = I('pro_name', null);
            $data['pro_content'] = I('pro_content', null);
            $data['lv1'] = I('lv1', null);
            $data['lv2'] = I('lv2', null);
            $data['lv3'] = I('lv3', null);
            $data['lv4'] = I('lv4', null);
            $data['lv5'] = I('lv5', null);

            $res = M('RewardConfig')->add($data);
            if ($res) {
                $this->success('添加成功', '/Back/RewardConf/index.html', 1);
                exit();
            }
            $this->error('添加失敗');
            exit();
        }
        $this->display();
    }

    public function update()
    {
        if ($_POST) {
            $where['id'] = I('get.id');

            $data['pro_name'] = I('pro_name', null);
            $data['pro_content'] = I('pro_content', null);
            $data['lv1'] = I('lv1', null);
            $data['lv2'] = I('lv2', null);
            $data['lv3'] = I('lv3', null);
            $data['lv4'] = I('lv4', null);
            $data['lv5'] = I('lv5', null);

            $model = M('RewardConfig')->where($where);
            $res = $model->save($data);
            if ($res) {
                $this->success('修改成功', '/Back/RewardConf/index.html', 3);
                exit();
            }
            $this->error('修改失敗');
            exit();
        }
        $where['id'] = (int)I('get.id');
        $row = M('RewardConfig')->where($where)->find();
        $this->assign('reward_conf', $row);

        $this->display();
    }

    public function delete()
    {
        $id = I('id', null);
        if (empty($id)) return $this->error('请选择删除的奖励配置');
        $where['id'] = $id;
        $res = M('RewardConfig')->where($where)->delete();
        if ($res) return $this->success('刪除成功');
        return $this->error('刪除失敗');
    }

    /**
     * 统计
     */
    public function statistics()
    {
        $type = I('get.type', RewardLogModel::TYPE_30U);//奖金来源类型
        $rewardType = I('get.reward_type', RewardLogModel::REWARD_TYPE);//奖励类型

        if (!in_array($type, [
            RewardLogModel::TYPE_30U, RewardLogModel::TYPE_3000U
        ])) {
            return $this->error('奖金来源类型不正确');
        }

        if (!in_array($rewardType, [
            RewardLogModel::REWARD_TYPE, RewardLogModel::REWARD_TYPE_POINT
        ])) {
            return $this->error('奖励类型不正确');
        }

        $model = new Model();
        $tablePrefix = 'hvc_reward_log';
        $union = [];
        for ($i = 0; $i <= 9; $i++) {
            $tableName = $tablePrefix . $i;
            $sql = "SELECT rl.user_id, rl.type, rl.reward_type, SUM(rl.num) AS num FROM {$tableName} AS rl WHERE rl.currency_id = 1 AND type = {$type} AND reward_type = {$rewardType} GROUP BY rl.user_id";
            array_push($union, $sql);
        }
        $sql = "SELECT r.user_id, SUM(r.num) AS num, r.type, r.reward_type, u.username, u.nickname FROM ";
        $sql .= '(' . join(' UNION ALL ', $union) . ") AS r LEFT JOIN hvc_user AS u ON u.uid = r.user_id GROUP BY user_id";

        $data = $model->query($sql);

        $this->assign('type', $type);
        $this->assign('reward_type', $rewardType);
        $this->assign('data', $data);

        $this->display();
    }
}