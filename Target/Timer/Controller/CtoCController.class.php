<?php
namespace Timer\Controller;
use Back\Common\OrderUserMoney;
use Back\Common\CCBreakOrder;
use Back\Tools\SceneCode;
/**
 * @author 建强 2018年3月1日16:48:25
 * @desc   定时任务的脚-处理C2C交易的成交订单
 *  trade_time     '成交时间',
 *  shoukuan_time  '买家确认打款的时间',
 *  end_time       '卖家确认收款时间',
 *  status         '1买入成功 2买家确认打款 3卖家确认收款 4.超时自动撤销 5.待处理 6.管理员撤销订单 7.管理员完成订单',
 *  type            1买  2卖 
 *  小订单问题一律进行退币和手续费的处理  跟主订单没有关系 
 *  定时任务的任务脚本- 负责订单为2状态的数据处理
 */
class  CtoCController extends  RunController
{  
    /**
     * @var array  $time_config      c2c超时配置
     * @var array  $c2c_fee_config   c2c手续费保证金
     * @var array  $currs_name       币种名称信息 
     * @var object $userMoneyObj     设置用户资金余额
     * @var array  $pushJiGuangMsg   极光推送数据
     * @var array  $logUpdateInfoArr 记录自动放币处理程序的日志
     */
    protected $time_config;
    protected $c2c_fee_config;
    protected $currs_name;
    protected $userMoneyObj;
    protected $pushJiGuangMsg   = [];
    protected $logUpdateInfoArr = [];
   
    public function __construct(){
     	 parent::__construct();
     	 $this->userMoneyObj=new OrderUserMoney();
     	 $this->setC2CfeeConfig();
     	 $this->setC2CTradeTimeConfig(); 
     	 $this->setCurrensNames();
     }
     /**
      * @method 设置c2c配置交易手续费保证设置
     */
     protected function setC2CfeeConfig(){
         $config=M('CcConfig')->select();
         if(empty($config)) die('--------c2c未配置手续费,保证金');
         $this->c2c_fee_config = array_column($config,null,'currency_id');
     }
     /**
      * @method 设置c2c 交易超时时间 
      */
     protected function setC2CTradeTimeConfig(){
         $config = M('Config')->select();
         if(empty($config)) die('-------网站配置表没配置数据 ');
         $config = array_column($config,'value','key');
         if(empty($config['CC_RECEIPT_TIME'])) die('------没有配置c2c收款超时时间');
         $this->time_config = $config['CC_RECEIPT_TIME']*3600;
     }
     /**
      * @method 设置币种名称
      */
     protected function setCurrensNames(){
         $currs = M('Currency')->field('id,currency_name')->select();
         if(empty($currs)) die('------currency币种表无数据');
         $this->currs_name = array_column($currs,'currency_name','id');
     }
     /**
      * @author 建强 2018年3月16日14:56:29 
      * @method 定时处理    卖家确认收款超时执行自动放币程序
     */
     public function runCoinTobuyer(){
         $overTime = time()-($this->time_config);
         $process_order =[];
	     $limit    = 100;
	     $where    = [
	         'status'=>2,  //买家已经打款
	         'shoukuan_time'=>['BETWEEN',[1,$overTime]]
	     ];
	     $orders   = M('CcTrade')->where($where)->limit($limit)->select();
	     if(empty($orders)) die('--------无订单需要处理');
	     foreach($orders as $key=>$value) $process_order[] = $this->autoGiveCoinTobuyer($value,$key);
         //推送极光给用户
	     $ret=$this->pushJGmsgToUser($this->pushJiGuangMsg); 
     	 //执行结果： 
     	 dump($process_order);
     	 dump($this->pushJiGuangMsg);
     	 dump($this->logUpdateInfoArr);
     	 dump($ret);  
     }
     /**
      *@method 获取主订单信息 cc_order 
      *@param  int id  cc_trade pid
      *@return array 
      */
     protected function getMainC2COrderInfoById($id){
         $field = 'om,bond_num,is_break,status';
         $mainOrderInfo = M('CcOrder')->field($field)->find($id);
         if(empty($mainOrderInfo)) return [];
         return $mainOrderInfo;
     }
     /**
      * @method 获取用户指定币种的余额
      * @param  int $curre_id
      * @param  array $uids
      * @return array
      */
     protected function getUserAccountByUids($curre_id,$uids){
         $where = [
             'currency_id'	=>$curre_id,
             'uid'=>['IN',$uids],
         ];
         $currs  = M('UserCurrency')->field('uid,num')->where($where)->select();
         if(empty($currs)) return [];
         return array_column($currs, 'num','uid');
     }
     /**
      * @method 子订单撤销的处理
      * @param  array  $order cc_trade订单
      * @param  int    i 循环组装数据key
      * @return bool
      */
     protected function autoGiveCoinTobuyer($order,$i){
         //cc_trade 修改
         $dataOrder=[
             'update_time'=>time(),
             'status'=>8    //自动放币
         ];
         $uids         = [$order['sell_id'],$order['buy_id']];
         $userAccount  = $this->getUserAccountByUids($order['currency_type'], $uids);
         $mainOderInfo = $this->getMainC2COrderInfoById($order['pid']);
         
         if(empty($userAccount) || empty($mainOderInfo)){
             return 'cc_trade'.$order['id'].'用户数据异常';
         }
         //公共数据   
         $curr_id      = $order['currency_type'];
         $uid          = $order['buy_id'];
         $order_num    = $order['order_num'];
         $or_id        = $order['id'];
         $or_pid       = $order['pid'];
         $straTans     = [];
         //成交订单类型 买入    (主订单类型是卖出 挂卖单人的需求)
         if($order['type']==1){
             //卖家超时  ,卖家自动放币      (买家获取币 不收取手续费  并且没有保证金)
             $str_content     = "C2C交易买家获取币(系统)";
             $finance_type    = 32;
             $num             = $order['trade_num'];
             $after_money     = bcadd($userAccount[$uid],$num,4);       
            
             M()->startTrans();
             $straTans[]=$this->userMoneyObj->setUserMoney($uid,$curr_id,$num); 
             $straTans[]=$this->userMoneyObj->AddFinanceLog($uid,$curr_id,$finance_type,$str_content,1,$num,$after_money,$order_num);
             //卖家处罚
             CCBreakOrder::addBreakTimeNum($order['sell_id']);
             //买家不罚 增加交易次数
             CCBreakOrder::addBreakTimeNumForbuyAddOneSmallOrderTime($uid);  //只加1次
             $straTans[]=M('CcTrade')->where(['id'=>$order['id']])->save($dataOrder);
         }
         //成交订单类型为卖出   (买家正常收币 需要扣手续费  卖家3+1污点处理 )
         if($order['type']==2){
             $bond_num     = $mainOderInfo['bond_num'];
             $num          = $order['trade_num'];  
             $buy_fee      = $order['buy_fee'];
             
             $real_num     = bcsub($num, $buy_fee,8); //买家减去手续费;
             $user_num     = $userAccount[$uid];
           
             //财务日志余额
             $after_money         = bcadd($user_num, $num,8);           //第一次账户余额加上
             $after_fee_money     = bcsub($after_money, $buy_fee,8);    //第二次减去手续费
             $after_bond_num_money= bcadd($after_fee_money,$bond_num,8);//加上退还保证金
             
             //判断买家是否可以退回保证金
             $is_break= $mainOderInfo['is_break'];
             $status  = $mainOderInfo['status'];
             $bond_tag= $this->checkCanRebackUserBondNum($or_id,$or_pid,$is_break,$status);
             
             //财务日志类型  
             $str_content          = "C2C买家获取币(系统)";
             $fee_content          = "C2C买家获取币扣除手续费(系统)";
             $fee_bond_num_content = "C2C买家退还保证金(系统)";
             $finance_type         = 32;
             $finance_type_fee     = 33;
             $finance_type_bond_num= 36; //退还保证金(系统)
             $order_num            = $order['order_num'];
             
             M()->startTrans();
             $straTans[] = $this->userMoneyObj->setUserMoney($uid,$curr_id,$real_num); //加上钱 注意是扣除手续费
             //买家入账
             $straTans[] = $this->userMoneyObj->AddFinanceLog($uid,$curr_id,$finance_type,$str_content,1,$num,$after_money,$order_num);
             //收取手续费
             $straTans[] = $this->userMoneyObj->AddFinanceLog($uid,$curr_id,$finance_type_fee,$fee_content,2,$buy_fee,$after_fee_money,$order_num);
             //退保证金
             if($bond_tag==true && $bond_num>0){
                 $brake_bond = ['update_time'=>time(),'is_break'=>1];
                 $straTans[] = $this->userMoneyObj->setUserMoney($uid, $curr_id,$bond_num);   //加上保证金
                 $straTans[] = $this->userMoneyObj->AddFinanceLog($uid, $curr_id, $finance_type_bond_num, $fee_bond_num_content, 1, $bond_num, $after_bond_num_money,$order_num);
                 $straTans[] = M('CcOrder')->where(['id'=>$or_pid])->save($brake_bond);  //主订单
             }
             CCBreakOrder::addBreakTimeNum($order['sell_id']);               //卖家做污点3+1 
             CCBreakOrder::addBreakTimeNumForbuyAddOneSmallOrderTime($uid);  //买家只加1次
             $straTans[] = M('CcTrade')->where(['id'=>$or_id])->save($dataOrder);
         }
         //数据处理失败
         if(in_array(false, $straTans)){
             M()->rollback();
             return false;
         }
         //处理成功 记录日志 
         $this->logUpdateInfoArr[$i] = [
             'trade_order_id'=>$order['id'], 'type'=>$order['type'],
             'sell_id'=>$order['sell_id'],   'buy_id'=>$order['buy_id'],
         ];
         
         //极光推送数据
         $this->pushJiGuangMsg[$i] = [
             'orderNum'    =>$order['order_num_buy'],
             'uid'         =>$order['buy_id'],
             'price'       =>$order['trade_price'],
             'rate_total_money'=>$order['rate_total_money'],
             
              //@att 注意不考虑手续费加减 ,保证金。用交易数量trade_num
             'num'         =>$order['trade_num'],
             'currencyName'=>$this->currs_name[$curr_id],
             'om'          =>'+'.$mainOderInfo['om'],
         ];
         //执行成功 
         M()->commit();
         return true;
     }
     /**
      * @author  建强          2018年5月15日16:17:52 
      * @method  定时任务   撤销不足已生成子订单的发布订单   cc_order表 
     */
     public function revokeMainOrder(){    
         $ret_orders = [];
         $sql        = $this->generateSQL();
         $orders     = M()->query($sql);
         if(empty($orders)) die('------没有符合要求的小订单');
         foreach($orders as $order){
             $ret_orders[] = $this->rebackUser($order);
         }
         //推送极光消息 
         $this->smallOrderpushJGMsg();
         
         dump($orders);
         dump($ret_orders);
         dump($this->smallLeaveNumArr);
     }
     /**
      * @method 根据币种表生成sql 
      * @return string SQL
     */
     protected function generateSQL(){
         $where='';
         foreach($this->c2c_fee_config as $curr_id=>$value){
             $min_money = $value['min_trade_money'];
             $where    .="(currency_type={$curr_id} and (price*leave_num)<{$min_money}) or ";
         }
         $where= trim($where,'or ');
         $sql  = "select * from trade_cc_order where status=1 and ({$where}) limit 100";
         return $sql;
     }
     /**
      * @method 极光推送数据  不足成交的主订单
     */
     protected function smallOrderpushJGMsg(){   
         if(empty($this->smallpushArrMsg)) return '极光推送数据包为空';
	     $result=["server"=>"SendMsgToPersonList"];
	     $pushArr= $temp= [];
	     foreach($this->smallpushArrMsg as $orderValue){
	     	 $temp['uid']=$orderValue['uid'];
	     	 $tempString=explode('&&&',SceneCode::getC2CTradeTemplate($orderValue['tmeplateType'], $orderValue['om'], $orderValue));
	     	 $temp['title']=$tempString[0];
	     	 $temp['content']=$tempString[1];
	     	 $temp['extras']['send_modle']        = 'C2C';
	     	 $temp['extras']['new_order_penging'] = '1';
	     	 $pushArr[]=$temp;
	     }
	     $result['data']['send_msg_list']=$pushArr;
	     $ret = curl_api_post($result);
	     dump($ret);
     }
     /**
      * @method 处理撤销不足成交的主订单 
      * @param  array $order
      */
     private function rebackUser($order){ 
         $id           = $order['id'];
         $uid          = $order['uid'];
         $curr_id      = $order['currency_type'];
        
         $starTrans    = [];  
     	 $where        = ['id'=>$id]; 
     	 $order_status = ['status'=>4,'update_time'=>time()];
     	 
     	 $is_break     = $order['is_break'];
         $type         = $order['type'];
         
     	 M()->startTrans();
     	 //发布订单类型买单 
     	 if($type==1){   
     	     //扣保证金  1.是否存在未完成的子订单（暂时不做处理）   2全部完成 （退回保证金）
     	     $break = $this->checkCanRevokTradeOrder($id,$is_break);
     	     if($break ==2) return '------注意 ：保证金暂时不能退,订单状态不能更改,有可能是存在为完成子订单';
     	     if($break ==3 && $order['bond_num']>0){
     	         $order_status['is_break']= 1;
     	         $extArr = [
     	             'financeType'=>36,
     	             'content'=>'C2C挂单返回保证金(系统撤销)',
     	             'type'   =>1,
     	             'money'  =>$order['bond_num'],
     	             'remarkInfo'=>$order['order_num'],
     	         ];
     	         $starTrans[] = true;
     	         $ret         = $this->setUserMoneyAndAddLog($uid,$curr_id,$extArr);
     	         if(in_array(false, $ret)) $starTrans[]=false;
     	     }
     	     //break =1 不退还保证金 :订单状态修改 
	     }
     	 //发布订单类型卖单 
     	 if($type==2){
     	     //判断如果leave_num 都没有那么不进行处理该数据 
     	     if($order['leave_num']<=0)  return '------卖单 leave_num为0,数据不进行处理,订单id'.$id;
     	     $leave_fee   = $order['leave_fee'];
     		 //卖出扣手续费  直接进行余额的手续费和leave_num的数量
     		 $extArr =[
     		     'financeType'=>34,
     		     'content'=>'C2C挂单返还币(系统撤销)',
     		     'type'=>1,
     		     'money'=>$order['leave_num'],
     		     'remarkInfo'=>$order['order_num']
     		 ];
     		 $ret         = $this->setUserMoneyAndAddLog($uid, $curr_id,$extArr);
     		 $starTrans[] = true;
     		 if(in_array(false, $ret)) $starTrans[]=false;
     		 
     		 //如果剩余手续费大于0，则退手续费
     		 if($leave_fee > 0){
     		     $bondArr=[
     		         'financeType'=>35,'content'=>'C2C挂单返还手续费(系统)',
     		         'type'=>1,'money'=>$order['leave_fee'],
     		         'remarkInfo'=>$order['order_num'],
     		     ];
     		     $ret         = $this->setUserMoneyAndAddLog($uid,$curr_id,$bondArr);
     		     $starTrans[] = true;
     		     if(in_array(false, $ret)) $starTrans[]=false;
     		 }
     	 }
     	 
     	 //更新主订单状态
     	 $starTrans[] = M('CcOrder')->where($where)->save($order_status);
     	 if(in_array(false, $starTrans)){
     	     M()->rollback();
     	     return '撤销处理失败 ,订单id为'.$id;
     	 }
     	 
     	 M()->commit();
     	 //@att 不足以成交的订单  推送模板固定为8   
     	 $this->dataForPush($order, $order['leave_num'],8);
     	 return true;
     }
     /**
      * @method  检验是否可以退保证金退   注意如果状态为is_break=0 并且存在未完成的订单那么不能处理该订单
      * @return  int  1 保证金已经处理   订单状态可以改
      *               2 保证金不能退 ，   订单状态不能改 
      *               3 可以退保证可金 。订单状态可以更改 
     */
     protected function checkCanRevokTradeOrder($id,$is_break){  
         $break_status = [1,2];
         if(in_array($is_break, $break_status)){
             return 1;   //保证金已被收了 。只进行主订单状态更改
         }
	     $where = [
	     	  "pid"=>$id,"status"=>['in',[1,2,5]]
	     ];
	     $count=M('CcTrade')->where($where)->count();
	     if($count>0) return 2;
	     return 3;
     }
     /***
      * @var array $smallLeaveNumArr 记录日志
      * @var array $smallpushArrMsg  推送数据包 
     */
     public $smallLeaveNumArr,$smallpushArrMsg;  
     /**
      * @param 主订单id  array  $order
      * @param 推送数量       float  $num
      * @param 推送模板      int     $templateType
      */
     protected function dataForPush($order,$num,$templateType){
         $id = $order['id'];
         $this->smallLeaveNumArr[$id]=[
	     	'opt_table'=>'order','order_id'=>$id,
            'uid'	=>$order['uid'],'msg'	=>'撤销剩余量的小订单'
	     ];
         $this->smallpushArrMsg[$id]=[
             'type'    =>$order['type'],             //挂买单 扣除保证金
             'orderNum'=>$order['order_num'],
             'uid'     =>$order['uid'],
             'price'   =>$order['price'],
             'num'     =>$num,
             'currencyName'=>$this->currs_name[$order['currency_type']],
             'om'=>'+'.$order['om'],
	     	 'tmeplateType'=>$templateType,
	    ];
     }
     /**
      * @author 建强 2018年3月2日12:05:00 
      * @method 消息推送
      * @param  array $msgArr
      */
     public function pushJGmsgToUser($pushJiGuangMsg){ 
	     $dateIndex = 'send_msg_list';
	     $result    = ["server"=>"SendMsgToPersonList"];
	     $pushArr   = $temp = [];
	     foreach($pushJiGuangMsg as $orderValue){ 
	         $temp['uid']  = $orderValue['uid'];
	     	 $tempString   = explode('&&&',SceneCode::getC2CTradeTemplate(4, $orderValue['om'], $orderValue));
	     	 $temp['title']= $tempString[0];
	     	 $temp['content'] =$tempString[1];
	         $temp['extras']['send_modle']        = 'C2C';
	         $temp['extras']['new_order_penging'] = '1';
	     	 $pushArr[]    =$temp;
	     }
     	 $result['data'][$dateIndex]=$pushArr;
     	 $ret=curl_api_post($result);
     	 return $ret;
     }
   
     /**
      * @author 建强 2018年3月6日17:22:08 
      * @method 检测保证金是否能退 
      * @return bool
     */
     private function checkCanRebackUserBondNum($id,$pid,$is_break,$mainOrderStatus){    
          $where          =[];
          $where['pid']   = $pid;
     	  $where['id']    = ['neq',$id];         //剔除当前的子订单
     	  $where['status']= ['in',[1,2,5]];
     	  //仍旧还在交易
     	  if($mainOrderStatus==1)  return false;
     	  if($is_break==1 || $is_break==2) return false;
     	  $res=M('CcTrade')->where($where)->select();
     	  //存在未完成的订单  不能退保证金
     	  if(count($res)>=1) return false;
     	  return true;	  
     }
     /**
      * @method 设置用户金额 并且添加日志
      * @param  int    $uid
      * @param  int    $currencyId
      * @param  array  $data
      * @param  string $oper
      * @return array
      */
     private function setUserMoneyAndAddLog($uid, $currencyId, $data, $oper='inc'){
     	$logRes   = array();
     	$logRes[] = $this->setUserMoney($uid, $currencyId, $data['money'], 'num', $oper);
     	$data['afterMoney'] = $this->getUserBalance($uid, $currencyId);
     	$logRes[] = $this->AddFinanceLog($uid, $currencyId, $data);
     	return $logRes;
     }
     /**
      * @method 获取用户某币种余额
      * @author lirunqing 2018年3月5日15:47:29
      * @param  int $userId     用户id
      * @param  int $currencyId 币种id
      * @return float
      */
     public function getUserBalance($userId, $currencyId) {
         $currencyWhere                =[];
     	 $currencyWhere['uid']         = $userId;
     	 $currencyWhere['currency_id'] = $currencyId;
     	 $curRes                       = M('UserCurrency')->where($currencyWhere)->find();
     	 return !empty($curRes['num']) ? $curRes['num'] : 0.0000;
     }
     /**
      * 添加财务日志
      * @author lirunqing 2017-10-13T12:20:43+0800
      * @param  int $uid        用户id
      * @param  int $currencyId 币种id
      * @param  array   $dataArr 扩展数组
      *         string  $dataArr['financeType'];日志类型 必传
      *         string  $dataArr['content'];内容 必传
      *         int     $dataArr['type'];类型(收入=1/支出=2) 必传
      *         float   $dataArr['money'];金钱 必传
      *         float   $dataArr['afterMoney'];操作之后的余额 必传
      *         float   $dataArr['remarkInfo'];线下交易的订单号 非必传
      * @return bool
      */
     private function AddFinanceLog($uid, $currencyId, $dataArr=array()) {
     
     	$financeType = $dataArr['financeType'];
     	$content     = $dataArr['content'];
     	$type        = $dataArr['type'];
     	$money       = $dataArr['money'];
     	$afterMoney  = $dataArr['afterMoney'];
     	$remarkInfo  = !empty($dataArr['remarkInfo']) ? $dataArr['remarkInfo'] : 0;
     
     	if (empty($financeType) || empty($content) || empty($type)
     			|| empty($money) || empty($afterMoney) ) {
     				return false;
     			}
     
     			$data = array (
     					'uid'          => $uid,
     					'currency_id'  => $currencyId,
     					'finance_type' => $financeType,
     					'content'      => $content,
     					'type'         => $type,
     					'remark_info'  => $remarkInfo,
     					'money'        => $money,
     					'add_time'     => NOW_TIME,
     			);
     
     			if (!empty($afterMoney)) {
     				$data['after_money'] = $afterMoney;
     			}
     
     			$table     = 'UserFinance';
     			$tableName = getTbl($table,$uid);
     			$res       = M($tableName)->lock(true)->add($data);
     			return $res;
     }
     /**
      * 增加修改个人币种资金信息(缓存)
      * @author lirunqing 2018年3月5日10:08:54
      * @param int $uid	用户id
      * @param int $currencyId	币种id
      * @param string $num		数量
      * @param string $field		类型 num/forzen_num
      * @param string $operationType	运算类型	inc/dec
      * @return boolean
      */
     private function setUserMoney($uid, $currencyId, $num, $field='num', $operationType='inc'){
     
     	if ($field != 'num' && $field != 'forzen_num'){
     		return false;
     	}
     
     	if ($operationType != 'inc' && $operationType != 'dec'){
     		return false;
     	}
     	$userCurrency = M('UserCurrency')->lock(true)->where(array('uid'=> $uid, 'currency_id'=> $currencyId))->find();
     	if( $num == 0 ){
     		return true;
     	}
     	if($operationType == 'inc'){
     		$newNum = bcadd($userCurrency['num'], $num, 8);
     		return  M('UserCurrency')->lock(true)->where(array('uid'=> $uid, 'currency_id'=> $currencyId))->setField($field, $newNum); // 加
     	}
     	// 不能让余额为负数  改为可以让余额为负数
     	$newNum = bcsub($userCurrency['num'], $num, 8);
     	return M('UserCurrency')->lock(true)->where(array('uid'=> $uid, 'currency_id'=> $currencyId))->setField($field, $newNum); //减
     }
}