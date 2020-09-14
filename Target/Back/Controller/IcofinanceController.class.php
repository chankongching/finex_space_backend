<?php

namespace Back\Controller;


class IcofinanceController extends BackBaseController
{
    //sdsd
    public function showIcofinance(){
        $data = D("user_ico_finance")->field("id,user_id,q_name,q_num,currency_id,dh_num,ico_user_num,add_time,status")->select();
        $assign=array(
            'datatable'=>$data
        );

        $this->assign($assign);
        $this->display();
    }

}