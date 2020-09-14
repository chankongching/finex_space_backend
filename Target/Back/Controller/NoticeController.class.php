<?php

/*
 * 登录后台公共列表
 * @author yangpeng
 * @Date   2017年8月11日15:26:36
 * 
*/

namespace Back\Controller;

class NoticeController extends BackBaseController
{
    /**
     * 公告列表显示页面
     * @author yangpeng
     * @Date   2017年8月11日15:40:36
     */
    public function showNoticeList()
    {
        $count = M('NoticeNew')->count();
        $Page = new \Back\Tools\Page($count, 10);
        $show = $Page->show();
        //改成按照 id倒序
        $list = M('NoticeNew')->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['title'] = html_entity_decode($v['title']);
            $list[$k]['abstract'] = html_entity_decode($v['abstract']);
            $list[$k]['content'] = strip_tags(html_entity_decode($v['content']));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    /**
     * 添加公告页面与方法
     * @author yangpeng
     * @Date   2017年8月11日15:40:36
     */
    public function addNotice()
    {
        if ($_POST) {
            $upload = $this->uploadOne('face_img');
            if ($upload['status']) {
                $data['face_img'] = $upload['info'];
            }
            $data['title'] = I('title', null);
            $data['abstract'] = I('abstract', null);
            $data['content'] = I('content', null);
            $data['add_time'] = time();
            $res = M('NoticeNew')->add($data);
            if ($res) {
                $this->success('添加成功', '/Back/Notice/showNoticeList', 1);
                exit();
            } else {
                $this->error('添加失敗');
                exit();
            }
        }
        $this->display();
    }

    /**
     * 修改公告页面与方法
     * @author yangpeng
     * @Date   2017年8月11日15:40:36
     */
    public function changeNotice()
    {
        if ($_POST) {
            $where['id'] = I('id');
            $data['title'] = I('title', null);
            $data['abstract'] = I('abstract', null);
            $upload= $this->uploadOne('face_img');
            if ($upload['status']) {
                $data['face_img'] = $upload['info'];
            }
            $data['content'] = I('content', null);
            $data['add_time'] = time();

            $res = M('NoticeNew')->where($where)->save($data);
            if ($res) {
                $this->success('修改成功', '/Notice/showNoticeList', 3);
                exit();
            } else {
                $this->error('修改失敗');
                exit();
            }
        }
        $where['id'] = (int)I('get.notice_id');
        $notice = M('NoticeNew')->where($where)->find();
        $this->assign('notice', $notice);
        $this->display();
    }

    /**
     * 删除公告
     * @author yangpeng
     * @Date   2017年8月11日15:40:36
     */
    public function delNotice()
    {
        $id = I('id', null);
        if (empty($id)) {
            $this->error('請選擇刪除公告');
        }
        $where['id'] = $id;
        $res = M('NoticeNew')->where($where)->delete();
        if ($res) {
            $this->success('刪除成功');
        } else {
            $this->error('刪除失敗');
        }
    }

    /*
   * 上传图片文件的方法后台上传的logo图片
   * 2017年10月16日15:44:42
   */
    private function uploadOne($name)
    {
        $path = './Upload/News/';
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg', 'svg');// 设置附件上传类型
        if(!is_dir($path)){
            mkdir('./Upload/News',0777);
        }
        $upload->rootPath = $path; // 设置附件上传根目录
        $upload->saveName = $name . '_' . time() . '_' . rand(100000, 999999);
        // 上传单个文件
        $info = $upload->uploadOne($_FILES[$name]);
        if (!$info) {// 上传错误提示错误信息
            $data['info'] = $upload->getError();
            $data['status'] = false;
            return $data;
        } else {// 上传成功 获取上传文件信息
            $data['info'] = '/Upload/News/' . $info['savepath'] . $info['savename'];
            $data['status'] = true;
            return $data;
        }
    }
}
