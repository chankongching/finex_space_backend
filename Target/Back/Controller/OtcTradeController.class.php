<?php
namespace Back\Controller;

/*
 * 後臺 線下交易控制器
 * @author 劉富國
 * @date  2017年10月13日
*/
class  OtcTradeController  extends   BackBaseController
{

    public function index()
    {
        echo $this->$_SERVER;die;
        $list = M('Product')->select();
        $this->assign('product_list',$list);
        $this->display();
    }
}