<?php
/**
 * User: yangpeng
 * Date: 2017/10/11
 * Time: 15:12
 */
namespace Back\Controller;
use Back\Tools\Page;//分页类
use Back\Common\OperateLog;//管理员操作日志写入类
use Back\Tools\Score;
use Back\Tools\Point;
use Back\Tools\SceneCode;
use Common\Api\RedisCluster;

class SecurityController extends BackBaseController{
    public $redis  = null ;
    public function __construct(){
        $this->redis =  RedisCluster::getInstance();
        parent::__construct();
    }
    
    
    public  $user_identify_status=[
            '0'=>'審核中',	
            '1'=>' 審核通過',	
            '-1'=>'審核未通過',
	];
    /** 
     * 回复审核的理由需要做成多语言
    */
    public $arr_reply=[
        "1"=>"您的證件已註冊綁定，請勿重複註冊。",
        "2"=>"您的手持證件照片證件不清晰，無法查看證件姓名和證件號碼，請重新拍照上傳。",
        "3"=>"證件號碼或姓名被遮擋。",
        "4"=>"照片格式錯誤(標準格式為.jpg,照片體積不能超過 3MB)",
        "5"=>"您所提交的實名信息與手持證件照不相符或被判定為後期處理照片。",
        "6"=>"您的證件年齡超限。",
        "9"=>"您使用的證件不正確，請使用護照進行注册認證。",
    ];
    //如下两种情况处理待审核和通过审核的
    public $arr=[
        "7" =>"身份認證待審核",
        "8"=> "符合條件給予通過",
    ];
    /*
     * yangpeng
     * 2017年10月11日12:14:50
     * 身份认证
     */
    public function showIdCardList(){
        //1、獲取表單數據并拼裝where條件
        $name=trim(I('get.username'));
        if($name){
            $where['m.uid'] = D('User')->getUidByUname($name);
            $this->assign('username',$name);
        }
        $uid=trim(I('get.uid'));
        if($uid){
            $where['m.uid'] = $uid;
        }
        $card_num=trim(I('get.card_num'));
        if($card_num){
            $where['m.card_num'] = $card_num;
        }
        //2、獲取數據并分頁
        $M_User_Real = M('UserReal');
        $count  = $M_User_Real->alias('m')->where($where)->count();// 查询满足要求的总记录数
        $Page   = new Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show   = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = $M_User_Real
                    ->where($where)
                    ->field('m.*,u.username')
                    ->alias('m')
                    ->join("left join __USER__ as u on u.uid= m.uid")
                    ->order("add_time desc ")
                    ->limit($Page->firstRow.','.$Page->listRows)
                    ->select();
        //2、模板傳值
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }


    /**
     * @method 生成实名认证提交token 防止重复提交
     * @author 杨鹏 2019年4月1日12:17:23
     * @param null
     * @retrun null
     */
    public function createToken(){
        dump(createToken());
    }
    /**
     * @method 验证实名认证token 防止重复提交
     * @author 杨鹏 2019年4月1日12:17:44
     * @param $token
     * @retrun bool
     */
    public function checkToken($token){
        checkToken($token);
    }
 
    /**
     * @method 修改确认身份认证提交  护照身份认证 进行加积分
     * @author 2017-10-15 yangpeng
     */
    public function userDetail(){
        $uid = trim(I('uid'));
        if(empty($uid))  return $this->error('暫未找到該用護！');
        
        $where = ['uid'=>$uid];
        $list  = M('UserReal')->where($where)->find();
        if(empty($list))  return $this->error('暫未提交身份認證！');
        
        if(IS_GET){
            //at 模板渲染 
            $record_all = M('AdminCtrlog')->where($where)->order("add_time desc")->select();
            
            $this->assign('list',$list);
            $this->assign("word_reply",$this->arr_reply);
            $this->assign('record_all',$record_all);
            $this->display();
        }
        
        if(IS_POST){
            $user_id = I('uid');
            $status  = trim(I('status'));
            //$token = I('real_token');
            // if(!checkToken($token)) return $this->error("请勿重复提交");//重复提交校验
            //at 2019年5月7日 重复提交  
            
            $key       = 'repeat_op_user'.$uid ;
            $repeat_op = $this->redis->get($key);
            if($repeat_op) return $this->error('请勿重复提交');
            
            $flag         = false;
            $data         = ['status'=>$status];
            $reply_data   = [ 
                 'status'  => $status,
                  'uid'    =>$user_id,
                 'c_user'  => $this->back_userinfo['username'],
                 'add_time'=> time(),
            ];
            
            $result         = M('UserReal')->where($where)->field('status')->find();
            if($result['status']==1) return $this->error("審核已通過，請勿重復審核");
            if($data['status']==0){
                //status=0 待审核
                $data['system_reply']=7;
                //ctr log
                $reply_data['c_reply'] = 7;
                $reply_data['status']  = -1;  
            	
            }else if($data['status']==1){
            	//status=1 审核通过 
            	$data['system_reply'] = 8;
            	$data['expire_status'] = 2;  // 护照过期状态 
            	$flag = true; // 需要加积分
            	$reply_data['c_reply'] = 8;
				
            }else{  
            	// status=-1  未通过  必须选择没有通过的原因
                $sysAnswer = I('post.system_reply');
                if(empty($sysAnswer) || $sysAnswer<0) return $this->error("請選擇未通過審核的原因");
                $data['system_reply']  = $sysAnswer;
                
                $reply_data['c_reply'] = $data['system_reply'];
            }
            
            //at 格式化并拼裝管理員操作日誌數據
            if($data['status']  != 0) $data['check_time'] = time();
            $data['submit_lock'] = 0;
            //at 插入数据的日志的记录
            $log_status = $data['status']?$data['status']:0;  
            $log        = '修改用户状态为-'.$this->user_identify_status[$log_status];
            $res_log    = M('UserReal')->where(['uid'=>$user_id,'status'=>$log_status])->find();
            $admin_user = $this->back_userinfo;
            
            $this->redis->setex($key,5,1); //冪等操作
            $r = [];
            M()->startTrans();
            if(!$res_log){
                $res_user=M('User')->where(['uid'=>$user_id])->find();
                $username=$res_user['username']?$res_user['username']:'未找到該用戶';
                // at对比记录是否需要写日志    添加操作审核的记录日主
               $r[] = OperateLog::inser_user_authentication_log($user_id, $username,$admin_user['username'],$log);
            }
            //at 添加积分
            if ($flag==true){
                 $wherePoint = ['status' => Point::BIND_PASS_REAL_STATUS, 'uid'=> $user_id];
                 $scoreTable = 'UserScoreLog'.$user_id%4;
                 $ret        = M($scoreTable)->where($wherePoint)->find();
                 if(empty($ret)) $r[] = $this->addScore($user_id);
                 M('User')->where(['uid'=>$user_id])->setField('tips',1); //開啟tips 
            }
            
            //at 添加管理員操作日誌
            $r[] = M('UserReal')->where(['uid'=>$user_id])->save($data);
            $r[] = M('AdminCtrlog')->add($reply_data);
            if(in_array(false,$r)){
                M()->rollback();
                return  $this->error('操作失败');
            }
            M()->commit();
            
            //at 是否需要推送 
            if($data['status'] == 1) self::realUserPushMsg($user_id);
            $this->success('审核状态修改成功');
        }
    }
    /**
     * @method 实名认证成功后推送 
     * @author 建强 2019年6月11日14:46:47 
     * @return void 
     */
    private static function realUserPushMsg($uid){
        $userData = M('User')->field('om,username')->where(array('uid' =>$uid))->find();
        $template = SceneCode::getPersonSafeInfoTemplate($userData['username'],$userData['om'],8 );
        $template = explode('&&&', $template);
        
        $arr = $post_data    = [];
        $arr['uid']          = $uid;
        $arr['title']        = $template[0];
        $arr['content']      = $template[1];
        $arr['extras']['checkUserReal'] = '1';
        $post_data['data']   =  $arr;
        $post_data['server'] =  'SendMsgToPerson';
       
        curl_api_post($post_data);
    }
    
   /**
     * @method 实名认证通过加积分 
     * @author 宋建强 2017年11月7日
    */
    private function addScore($uid)
    {
    	$scoreObj=new Score();
    	$param=[
    			'operationType'=>'inc',
    			'isOverTime'=>'',
    			'scoreInfo'=>'實名認證審核通過',
    			'status'=>Point::BIND_PASS_REAL_STATUS
    	];
    	return $scoreObj->calUserIntegralAndLeavl($uid,Point::ADD_PERSON_PASS,$param);
    }
    /*
     * 銀行卡信息
     * @author yangpeng 
     * 2017-8-15
     */
    public function showBankList(){
        //  1、获取表单数据
        $name=trim(str_replace('+', '', I('name')));//剔除'+'和去除空格
        $user_id=trim(str_replace('+', '', I('user_id')));//剔除'+'和去除空格
        $uid = trim(I('uid'));  
         //  2、确定查询条件（顺序为user_id、name、uid）
        $where['m.status'] = 1;
        if($uid ){
            $where['m.uid'] =$uid;
        }
        if($name){
            $user_info = D('User')->getUidByUname($name);
            $where['m.uid'] =$user_info;
            if(!$user_id){
                $this->assign('username',$name);
            }
        }
        if($user_id){
            $where['m.uid'] =$user_id;
            $this->assign('user_id',$user_id);
        }
        //  3、获取数据并分页
        $M_User_Bank = M('UserBank');
        $count      = $M_User_Bank->alias('m')->where($where)->count();// 查询满足要求的总记录数
        $Page       = new Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        $list = $M_User_Bank
            ->where($where)
            ->field('m.*,u.username')
            ->alias('m')
            ->join("left join  __USER__ as u on u.uid=m.uid")
            ->order("m.add_time desc ")
            ->limit($Page->firstRow.','.$Page->listRows)->select();
        //获取银行对应的列表
        $ret=M('BankList')->field('id,bank_name')->select();
        $bank_list=array_column($ret, 'bank_name','id');
        
        $this->assign("bank_list",$bank_list);
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    /*
     * 用户登录日志
     * 黎玲 2017 10 12
     */
    public function showUserLog() {
    	$this->DelLog('UserLog',3,'add_time',3); //删除日志记录
    	
        $map['username'] = trim(I('username'));
        if(!empty($map['username'])){
            $user_data = M('User')->where($map)->find();
            $us = $user_data['uid'] % 4;
            $count = M("UserLog$us")->where(array('uid'=>$user_data['uid']))->count();
            $page = new \Back\Tools\Page($count,15);
            $show = $page->show();
            $list = M("UserLog$us")
                    ->where(array('uid'=>$user_data['uid']))
                    ->order('add_time desc')
                    ->limit($page->firstRow.','.$page->listRows)
                    ->select();
            foreach ($list as $k=>$v){
                    $list[$k]['username'] = $user_data['username'];
                }
        }else {
            //1.用分页查询所有用户的登录信息
            $model = new \Think\Model();
            $sql_c = "select count(b.id) as count FROM(
                   select * from trade_user_log0
                   UNION ALL
                   SELECT * from trade_user_log1
                   UNION ALL
                   select * from trade_user_log2
                   UNION ALL
                   SELECT * from trade_user_log3
                   )as b";
            $count_data = $model->query($sql_c);
            $page = new \Back\Tools\Page($count_data[0]['count'], 10);
            $show = $page->show();
            $sql = "select a.username,b.type,b.url,b.ip,b.add_time FROM(
                   select * from trade_user_log0
                   UNION ALL
                   SELECT * from trade_user_log1
                   UNION ALL
                   select * from trade_user_log2
                   UNION  ALL
                   SELECT * from trade_user_log3
                   )as b  LEFT JOIN trade_user as a ON b.uid=a.uid    ORDER BY add_time desc LIMIT {$page->firstRow},{$page->listRows}";
            $list = $model->query($sql);
        }
        $this->assign('list', $list);
        $this->assign('page',$show);
        $this->display();
        }
    /**
     * @author 建强  2018年12月5日18:04:13 
     * @method excel 导出实名认证数据
     * @return string json 
    */    
    public function outExcel()
    {
        $data = [
            'code'=>400,
            'msg'=>'操作失敗',
            'data'=>[]
        ];
        if(!IS_AJAX && !IS_POST) $this->ajaxReturn($data);      
        $where =[
            'status'=> 1, //审核通过
        ];        
        //按照时间查询 
        $s_time = I('s_time',0);
        $e_time = I('e_time',0);
        if($s_time<=0 || $e_time<=0)
        {
            $data['msg']= '請選擇起始時間'; 
            $this->ajaxReturn($data);
        }
        if(date('Y-m-d H:i', strtotime($s_time))!=$s_time ||
           date('Y-m-d H:i', strtotime($e_time))!=$e_time)
        {
            $data['msg']="起始時間格式不正確";
            return $this->ajaxReturn($data);
        }
        
        $s_time = ((strtotime($s_time))>0)?(strtotime($s_time)):0;
        $e_time = ((strtotime($e_time))>0)?(strtotime($e_time)):time();
        $where['check_time']=['BETWEEN',[$s_time,$e_time]];
        
        $field = 'uid,card_type,card_name,card_num,check_time,status';
        $res = M('UserReal')->field($field)->where($where)->select();
        
        if(empty($res))
        {
            $data['code']=406;
            $data['msg'] ='沒有符合要求的數據' ;
            $this->ajaxReturn($data);
        }
        $res = $this->getInfoUserReal($res);
        $data['data']=[
            'title'=>'BTCS實名認證' ,
            'th'   =>['用戶ID','用戶名','證件類型','證件姓名','證件號碼','審核時間' ,'處理人','狀態'],
            'res'=>$res,
        ] ;
        $data['code']= 200;
        $data['msg'] ='success';
        $this->ajaxReturn($data);
    }
    
    /**
     * @author 建強 2018年12月6日10:35:05 
     * @method 獲取實名認證詳細數據
     * @param  array  
     * @return array
    */
    protected function getInfoUserReal($arr)
    {
        $uids  = array_unique(array_column($arr, 'uid'));
        $operatLog = [
            'user_id'=>['IN',$uids],
            'type'   =>9            // 實名認證類型日誌
        ];
        $operatNames = M('ChangeUserLog')->where($operatLog)->field('id,user_id,admin_user')->select();
        $operatNames = array_column($operatNames, 'admin_user','user_id'); 
        $names = M('User')->where(['uid'=>['IN',$uids]])->field('uid,username')->select();
        $names = array_column($names, 'username','uid');
        foreach($arr as $key=>$value) 
        {
            $arr[$key]['name']       = ($names[$value['uid']])?($names[$value['uid']]):'-';
            $arr[$key]['type_str']   = '護照';
            $arr[$key]['status_str'] = '通過審核';
            $arr[$key]['check_time'] = ($value['check_time']>0)?(date('Y-m-d H:i:s',$value['check_time'])):0;
            $arr[$key]['operat_name']= ($operatNames[$value['uid']])?($operatNames[$value['uid']]):'-';
            unset($arr[$key]['status']);
            unset($arr[$key]['card_type']);
        }
        return $arr; 
    }
}