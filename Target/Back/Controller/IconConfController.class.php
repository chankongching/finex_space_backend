<?php

namespace Back\Controller;

use Back\Model\CurrencyModel;

class IconConfController extends BackBaseController
{
    public $arrCoinClass = [
        1 => '壹級市場幣',
        2 => '二級市場幣',
    ];

    /**
     * 添加币种
     */
    public function Create()
    {
        if (IS_POST) {
            $data = I('post.');

            if ($data['currency_name'] == '') {
                return $this->error("币种名称不能为空");
            }

            if ($data['currency_mark'] == '') {
                return $this->error("币种标识不能为空");
            }

            if (!in_array($data['status'], [
                CurrencyModel::STATUS_DISABLED,
                CurrencyModel::STATUS_ENABLED
            ])) {
                return $this->error("币种状态不正确");
            }

            $upload = $this->uploadOne('currency_logo');
            if ($upload['status']) {
                $data['currency_logo'] = $upload['info'];
            }

            $upload = $this->uploadOne('currency_big_logo');
            if ($upload['status']) {
                $data['currency_big_logo'] = $upload['info'];
            }

            $res = M('Currency')->add($data);
            if ($res) return $this->success('添加成功');
            return $this->error('添加失敗');
        }
        $this->display();
    }

    /**
     * 列表页
     */
    public function Index()
    {
        $where = ['status' => CurrencyModel::STATUS_ENABLED];
        $list = M('Currency')->where($where)->select();
        $this->assign('list', $list);
        if (IS_POST && I('post.currency_id') > 0) {
            $where['id'] = I('post.currency_id');
        }
        $model = M('Currency');
        if(array_key_exists('id', $where)) $model->where(['id' => $where['id']]);
        $data = $model->select();
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 更新
     */
    public function update()
    {
        if (IS_POST) {
            $data = I('post.');
            $id = $data['id'];

            if ($data['currency_name'] == '') {
                return $this->error("币种名称不能为空");
            }

            if ($data['currency_mark'] == '') {
                return $this->error("币种标识不能为空");
            }

            if (!in_array($data['status'], [
                CurrencyModel::STATUS_DISABLED,
                CurrencyModel::STATUS_ENABLED
            ])) {
                return $this->error("币种状态不正确");
            }

            $upload = $this->uploadOne('currency_logo');
            if ($upload['status']) {
                $data['currency_logo'] = $upload['info'];
            }

            $upload = $this->uploadOne('currency_big_logo');
            if ($upload['status']) {
                $data['currency_big_logo'] = $upload['info'];
            }

            $res = M('Currency')->where(['id' => $id])->save($data);
            if ($res) $this->success('修改成功', U('Back/IconConf/index'));
            return $this->error('修改失敗');
        }

        $where = [];
        $where['id'] = I('get.id');
        $curr_info = M('Currency')->where($where)->find();
        $this->assign('curr_info', $curr_info);
        $this->assign('className', $this->arrCoinClass[$curr_info['flag']]);
        $this->display();
    }

    /*
     * 上传图片文件的方法后台上传的logo图片
     * 2017年10月16日15:44:42
     */
    private function uploadOne($name)
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg', 'svg');// 设置附件上传类型
        $upload->rootPath = './Upload/Back/'; // 设置附件上传根目录

        $upload->saveName = $name . '_' . time() . '_' . rand(100000, 999999);
        // 上传单个文件
        $info = $upload->uploadOne($_FILES[$name]);
        if (!$info) {// 上传错误提示错误信息
            $data['info'] = $upload->getError();
            $data['status'] = false;
            return $data;
        } else {// 上传成功 获取上传文件信息
            $data['info'] = '/Upload/Back/' . $info['savepath'] . $info['savename'];
            $data['status'] = true;
            return $data;
        }
    }
}