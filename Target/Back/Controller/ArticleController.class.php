<?php

namespace Back\Controller;

use Back\Model\ArticleModel;
use Back\Tools\Page;

class ArticleController extends BackBaseController
{

    /**
     * 文章列表页
     */
    public function index()
    {
        $this->listPage(ArticleModel::TYPE_ARTICLE);
    }

    /**
     * 广告列表页
     */
    public function adsIndex()
    {
        $this->listPage(ArticleModel::TYPE_ADS);
    }

    /**
     * 新手指引列表页
     */
    public function guideIndex()
    {
        $this->listPage(ArticleModel::TYPE_GUIDE);
    }

    /**
     * 商学院列表页
     */
    public function businessIndex()
    {
        $this->listPage(ArticleModel::TYPE_BUSINESS);
    }

    /**
     * 添加公告页面与方法
     * @author yangpeng
     * @Date   2017年8月11日15:40:36
     */
    public function create()
    {
        $types = [
            ArticleModel::TYPE_ARTICLE, ArticleModel::TYPE_ADS,
            ArticleModel::TYPE_GUIDE, ArticleModel::TYPE_BUSINESS
        ];
        if ($_POST) {

            $data['type'] = I('type', null);

            if (!in_array($data['type'], $types)) {
                $this->error('类型不正确');
                exit();
            }

            $data['en_us_title'] = I('en_us_title', null);
            $data['zh_tw_title'] = I('zh_tw_title', null);
            $data['zh_cn_title'] = I('zh_cn_title', null);

            $data['en_us_content'] = I('en_us_content', null);
            $data['zh_tw_content'] = I('zh_tw_content', null);
            $data['zh_cn_content'] = I('zh_cn_content', null);

            $data['create_time'] = $data['update_time'] = time();
            $data['status'] = ArticleModel::STATUS_ENDBALED;
            $data['sort'] = 0;
            $res = M('Article')->add($data);
            if ($res) {
                $this->success('添加成功', $this->getTypeIndex($data['type']), 1);
                exit();
            }
            $this->error('添加失敗');
            exit();
        }

        $type = isset($_GET['type']) ? $_GET['type'] : 0;
        if (!in_array($type, $types)) {
            return $this->error('页面不存在');
        }

        $this->assign('type', $type);

        $this->_assign($type);

        $this->display('create');
    }

    /**
     * 修改公告页面与方法
     * @author yangpeng
     * @Date   2017年8月11日15:40:36
     */
    public function update()
    {
        if ($_POST) {
            $where['id'] = I('get.id');
            $data['en_us_title'] = I('en_us_title', null);
            $data['zh_tw_title'] = I('zh_tw_title', null);
            $data['zh_cn_title'] = I('zh_cn_title', null);

            $data['en_us_content'] = I('en_us_content', null);
            $data['zh_tw_content'] = I('zh_tw_content', null);
            $data['zh_cn_content'] = I('zh_cn_content', null);

            $data['update_time'] = time();
            $model = M('Article')->where($where);
            $res = $model->save($data);
            if ($res) {
                $row = M('Article')->where($where)->find();
                $this->success('修改成功', $this->getTypeIndex($row['type']), 3);
                exit();
            }
            $this->error('修改失敗');
            exit();
        }
        $where['id'] = (int)I('get.id');
        $row = M('Article')->where($where)->find();
        $this->assign('article', $row);

        $this->_assign($row['type']);

        $this->display();
    }

    /**
     * 删除
     * @author yangpeng
     * @Date   2017年8月11日15:40:36
     */
    public function delete()
    {
        $id = I('id', null);
        if (empty($id)) return $this->error('請選擇刪除公告');
        $where['id'] = $id;
        $res = M('Article')->where($where)->delete();
        if ($res) return $this->success('刪除成功');
        return $this->error('刪除失敗');
    }

    /**
     * 文章管理分页
     * @param int $type
     */
    private function listPage(int $type)
    {
        $condition = ['type' => $type];
        $model = M('Article');

        $count = $model->where($condition)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();

        //改成按照 id倒序
        $list = (clone $model)->where($condition)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['zh_tw_title'] = html_entity_decode($v['zh_tw_title']);
            $list[$k]['zh_tw_content'] = strip_tags(html_entity_decode($v['zh_tw_content']));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('type', $type);

        $this->_assign($type);

        $this->display('index');
    }

    /**
     * 根据类型返回相关参数到视图
     * @param int $type
     */
    private function _assign(int $type)
    {
        $this->assign('type_name', $this->getTypeName($type));
        $this->assign('list_url', $this->getTypeIndex($type));
    }

    /**
     * 获取类型名称
     * @param int $type
     * @return string
     */
    private function getTypeName(int $type)
    {
        switch ($type) {
            case ArticleModel::TYPE_ARTICLE:
                $name = '文章';
                break;
            case ArticleModel::TYPE_ADS:
                $name = '广告';
                break;
            case ArticleModel::TYPE_GUIDE:
                $name = '新手指南';
                break;
            case ArticleModel::TYPE_BUSINESS:
                $name = '商学院';
                break;
        }
        return $name;
    }

    /**
     * 获取类型的列表名称
     * @param int $type
     * @return string
     */
    private function getTypeIndex(int $type)
    {
        switch ($type) {
            case ArticleModel::TYPE_ARTICLE:
                $index = 'index';
                break;
            case ArticleModel::TYPE_ADS:
                $index = 'adsIndex';
                break;
            case ArticleModel::TYPE_GUIDE:
                $index = 'guideIndex';
                break;
            case ArticleModel::TYPE_BUSINESS:
                $index = 'businessIndex';
                break;
        }
        return '/Back/Article/' . $index . '.html';
    }
}
