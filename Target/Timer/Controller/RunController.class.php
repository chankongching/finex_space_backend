<?php

namespace Timer\Controller;
use Think\Controller;
/**
 * @author 建强 2018年1月25日15:08:58
 * @desc   定时任务的脚本-基础类
 */
class  RunController extends Controller
{  
     public function __construct(){
        parent::__construct();
     	set_time_limit(0);
     	date_default_timezone_set('PRC');
     }	
     
     
}
