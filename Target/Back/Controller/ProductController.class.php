<?php
namespace Back\Controller;

use Back\Model\ProductModel;
use Back\Tools\Page;

/*
 * 後臺 線下交易控制器
 * @author 劉富國
 * @date  2017年10月13日
*/
class  ProductController  extends   BackBaseController
{
    protected $staticArr = [
        ''  =>  '=====选择商品状态=====',
        '1' =>  '上架',
        '2' =>  '下架',
    ];
    protected $url;  //域名+图片路径（路径徐包含Uploads文件加）

    public function __construct()
    {
        parent::__construct();
        $this->url = 'http://'.$_SERVER['HTTP_HOST'].'/';  //域名+图片路径（路径徐包含Uploads文件加）
    }

    public function index()
    {
        $where = $this->getParams();
        $count = M('Product')->where($where)->count();
        $Page  = new Page($count,10);
        $show  = $Page->show();// 分页显示输出
        $list = M('Product')
            ->order("static asc ")
            ->where($where)
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        foreach ($list as &$item){
            $item['product_logo'] = $this->url.$item['product_logo'];
        }
        $this->assign('page',$show);
        $this->assign('product_list',$list);
        $this->assign('staticArr',$this->staticArr);
        $this->display();
    }

    protected function getParams(){
        $where = [];
        $id = intval(I('id'));
        $product_name = trim(I('product_name')); //账户
        $static = intval(I('static'));
        if($id){
            $where['id'] = $id;
        }
        if($product_name){
            $where['product_name'] =['like','%'."$product_name".'%'];
        }
        if($static){
            $where['static'] = $static;
        }
        return $where;
    }
    //添加商品
    public function create(){
        if (IS_POST) {
            $data = I('post.');

            if ($data['product_name'] == '') {
                return $this->error("商品名称不能为空");
            }
            if ($data['price'] == '') {
                return $this->error("币种价格不能为空");
            }

            if (!in_array($data['static'], [
                ProductModel::PRODUCT_STATUS_UP,
                ProductModel::PRODUCT_STATUS_D
            ])) {
                return $this->error("商品状态不正确");
            }

            $upload = $this->uploadOne('product_logo');
            if ($upload['status']) {
                $data['product_logo'] = $upload['info'];
            }
            $res = M('Product')->add($data);
            if ($res) return $this->success('添加成功');
            return $this->error('添加失敗');
        }
        $this->display();
    }

    public function update(){
        if (IS_POST) {
            $data = I('post.');
            $id = $data['id'];

            if ($data['product_name'] == '') {
                return $this->error("商品名称不能为空");
            }

            if ($data['price'] == '') {
                return $this->error("商品价格不能为空");
            }

            if (!in_array($data['static'], [
                ProductModel::PRODUCT_STATUS_UP,
                ProductModel::PRODUCT_STATUS_D
            ])) {
                return $this->error("商品状态不正确");
            }

            $upload = $this->uploadOne('product_logo');
            if ($upload['status']) {
                $data['product_logo'] = $upload['info'];
            }

            $res = M('product')->where(['id' => $id])->save($data);
            if ($res) $this->success('修改成功', U('Back/Product/index'));
            return $this->error('修改失敗');
        }
        $where = [];
        $where['id'] = I('get.id');
        $product_info = M('Product')->where($where)->find();
        $this->assign('product_info', $product_info);
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