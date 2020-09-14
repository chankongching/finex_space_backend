<?php
/**
 * Created by PhpStorm.
 * User: 李江
 * Date: 2017/10/24
 * Time: 14:55
 * 银行卡管理控制器
 */

namespace Back\Controller;
use Back\Tools\Page;

class BankController extends BackBaseController
{
    public function AddBankCard(){
        if(IS_POST){
            $input = I('post.');
            $country_name = $input['country_name'];
            $card_name = trim($input['card_name']);
            $data['country_code'] = $country_name;
            
            if (empty($country_name) || $country_name==-1)
            {
            	return  $this->error("請選擇銀行國家");
            
            }
            if (empty($card_name))
            {
                return $this->error("請輸入銀行名稱");            	
            }
            $ret=M('BankList')->where(['bank_name'=>$card_name])->find();
            if ($ret)
            {
            	return $this->error('該銀行已存在');
            }
            $bank_logo = $this->uploadOne('bank_logo');
            if( $bank_logo['status'] ){
                $logo_path = $bank_logo['info'];
            }
            $bank_img = $this->uploadOne('bank_img');
            if( $bank_img['status'] ){
                $img_path  = $bank_img['info'];
            }
            $data['bank_name'] = $card_name;
            $data['bank_logo'] = $logo_path;
            $data['bank_img']  = $img_path;
            $res = M('BankList')->add($data);
            if( $res ){
                return $this->success('添加成功',U('/Back/Bank/BankCardList'));die;
            }else{
                return $this->error('添加失敗');die;
            }
        }
        $country_list = $this->getAllBank();
        $this->assign('country_list',$country_list);
        $this->display();
    }

    public function BankCardList(){
        if( I('get.country_name') && I('get.country_name') != -1 ){
            $where['country_code'] = trim(I('get.country_name'));
        }
        if( I('get.cardname') ){
            $where['bank_name'] = trim(I('get.cardname'));
        }
        $country_name_list = $this->getAllBank();
        $this->assign('country_name_list',$country_name_list);

        $count = M('BankList')->count();
        $pageModel = new Page($count,15);
        $show = $pageModel->show();
        $bankList = M('BankList')
            ->join('left join trade_country_code as cc on trade_bank_list.country_code=cc.code')
            ->order('id asc')
            ->field('cc.country,trade_bank_list.*')
            ->where($where)
            ->limit($pageModel->firstRow.','.$pageModel->listRows)
            ->select();
        foreach ($bankList as $k=>&$v){
            $v['country_name'] = $v['country_code'].$v['country'];
        }

        $this->assign('bankList',$bankList);
        $this->assign('page',$show);
        $this->display();
    }

    
    //删除银行卡
    public function delBankCard(){
        if( IS_AJAX ){
            $id = I('id');
            if( empty($id) || !isset($id)){
                $this->ajaxReturn(['status'=>400,'msg'=>'刪除失敗']);
            }else{
                $res = M('BankList')->where(['id'=>$id])->delete();
                if( $res ){
                    $this->ajaxReturn(['status'=>200,'msg'=>'刪除成功']);
                }else{
                    $this->ajaxReturn(['status'=>400,'msg'=>'刪除失敗']);
                }
            }
        }
    }

    /*
     * 修改银行卡信息
     * 李江
     * 2017年10月25日15:03:31
     */
    public function changeBank(){
        if( I('get.') ){
            $country_list = $this->getAllBank();
            $this->assign('country_list',$country_list);
            $id = I('get.id');
            $bankInfo = M('BankList')->where(['id'=>$id])->find();
            $this->assign('bankInfo',$bankInfo);
            $this->display();
        }
        if( I('post.') ){
            $input = I('post.');
            $bank_id = $input['bank_id'];
            $data['country_code'] = $input['country_name'];
            $data['bank_name'] = $input['card_name'];

            //上传银行卡背景图片
            $bank_logo = $this->uploadOne('bank_logo');
            if( $bank_logo['status'] ){
                $logo_path = $bank_logo['info'];
            }
            $bank_img = $this->uploadOne('bank_img');
            if( $bank_img['status'] ){
                $img_path = $bank_img['info'];
            }
            $data['bank_logo'] = $logo_path;
            $data['bank_img']  = $img_path;

            $res = M('BankList')->where(['id'=>$bank_id])->save($data);
            if($res){
                return $this->success('修改成功',U('Back/Bank/BankCardList'));
            }else{
                return $this->error('修改失敗');
            }
        }
    }

    /*
     * 获取所有银行卡信息
     * 李江
     * 2017年10月25日15:13:32
     */
    private function getAllBank(){
        $country_list = M('CountryCode')->select();
        foreach ($country_list as &$v) {
            $v['country_name'] = $v['code'].$v['country'];
        }
        return $country_list;
    }

    /*
     * 上传图片文件的方法后台上传的logo图片
     * 2017年10月16日15:44:42
     */
    private function uploadOne($name)
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg','svg');// 设置附件上传类型
        $upload->rootPath  =     './Upload/Back/'; // 设置附件上传根目录

        $upload->saveName  =     $name.'_'.time().'_'.rand(100000,999999);
        // 上传单个文件
        $info   =   $upload->uploadOne($_FILES[$name]);
        if(!$info) {// 上传错误提示错误信息
            $data['info']=$upload->getError();
            $data['status']=false;
            return $data;
        }else{// 上传成功 获取上传文件信息
            $data['info']='/Upload/Back/'.$info['savepath'].$info['savename'];
            $data['status']=true;
            return $data;
        }
    }
}