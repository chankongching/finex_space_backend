<?php
namespace Back\Controller;

use \Back\Tools\Page;
/*
 * 後臺 線下交易控制器
 * @author 劉富國
 * @date  2017年10月13日
*/
class  MyProductController  extends   BackBaseController
{
    //商品列表
    public function index(){
        $where = $this->getParams();
        $product_list = $this->getProductList($where);
        $products = D('Product')->getProduct();
        $this->assign('product_list',$product_list['list']);
        $this->assign('page',$product_list['page']);
        $this->assign('products',$products);
        $this->display();
    }

    //获取列表
    protected function getProductList($where){
        $count = M('MyProduct')->alias('m')->where($where)->count();
        $Page  = new Page($count,20);
        $show  = $Page->show();// 分页显示输出
        $list  = M('MyProduct')->alias('m')->where($where)
            ->join('left join __PRODUCT__ as p on p.id = m.product_id')
            ->join('left join __USER__ as u on u.uid = m.uid')
            ->field('u.username,m.product_num,m.uid,m.add_time,p.product_name')
            ->order("m.add_time desc ")
            ->limit($Page->firstRow.','.$Page->listRows)->select();
        return ['list'=>$list,'page'=>$show];
    }
    //获取商品列表
    protected function getParams(){
        $where = [];
        $uid = trim(I('uid'));
        $username = trim(I('username')); //账户
        $product_id = intval(I('product_id'));
        if($uid){
            $where['m.uid'] = $uid;
        }
        if($username){
            $where['m.uid'] = D('User')->getUidByUname($username);
        }
        if($product_id){
            $where['m.product_id'] = $product_id;
        }
        return $where;
    }



}