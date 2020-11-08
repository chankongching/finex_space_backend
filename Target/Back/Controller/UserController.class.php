<?php
/**
 * User: 李江
 * Date: 2017/8/11
 * Time: 14:22
 *     用户中心控制器
 */
namespace Back\Controller;
use Back\Common\OrderUserMoney;
use Back\Tools\Page;
use Common\Api\RedisIndex;
use Common\Api\redisKeyNameLibrary;
use Common\Api\RedisCluster;

class UserController extends BackBaseController{

	protected static $adminUid=88;

	private $desc=[
			'1'=>'asc',
	        '-1'=>'desc',
	];

    private $_where        = [];
    private $_mod          = 0;
    private $_username     =  'unknow';
    private $_currencyName = [];
    private $_uid          = 0;

    public function __construct(){
        parent::__construct();
        $uid = intval(trim(I('uid')));

        if($uid > 0 ) {
            $this->_where['uid'] = $uid;
            $this->_mod = $uid % 4 ;
            $this->_uid = $uid;
            $this->setUserName();
            $this->setCurrencyName();
        }
    }
    /**
     * @method 设置用户名   避免连表获取
     * @return bool
     */
    private function setUserName(){
        $user = M('User')->where($this->_where)->find();
        if(!empty($user)) $this->_username = $user['username'];
        return true;
    }
    /**
     * @method 设置货币单位
     * @return boolean
     */
    private function setCurrencyName(){
        $currs = M('Currency')->field('id,currency_name')->select();
        if(!empty($currs)) $this->_currencyName = array_column($currs, 'currency_name','id');
        return true;
    }


    /**
     * 用户列表查看
     * @author wangfuw
     * @time 23点13分
     */
    public function index(){
        $admin=0;

       if($this->back_userinfo['id']==self::$adminUid)
       {
       	   $admin=1;
       }
       $where = $this->getParamsTwo();
        $redis=RedisIndex::getInstance();
        $redis_data = $redis->getSessionValue("user");
        //查詢該用戶是否是代理商
        $agent =D("auth_group_access")->where("uid = {$redis_data['id']} and group_id = 11")->find();
       if ($agent){
            $admin_data = D("admin_user")->where("id = {$redis_data['id']} ")->find();
            if (empty($where)){
                $array["trade_user.invite_code"] = $admin_data["invite_code"];
                $where = $array ;
            }else{
                $where["trade_user.invite_code"] = $admin_data["invite_code"];
            }
           $agent["invite_code"] = $admin_data["invite_code"];
       }
        if(empty($where)){
            $where = 200;
        }
        $userInfoList = $this->getUserList($where,'User', 'uid desc');
       $this->assign('list',$userInfoList['list']);// 赋值数据集
       $this->assign('agent',$agent);// 传入前端判断是否是代理商
       $this->assign('agent_count',count($agent));// 传入前端判断是否是代理商
       $this->assign('page',$userInfoList['page']);// 赋值分页输出
       $this->assign('admin',$admin);              //  管理员
       $this->display();
    }

    public function loginInfo(){
        $admin=0;

        if($this->back_userinfo['id']==self::$adminUid)
        {
            $admin=1;
        }
        $where = $this->getParamsTwo();
        $redis=RedisIndex::getInstance();
        $redis_data = $redis->getSessionValue("user");
        //查詢該用戶是否是代理商
        $agent =D("auth_group_access")->where("uid = {$redis_data['id']} and group_id = 11")->find();
        if ($agent){
            $admin_data = D("admin_user")->where("id = {$redis_data['id']} ")->find();
            if (empty($where)){
                $array["trade_user.invite_code"] = $admin_data["invite_code"];
                $where = $array ;
            }else{
                $where["trade_user.invite_code"] = $admin_data["invite_code"];
            }
            $agent["invite_code"] = $admin_data["invite_code"];
        }
        if(empty($where)){
            $where = 200;
        }


        $userInfoList = $this->getUserListLogin($where,'User', 'add_time desc');
        $this->assign('list',$userInfoList['list']);// 赋值数据集
        $this->assign('agent',$agent);// 传入前端判断是否是代理商
        $this->assign('agent_count',count($agent));// 传入前端判断是否是代理商
        $this->assign('page',$userInfoList['page']);// 赋值分页输出
        $this->assign('admin',$admin);              //  管理员
        $this->display();
    }


    //获取查询条件
    protected function getParams(){
        $where = [];
        if($_GET['uid']){
            $where['uid'] = trim($_GET['uid']);
        }
        if($_GET['username']){
            $where['username'] = array('like','%'.trim($_GET['username']).'%');
        }
        if($_GET['status'] != ''){
            $where['status'] = intval($_GET['status']);
        }
         if($_GET['invite_code'] != ''){
            $where['invite_code'] = trim($_GET['invite_code']);
        }
        return $where;
    }
    //获取查询条件2
    protected function getParamsTwo(){
        $where = [];
        if($_GET['uid']){
            $where['trade_user.uid'] = trim($_GET['uid']);
        }
        if($_GET['username']){
            $where['trade_user.username'] = array('like','%'.trim($_GET['username']).'%');
        }
        if($_GET['status'] != ''){
            $where['trade_user.status'] = intval($_GET['status']);
        }
         if($_GET['nickname'] != ''){
            $where['trade_user.invite_code'] =  $this->get_invite_code(trim($_GET['nickname']));
        }
        if($_GET['invite_code'] != ''){
            $where['trade_user.invite_code'] =  trim($_GET['invite_code']);
        }
        return $where;
    }
    protected function get_invite_code($nickname)
    {
        $invite_code = M("admin_user")->where("nickname = '$nickname'")->getField("invite_code");
        return $invite_code;
    }


    //修改用户
    public function editUser(){
        if(IS_POST){
            $uid = I('post.uid');
            if( empty($uid) || $uid == 0 ){
               return $this->error('用户不存在');
            }
            $data['nickname'] = trim(I('post.nickname'));
            $data['phone'] = trim(I('post.phone'));
            $data['email'] = trim(I('post.email'));
            I('post.password')?$data['pwd'] = passwordEncryption(I('post.password')):'';
            I('post.trade_password')?$data['trade_pwd'] = passwordEncryption(I('post.trade_password')):'';
            $data['status'] =  I('post.status');
//            if( !empty(I('post.assets')) && I('post.assets') )
//            {
//                $data['assets'] =  I('post.assets');
//            }

//            $mark  = trim(I('post.mark'));
//            if(empty($mark)){
//                return $this->error('備注不能爲空，請填寫備注');
//            }
            $data_mark['uid'] = $uid;

            $data_mark['add_time'] = time();

            $r = M('User')->where(array('uid'=>$uid))->save($data);

            if( !$r ){
                $this->error('未做任何修改');die;
            }
            $this->success('修改成功',U('User/editUser/uid/'.$uid));
        }else{

            //at 頁面渲染  補充備註
            $uid = I('uid');
            $user_list = D('User')->getUserByUid($uid);
            if(!$user_list)   return $this->error('用護不存在');
            $this->assign('user_list',$user_list);
            $this->display();
        }
    }

    //查看/修改用户资金
    public function userCurrency(){
        $uid = I('uid');
        $ret=M('User')->field('username')->where(['uid'=>trim($uid)])->find();
        if (!$ret)
        {
         	return $this->error('用護不存在');
        }
        /*
         * 每次修改或者查看之前先检查该用户是否添加全部币种 李江 2017年10月17日16:02:22
         */
        $res = checkUserCurrencyRecord($uid);
        $all_currency_list = M('UserCurrency')->alias('uc')->join('__CURRENCY__ as c on c.id=uc.currency_id','right')
            ->where(['uc.uid'=>$uid,'c.status'=>1])->field('c.currency_name,uc.*')->select();
        foreach ($all_currency_list as $k=>$v){
            $all_currency_list[$k]['pos'] = $k;
            if( $k == 0 ){
                $all_currency_list[$k]['flag'] = 'true';
                $all_currency_list[$k]['expend'] = 'in';
            }else{
                $all_currency_list[$k]['flag'] = 'false';
                $all_currency_list[$k]['expend'] = '';
            }
        }

        $currency_info = M('Currency')->where(['status'=>1])->field('id,currency_name')->select();
        $this->assign('currency_list',$currency_info);

        $this->assign('all_currency_list',$all_currency_list);
        $this->assign('uid',$uid);
        $this->assign('username',$ret['username']);
        $this->display();
    }

    public function setUserCurrencyNum(){
        if( IS_POST ){
            $input = I('post.');
            $uid = $input['uid'];
            $currency_id = $input['currency_id'];
            $currency_name = M('Currency')->where(['id'=>$currency_id])->getField('currency_name');
            $num =  trim($input['num']) ;
            if(!is_numeric($num) || $num<=0){
                return $this->error("請填寫大於零的數量");
            }
            $change_type = $input['change_type'];
            $where['uid'] = $uid;
            $where['currency_id'] = $currency_id;
            $userMoney = new OrderUserMoney();
            $res_info = M('UserCurrency')->where(['uid'=>$uid,'currency_id'=>$currency_id])->find();
            if( $change_type == 1 ){
                $res1 = M('UserCurrency')->where($where)->setInc('num',$num);
                $after_money = $res_info['num'] + $num;
                $res2 = $userMoney->AddFinanceLog($uid, $currency_id, 3, '系統充值', 1, $num,$after_money);
            }elseif( $change_type == 2 ){
                $rest_num = M('UserCurrency')->where($where)->getField('num');
                $small_num = $num;
                if( $num > $rest_num ){
                    $small_num = $rest_num;
                }
                $res1 = M('UserCurrency')->where($where)->setDec('num',$small_num);
                $after_money = $rest_num - $small_num;
                $res2 = $userMoney->AddFinanceLog($uid, $currency_id, 4, '系統扣除', 2, $small_num,$after_money);
            }
            if( $res1 && $res2 ){
                $this->success('修改成功',U('User/userCurrency',array('uid'=>$uid)));
            }
            else{
                $this->error('修改失敗,請填寫正確的金額數量',U('User/userCurrency',array('uid'=>$uid)));
            }
        }
    }
    /*
     * 2017年10月25日20:37:27
     * 李江
     * 调用绑定地址的方法
     */
    private function bindAddress($url_str='url1',$real_url,$uid,$currency_id){
        if( strlen($real_url) > 48 || strlen($real_url) < 15 ){
            $this->error('地址'.$this->changeUrl()[$url_str].'長度超過限制',U('User/userCurrency',array('uid'=>$uid)));
        }
        if( !regex($real_url,'addurl') ){
            $this->error('地址'.$this->changeUrl()[$url_str].'格式有誤',U('User/userCurrency',array('uid'=>$uid)));
        }
        $where['uid'] = $uid;
        $where['currency_id'] = $currency_id;
        $currency_name = M('Currency')->where(['id'=>$currency_id])->getField('currency_name');

        if( !in_array($url_str,['url1','url2','url3']) ){
            return false;
        }
        $data['my_mention_pack_'.$url_str] = $real_url;
        $opt_data['user_id'] = $uid;
        $opt_data['username'] = M('User')->where(['uid'=>$uid])->getField('username');
        $opt_data['admin_user'] = $this->back_userinfo['username'];
        $opt_data['type'] = 1;
        $opt_data['add_time'] = time();
        $opt_data['log'] = "修改({$currency_name})個人錢包地址".$this->changeUrl()[$url_str]."为:".$real_url;

        $res1 = M('ChangeUserMoneyLog')->add( $opt_data );
        if($res1){
            $res = M('UserCurrency')->where($where)->save($data);
            return $res;
        }
        return false;
    }
    private function changeUrl(){
        return ['url1'=>'一','url2'=>'二','url3'=>'三'];
    }
    public function subBindAddress(){
        if( IS_POST ){
            $input = I('post.');
            $uid = $input['uid'];
            $currency_id = $input['currency_id'];
            unset($input['uid']);
            unset($input['currency_id']);
            if( empty($input['url1']) && empty($input['url2']) && empty($input['url3']) ){
                return $this->error('請填寫要綁定的地址');
            }
            $res=[];
            M()->startTrans();
            foreach ($input as $key=>$value){
                if( !empty($value) ){
                    $res[] = $this->bindAddress($key,trim($value),$uid,$currency_id);
                }
            }
            if( in_array(false,$res) ){
                M()->rollback();
                return $this->error('绑定地址失败');
            }

            M()->commit();
            return $this->success('绑定地址成功');
        }
    }

    //认证预约
    public function upgradeAppointment(){


        $now = time();
        $day = 3600*24;
        $total = 9;
        $days =array() ;
        $weekarray=array("日","一","二","三","四","五","六");

        for ( $i=0;$i<9;$i++ )
        {
            $timer = $now+$day*$i;
            $num= date("N",$timer)-2; //周一开始
            if($num>=-1 and $num<=3)
            {
                if(count($days)>=10) break;
                $days[$i]['date']=date("Y-m-d",$now+$day*$i);
                $days[$i]['week'] = '星期'.$weekarray[date("w",$now+$day*$i)];
                $days[$i]['date_time'] = $i;
                $total +=1 ;
            }
            else
            {
                $total = $total==9 ?$total+1:$total;
            }
        }

        // 星期几
        $hours = array(
            '11:00:00 -- 12:00:00',
            '12:00:00 -- 13:00:00',
            '13:00:00 -- 14:00:00',
            '14:00:00 -- 15:00:00',
            '15:00:00 -- 16:00:00',
            '16:00:00 -- 17:00:00',
        );

        foreach($days as $day)
        {
            foreach($hours as $k=>$hour)
            {
                $data = M('UpgradeAppointment')->where(array('day'=>$day['date'],'hour_id'=>$k))->field('id')->find();
                if(!$data)
                {
                    $data['day'] = $day['date'];
                    $data['hour'] = $hour;
                    $data['quantity'] = 10;
                    $data['available'] = 0;
                    $data['hour_id'] = $k;
                    $Model = new \Think\Model();
                    $Model->execute("INSERT INTO trade_upgrade_appointment (day,hour_id,hour,quantity,available,week) VALUES  ('{$data['day']}','{$data['hour_id']}','{$data['hour']}','{$data['quantity']}','{$data['available']}','{$day['week']}')");
                }
            }
        }

        $data = reset($days);
        if( IS_POST ){
            $where['day'] = I('day');
            $this->assign('day',I('day'));
            $data = M('UpgradeAppointment')->where($where)->select();
        }else{
            $data = M('UpgradeAppointment')->where(array('day'=>$data['date']))->select();
        }
        $this->assign('data1',$data);
        $this->assign('days',$days);
        $this->display();
    }



    /**
     * 创建最近十个工作日的数据
     * @param
    */
    private  static function createDateTenWorKDay()
    {
    	//从指定的日期开始创建时间日期  创建最近的十天数据
    	$data = M('UpgradeAppointment')->order("id desc")->field('day')->find();

        if ($data)
        {
           $day=date('Y-m-d',strtotime("{$data['day']}"));
        }
        else
        {
        	//不存在
        	$day=date('Y-m-d');
        }

    	$weekarray = [
    			'1' =>"星期一",
    			'2' =>"星期二",
    			'3' =>"星期三",
    			'4' =>"星期四",
    			'5' =>"星期五"
    	];
    	$hours = array(
    			'11:00:00 -- 12:00:00',
    			'12:00:00 -- 13:00:00',
    			'13:00:00 -- 14:00:00',
    			'14:00:00 -- 15:00:00',
    			'15:00:00 -- 16:00:00',
    			'16:00:00 -- 17:00:00',
    	);

    	$res=[];
    	for($i=1;$i<=14;$i++)
    	{
    		$value=date('Y-m-d',strtotime("$day+$i day"));
    		$key= date('w', strtotime("$day+$i day"));

    		if($key!=6 && $key!=0)
    		{

    			$res[$i]=['week'=>$weekarray[$key],'date'=>$value];
    		}
    	}

       //取前七个数据
       $arr=  array_slice($res,0,7);
       foreach ($arr as $k=>$v)
       {
       	    foreach ($hours as $kk=> $vv)
       	    {
       	    	$inarr[$kk]['week']=$v['week'];
       	    	$inarr[$kk]['day']=$v['date'];
       	    	$inarr[$kk]['hour_id']=$kk;
       	    	$inarr[$kk]['hour']=$vv;
       	    	$inarr[$kk]['quantity']=10;
       	    	$inarr[$kk]['available']=0;
       	    }
       	  $res=M('UpgradeAppointment')->addAll($inarr);
       	  unset($inarr);   //释放该变量
       }
       return  $arr;
    }

    //提交预约升级
    public function subUpgrade()
    {
        if(IS_AJAX && isset($_POST['date']))
        {
            $Model = new \Think\Model();
            foreach($_POST['date'] as  $data )
            {
                $Model->execute("UPDATE trade_upgrade_appointment SET available='".$data['2']."',quantity='".$data['1']."' where id=".$data['0']);
            }
            echo json_encode(array('static'=>1,'info'=>'操作成功!'));
            exit;
        }
        else
        {
            echo json_encode(array('static'=>202,'info'=>'數據錯誤!'));
        }
    }
    //用户积分列表
    public function upgrade(){
        $now = time();
        $day = 3600*24;
        $total = 9;
        $days =array() ;
        $weekarray=array("日","一","二","三","四","五","六");

        for ($i=0;$i<9;$i++)
        {
            $timer = $now+$day*$i;
            $num= date("N",$timer); //周一开始
            if( $num>=1 && $num<=5 )
            {
                if(count($days)>=10) break;
                $days[$i]['date']=date("Y-m-d",$now+$day*$i);
                $days[$i]['week'] = '星期'.$weekarray[date("w",$now+$day*$i)];
                $days[$i]['date_time'] = $i;
                $total += 1 ;
            }else
            {
                $total = $total==9 ? $total+1 : $total;
            }
        }
        $newArr = [];
        foreach($days as $day){
            $newArr[$day['date']] = $day['date_time'];
        }

        unset($days[0]);
        $startTime = date('Y-m-01', strtotime(date("Y-m-d")));
        $data = M('UpgradeAppointment')->distinct(true)->field('day,week')->where(array('day'=>array('EGT',$startTime)))->select();
        $this->assign('days',$data);
        $this->assign('datea',$days[1]);
        $Model = M('UpgradeAppointment');

        if(IS_POST){
            if( I('date') && I('date') != -1 ){
                $where = "trade_upgrade_appointment.day='".I('date')."' ";
            } elseif( I('date') == -1 ){
            }else{
                $where = "trade_upgrade_appointment.day='".$days[1]."' ";
                $this->assign('datea',$days[1]);
            }
            $array = array(
                '11:00:00 -- 12:00:00'=>0,
                '12:00:00 -- 13:00:00'=>1,
                '13:00:00 -- 14:00:00'=>2,
                '14:00:00 -- 15:00:00'=>3,
                '15:00:00 -- 16:00:00'=>4,
                '16:00:00 -- 17:00:00'=>5
            );

            if($_POST['hour']!=-1){
                if($_POST['hour'] || $_POST['hour']==0){
                    if($where){
                        $where .=' AND ';
                    }
                    $hour = array_search($_POST['hour'],$array);
                    $where .= " trade_upgrade_appointment.hour='".$hour."' ";
                    $this->assign('hourid',$_POST['hour']);
                }
            }
            $count = $Model->join('trade_upgrade_appointment_detail ON trade_upgrade_appointment_detail.upgrade_appointment_id = trade_upgrade_appointment.id')->where($where)->count();

            $this->assign('datea',$_POST['date']);
            $this->assign('houra',$newArr[$_POST['hour']]);
            $Page       = new \Think\Page($count,10);
            $show       = $Page->show();
            $list = $Model->join('trade_upgrade_appointment_detail ON trade_upgrade_appointment_detail.upgrade_appointment_id = trade_upgrade_appointment.id')->join('trade_user ON trade_user.uid = trade_upgrade_appointment_detail.uid')->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
            setPageParameter($Page, array('date'=>$_POST['date'],'hour'=>$_POST['hour']));
            $this->assign('list',$list);// 赋值数据集
            $this->assign('page',$show);// 赋值分页输出
            $this->display();
            die;
        }


        $count = $Model->join('trade_upgrade_appointment_detail ON trade_upgrade_appointment_detail.upgrade_appointment_id = trade_upgrade_appointment.id')->count();
        $Page       = new \Think\Page($count,10);
        $show       = $Page->show();

        $list = $Model->join('trade_upgrade_appointment_detail ON trade_upgrade_appointment_detail.upgrade_appointment_id = trade_upgrade_appointment.id')
            ->join('trade_user ON trade_user.uid = trade_upgrade_appointment_detail.uid')->limit($Page->firstRow.','.$Page->listRows)->select();
        setPageParameter($Page, array('date'=>$_POST['date'],'hour'=>$_POST['hour']));
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出

        $this->display();
    }

    //一键锁定
    private function lock_or_unlock( $lock = -1 ){

        if(IS_POST){
            $user_id_data = I('post.jas');
            M()->startTrans();
            $temp_cunn_arr = [];
            $sum = count($user_id_data);
            foreach (  $user_id_data as $key => $value) {
                $id = (int)$value;
                $k =  M('User')->where(array('uid'=>$id))->setField('status',$lock);
                $temp_cunn_arr[] = $k;
                if( $k == 0 || empty($k) || $k == null ){
                    $status = M('User')->where(array('uid'=>$id))->getField('status');
                    if( $status != $lock ){
                        M()->rollback();
                        break;
                    }
                }
            }

            if( count($temp_cunn_arr) == $sum ){
                M()->commit();
                $data['static']=1;
                $data['info'] ="操作成功";
            }else{
                $data['static']=0;
                $data['info'] ="操作失敗";
            }
            $this->ajaxReturn($data);
        }
    }

    public function user_lock(){
        $this->lock_or_unlock(-1);
    }
    ///一键解锁////////
    public function user_unlock() {
        $this->lock_or_unlock(1);
    }
    /*
     * 李江
     * 2017年8月18日15:02:00
     * 查看用户短信
     *
     */
    public function showUserPhone(){
        $uid = trim(I('uid'));
        if( isset($uid) && !empty($uid) ){
            $where['uid'] = I('uid');
        }else{
            $where[] = "1=1";
        }

        $tableName = 'SmsLog';
        $order = 'id desc';
        $log_info_list = $this->getUserInfo($where,$tableName,$order);
        $log_list = $log_info_list['list'];
        $page = $log_info_list['page'];
        $UserModel = D('User');
        foreach ($log_list as &$vo){
            $user_list = $UserModel->getUserByUid($vo['uid']);
            $vo['username'] = $user_list['username'];
        }
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('log_list',$log_list);
        $this->display();
    }

    /*
    * 杨璐
    * 2020年6月12日
    * 封装查询数据的方法
    */
    protected function getUserList($where,$tableName,$order){
        $tableModel = M($tableName);
        if ($where==200)
        {
            $count = $tableModel->count();
            $Page       = new Page($count,15);
            $show       = $Page->show();// 分页显示输出
            $list = $tableModel->order($order)
                ->join("left join trade_admin_user as b on trade_user.invite_code = b.invite_code")
                ->limit($Page->firstRow.','.$Page->listRows)
                ->field("trade_user.*,b.nickname")
                ->select();
        }
        else
        {
            $count = $tableModel->where($where)->count();
            $Page       = new Page($count,15);
            $show       = $Page->show();// 分页显示输出
            $list = $tableModel
                ->where($where)
                ->order($order)
                ->join("left join trade_admin_user as b on trade_user.invite_code = b.invite_code")
                ->limit($Page->firstRow.','.$Page->listRows)
                ->field("trade_user.*,b.nickname")
                ->select();
        }
        return ['list'=>$list,'page'=>$show];
    }
    protected function getUserListLogin($where,$tableName,$order){
        $tableModel = M($tableName);
        if ($where==200)
        {
            $count = $tableModel->count();
            $Page       = new Page($count,15);
            $show       = $Page->show();// 分页显示输出
            $list = $tableModel->order($order)
                ->join("left join trade_admin_user as b on trade_user.invite_code = b.invite_code left join trade_user_log as l on trade_user.uid = l.uid")
                ->limit($Page->firstRow.','.$Page->listRows)
                ->field("trade_user.*,b.nickname,l.add_time")
                ->select();
        }
        else
        {
            $count = $tableModel->where($where)->count();
            $Page       = new Page($count,15);
            $show       = $Page->show();// 分页显示输出
            $list = $tableModel
                ->where($where)
                ->order($order)
                ->join("left join trade_admin_user as b on trade_user.invite_code = b.invite_code left join trade_user_log as l on trade_user.uid = l.uid")
                ->limit($Page->firstRow.','.$Page->listRows)
                ->field("trade_user.*,b.nickname,l.add_time")
                ->select();
        }
        return ['list'=>$list,'page'=>$show];
    }
    /*
     * 李江
     * 2017年10月12日12:17:18
     * 封装查询数据的方法
     */
    protected function getUserInfo($where,$tableName,$order){
        $tableModel = M($tableName);
        if ($where==200)
        {
        	$count = $tableModel->count();
        	$Page       = new Page($count,15);
        	$show       = $Page->show();// 分页显示输出
        	$list = $tableModel->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();
        }
        else
        {
        	$count = $tableModel->where($where)->count();
        	$Page       = new Page($count,15);
        	$show       = $Page->show();// 分页显示输出
        	$list = $tableModel->where($where)->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();
        }
        return ['list'=>$list,'page'=>$show];
    }
    /*
     * 李江
     * 2017年8月25日12:08:07
     * 审核
     */
    public function examine(){
        if( IS_AJAX ){
            $input = I('post.');
            $id = $input['id'];
            $status = $input['status'];
            $data['approval_status'] = $status;
            $res = M('UpgradeAppointmentDetail')->where(['id'=>$id])->save($data);
            if( $res ){
                $this->ajaxReturn(['status'=>1,'info'=>'審核成功']);
            }else{
                $this->ajaxReturn(['status'=>1,'info'=>'審核失敗']);
            }
        }
    }

    /**
     * 客服手动解封p2p
     */
    public function dishonestyTrade()
    {
    	 if(IS_AJAX)
    	 {
    	 	 $uid=I('post.uid');

    	 	 $updateOverTime=[
    	 	 	 'overtime_num'=>0,
    	 	 	 'overtime_time'=>0,
    	 	 ];
    	     $userInfo=M('User')->where(['uid'=>$uid])->find();

    	     if(!$userInfo)
    	     {
                return $this->error("无该用户");
    	     }
    	     $ret= M('User')->where(['uid'=>$uid])->save($updateOverTime);
    	     if($ret)
    	     {
    	     	return $this->success("解封成功");
    	     }
    	 	 return $this->success("解封失败");
    	 }
    }

    /**
     * 解封令牌和登陆密码错误次数
     * author zhanghanwen
     */
    public function unseal_token(){
        $uid = I('uid');
        $redis = RedisCluster::getInstance();

        $errorNum = $redis->get(redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM.$uid);
        $loginErrorNum = $redis->get(redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM.$uid);
        if($errorNum>=5 || $loginErrorNum >= 5){
            $redis->del(redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM.$uid); // 重置错误口令次数
            $redis->del(redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM.$uid); // 重置错误密码次数
            die(json_encode(['info'=>'该账号已解封']));
        } else{
            die(json_encode(['info'=>'该账号暂未违规!']));
        }
    }
}
