<?php
namespace Back\Controller;

use Back\Tools\Page;

class PetcLvController extends BackBaseController{

    public function showPetcLv(){
        $data = M('petc_lv')
            ->find();
        $this->assign("data",$data);
        $this->display();
    }

    public function updatePetcLv(){

            //数据查询find（）方法，通过一维数组返回一条记录信息
            if (!empty($_POST)){
                $z = M('petc_lv') -> save($_POST);
                if ($z){
                    $this->success('修改成功',U('back/PetcLv/showPetcLv/'));
                }else{
                    $this->error('未做任何修改');die;
                }
        }
    }

/*
* 李江
* 2017年10月12日12:17:18
* 封装查询数据的方法
*/
    /*protected function getPetcLvInfo($tableName){
        $tableModel = M($tableName);
        $list = $tableModel->select();

        return $list;
    }*/

}