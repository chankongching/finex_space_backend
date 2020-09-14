<?php
/**
 * 商品模型
 */

namespace Back\Model;
use Think\Model;

class ProductModel extends Model{
    const PRODUCT_STATUS_UP = 1; //上架
    const PRODUCT_STATUS_D  = 2; //下架

	public function getProduct(){
	    $list = $this->field('id,product_name')->select();
	    return array_column($list,'product_name','id');
    }

}