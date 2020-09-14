<?php
namespace Timer\Controller;
use Back\Tools\SceneCode;
use think\Exception;
/**
 * @author 建强 2018年7月18日19:29:23
 * @method 定时任务的脚本-30min 未收款待处理    12小时未收款 订单改为待处理
 */
class  ProcessedOrderController extends RunController
{
    protected  $currencys;
    protected  static $groupId = 8;   //at 客服组id
    
    protected  $pushArr  = [];
    protected  $reptOrds = [];     //at 重复订单数据 推送订单信息
    protected  $needLog  = 0;
    
    public function __construct(){
        $currenys=M('Currency')->where(['status'=>1])->field('id,currency_name')->select();
        $this->currencys=array_column($currenys, 'currency_name','id');
        parent::__construct();
    }
    /**
     * {@inheritDoc} 
     * @see \Think\Controller::__destruct()
     * @method 批量更新插入工单日志 
     */
    public function __destruct(){
        if($this->needLog == 0) die(' -- 本次没执行分配日志 ');
        $res = $this->SecdInsertFeedbackLog();
        dump('-- 执行订日志记录插入');
        dump($res);
    }
    
    /**
     * @method  30分钟待处理 付款超时（需要推送）
     * @author  建强  2018年10月9日15:33:34
     */
    public function runConfirmPaid(){
        $where  = [
            'status' =>1,'process_uid'=>0,
            'trade_time'=>['BETWEEN',[1,time()-(0.5*3600)]]
        ];
        
        $orders = $this->getOrder($where);
        if(empty($orders)) die('-- 没有符合要求的订单' );
        
        //at 筛选订单    1.存在重复订单    2.新订单 
        $orders = $this->checkP2PRepeatOrder($orders,$this->remarkInfo[1]); 
        if(empty($orders)) {
            $this->pushUserMsg('pay',$this->reptOrds);
            die('-- 没有剩下 的正常订单， 全部是重复订单 ');
        }
        
        //at 正常新订单进行分配
        $msg     = $this->remarkInfo[1]['msg'];
        $uid     = $this->remarkInfo[1]['uid'];
        $ret     = $this->updateTradeAndInsertSys($orders,$msg,$uid);
        
        //at 推送订单消息 
        $data    = array_merge($this->pushArr,$this->reptOrds);
        $pushRet = $this->pushUserMsg('pay',$data);
        
        dump('消息推送结果 ========');
        dump($pushRet);
        dump('修改订单信息========');
        dump($ret);
    }
    /**
     * @method 12小时待处理 收款超时（需要推送）
     * @author 建强  2018年10月9日15:33:34
     */
    public function runConfirmMoney(){
        $where  = [
            'status' =>2,'process_uid'=>0,
            'shoukuan_time'=>['BETWEEN',[1,time()-(12*3600)]]
        ];
        $orders = $this->getOrder($where);
        if(empty($orders)) die('-- 没有符合要求的订单' );
        
        
        //at 筛选订单    1.存在重复订单    2.新订单
        $orders = $this->checkP2PRepeatOrder($orders,$this->remarkInfo[2]);
        if(empty($orders)){
            // at需要推送一次 
            $this->pushUserMsg('confirm',$this->reptOrds);   
            die('-- 没有剩下 的正常订单， 全部是重复订单 ');
        }
        
        //at 正常新订单进行分配
        $msg     = $this->remarkInfo[2]['msg'];
        $uid     = $this->remarkInfo[2]['uid'];
        $ret     = $this->updateTradeAndInsertSys($orders,$msg,$uid);
        
        $data    = array_merge($this->pushArr,$this->reptOrds);
        $pushRet = $this->pushUserMsg('confirm',$data);
        
        dump('消息推送结果 ========');
        dump($pushRet);
        dump('修改订单信息========');
        dump($ret);
    }
    /**
     * @method 获取订单符合要求的订单进行
     * @param  int $hour 超时时间
     * @return array  
     */
    public function getOrder($where){
        return M('TradeTheLine')->where($where)->limit(100)->select();
    }
    //备注信息
    protected $remarkInfo=[
        '1'=>[
            'msg' =>'買家付款超时',
            'uid'=>'buy_id',
        ],
        '2'=>[
            'msg' =>'賣家收款超时',
            'uid'=>'sell_id',
        ],
    ];
    /**
     * @author 建强  2018年10月12日16:30:49
     * @method 二次操作 插入工单日志
     */
    public function SecdInsertFeedbackLog() {
        $where =[
            'deal_order'=>1,       // 待处理工单标识
            'source'    =>3,       // 来源为3  特殊订单
        ];
        
        $field = 'id,f_pid,level_id,status,assign_uid,custom_uid,deal_order' ;
        $feeds = M('Feedback','work_','DB_SYS')->field($field)->where($where)->select();
        
        if(empty($feeds)) return false;
        
        $uids      = array_unique(array_column($feeds, 'custom_uid'));
        $uids_asgn = array_unique(array_column($feeds, 'assign_uid'));
        $uids_arr  = array_merge($uids,$uids_asgn);
        $names     = $this->getNamesByUids($uids_arr);
        
        //組裝工單日志插入
        $feedbackLog =[] ;
        foreach($feeds as $k=>$value) {
            $feedbackLog[$k]['problem_gid']= self::PROBLEM_GID;
            $feedbackLog[$k]['level_id']   = $value['level_id'];
            $feedbackLog[$k]['status']     = 2;
            $feedbackLog[$k]['add_time']   = time();
            $feedbackLog[$k]['feedback_id']= $value['id'];
            
            $feedbackLog[$k]['from_uid']   = $value['custom_uid'];
            $feedbackLog[$k]['describe']   ='系統將工單分配給 '.$names[$value['custom_uid']];
            
            //at 如果是转接 日志格式
            if($value['status']== 4){
                $feedbackLog[$k]['status']     = 4;
                $feedbackLog[$k]['from_uid']   = $value['assign_uid'];
                $feedbackLog[$k]['describe']   = $names[$value['assign_uid']].' 指派至 交易問題 小組'; 
            }
        }
        
        $whereFeedIds =['id'=>['IN', array_column($feeds, 'id')]];
        $updealOrder  =[
            'deal_order' => 0,
            'update_time'=> time(),
        ];
        
        M('FeedbackLog','work_','DB_SYS')->startTrans();
        $r   = [];
        $r[] = M('FeedbackLog','work_','DB_SYS')->addAll($feedbackLog);
        $r[] = M('Feedback','work_','DB_SYS')->where($whereFeedIds)->save($updealOrder);
        
        if(in_array(false, $r)){
            M('FeedbackLog','work_','DB_SYS')->rollback();         
            return false;
        }
        
        M('FeedbackLog','work_','DB_SYS')->commit();
        return true;
    }
    /**
     * @param  array $uids
     * @return array names
     */
    protected function getNamesByUids($uids) {
        
        $where = ['user_id' =>['IN',$uids]];
        $names = M('AdminUser','work_','DB_SYS')->where($where)->field('user_id,username')->select();
        
        return array_column($names, 'username','user_id');
    }
    //交易问题顶级标题
    const TRADESTATUS         = 5;
    
    //P2P交易问题二级标题
    const TRADESTATUS_CID_P2P = 27;
    
    //C2C交易问题二级标题
    const TRADESTATUS_CID_C2C = 28;
    
    //待處理訂單 SOURCE來源
    const ORDER_SOURCE        = 3;
    
    //一線員工交易問題組
    const PROBLEM_GID         = 5;
    
    //一线员工的级别组
    const LEVEL_GID           = 4;
    /**
     * @method 组装数据 进行分配
     * @param array   $ordes
     * @param string  $remark
     * @param int     $uidType
     * @return array
     */
    protected  function updateTradeAndInsertSys($orders,$remark,$uidType){
        //at 在线问题组客服
        $uids        = $this->getAccordUser();
        if(empty($uids))  return  [];
        
        $newFeeds    = []; 
        $str         = '';   
        foreach($orders as $key=>$order) {
            $minValue                 =  min($uids);  
            $user_id                  =  array_search($minValue, $uids);  
            $uids[$user_id]          +=  1;
            
            //待插入工单系统 （不用客服回復）
            $newFeeds[$key]['uid']       = $order[$uidType];
            $newFeeds[$key]['f_pid']     = self::TRADESTATUS;
            $newFeeds[$key]['f_cid']     = self::TRADESTATUS_CID_P2P;
            $newFeeds[$key]['describe']  = 'P2P待處理訂單号 '.$order['order_num'];
            $newFeeds[$key]['status']    = 2 ;       //处理中
            $newFeeds[$key]['add_time']  = time();
            $newFeeds[$key]['source']    = self::ORDER_SOURCE;
            $newFeeds[$key]['custom_uid']= $user_id;
            $newFeeds[$key]['level_id']  = self::LEVEL_GID;
            $newFeeds[$key]['deal_order']= 1;
            $newFeeds[$key]['order_id']  = $order['id'];   //at订单id
            $newFeeds[$key]['order_num']  = $order['order_num'];
            
            //更新数据sql组装
            $tmpId          = $order['id'];                           
            $tmpremark      ='用户uid '.$order[$uidType].' , '.$remark; 
            $str           .=' WHEN '.$tmpId .' THEN '. "'{$tmpremark}'";
        }
        
        //执行操作
        $ids  = array_column($orders,'id');
        $sql  = 'UPDATE trade_trade_the_line SET status=8, process_uid=1,remark_info = CASE id'.  $str. ' END  WHERE id IN ('.implode(',', $ids).')';
        
        M()->startTrans();
        $ret = M()->execute($sql);
        
        if(empty($ret)){
            M()->rollback();
            return [ 'msg' =>'訂單分配失敗，事物失败  ' , 'code'=>206,'data'=>0];
        }
        
        $newFeeds  = array_values($newFeeds);
        $insrtRet = M('Feedback','work_','DB_SYS')->addAll($newFeeds);
        if(empty($insrtRet)){
            M()->rollback();
            return ['msg' =>'訂單分配失敗  ,事物2失败 回滚数据'  ,'code'=>207,'data'=>0];
        }
        
        //sql 执行成功
        M()->commit();
        $this->needLog = 1; 
        $this->pushArr = $orders;
        
        return ['msg'  =>'訂單分配成功  ' ,'code' =>200,'data' => $ret];
    }
    /* ==============================
     * | C2C待处理订单分配  跨数据操作                        |
     * ==============================
     * c2c模式待处理订单分配   批量更新sql  c2c
     */
    
    public function runC2C(){
        try{
            $where  = [ 'status'=>5 ,'process_uid'=>0 ];
            $orders = M('CcTrade')->field('id,buy_id,order_num_buy')->where($where)->limit(100)->select();
            if(empty($orders)) die('---C2C 无符合要求的订单分配');
        
            //at 分成两部分处理 
            $orders = $this->checkc2cFeeds($orders);
            
            //at 正常订单空  刚好全部是重复订单
            if(empty($orders)) die(' -- 刚好全部是重复订单') ;
            
            $initDataProblem = $this->getAccordUser();
            if(empty($initDataProblem)) die('--交易组客服都不在线');
            
            $ret = $this->chunkOrder($orders ,$initDataProblem);
            
            //执行结果
            dump('执行结果');
            dump($ret);
            
        } catch(Exception $e){
            echo $e->getMessage();
        }
    }
    
    /**
     * @method c2c   订单排除重复订单 
     * @param  array $order
     * @return array 可正常分配的订单 
     */
    protected function checkc2cFeeds($order) {
         foreach ($order as $key=>$value) {
             $bool = $this->c2cOrderisHas($value);
             if($bool) unset($order[$key]);
         }
         return $order;
    }
    /**
     * @method 分配工单或者转介
     * @param  $order
     * @return bool 
     */
    protected function c2cOrderIsHas($order){
        $where = [
            'order_id'=>$order['id'],
            'f_cid'   =>self::TRADESTATUS_CID_C2C,
        ];
        
        $res = M('Feedback','work_','DB_SYS')->field('custom_uid,status,order_id,level_id')->where($where)->select();
        //没有重复 订单 可以进行正常分配
        if(empty($res)) return false;
        
        $status_arr = array_column($res, 'status');
        if(in_array(4, $status_arr)) return true;   //存在转接 ,暂时不分配 返回true 过滤掉
        
        //判斷客服是否在線 有權限分配
        $cust_status_uid    = array_unique(array_keys(array_column($res,'status','custom_uid')));
        $where_uids         = [
            'p.user_id'       =>['IN',$cust_status_uid],
            'p.problem_gid'   => 5,   //交易問題類型分組
            'p.status'        => 0,
        ];
        
        $cust_list = M('ProblemUser','work_','DB_SYS')->alias('p')
            ->join('INNER JOIN __AUTH_GROUP_ACCESS__ AS a ON a.uid =p.user_id')
            ->join('INNER JOIN __ADMIN_USER__ AS u ON u.user_id =a.uid')
            ->field('p.user_id,u.duty')->where($where_uids)->group('p.user_id')->select();
        
            
        // at 无一线客服在权限组
        if(empty($cust_list)){
            // at 是否存在管理员接此单
            $level_user = array_column($res,'level_id','custom_uid');
            if(in_array(3,$level_user)) {
                return $this->c2c_dataToDBsave($order,array_search(3,$level_user),0,3,2);
            }
            // at 客服离职 ，没有被人接单    转介
            return $this->c2c_dataToDBsave($order,0,$cust_status_uid[0], 4, 4);
        }
        // at 区分上班不上班
        $user_duty = array_column($cust_list,'duty','user_id');
        if(in_array(1, $user_duty)){
            //at 分配给上班的人
            return $this->c2c_dataToDBsave($order, array_search(1,$user_duty),0, 4, 2);
        }
        // at 没有人在上班  转介
        return $this->c2c_dataToDBsave($order,0,array_search(0,$user_duty), 4, 4);
    }
  
    /**
   
     * @author 建强  2019年7月2日13:02:26
     * @method c2c订单业务 待分配数据入库 
     * @param int    $uid
     * @param int    $order_id
     * @param string $order_num
     * @param int    $c_uid
     * @param int    $ass_uid
     * @param int    $level_id
     * @param int    $status
     * @return boolean
     */
    private function c2c_dataToDBsave($order,$c_uid,$ass_uid,$level_id,$status){
        
        $newFeed = [
            'uid'       => $order['buy_id'],
            'f_pid'     => self::TRADESTATUS,
            'f_cid'     => self::TRADESTATUS_CID_C2C,
            'describe'  =>'C2C待處理訂單号 : '  .$order['order_num_buy'],
            'status'    => $status,      //指派状态
            'add_time'  => time(),
            'source'    => self::ORDER_SOURCE,
            'custom_uid'=> $c_uid,
            'level_id'  => $level_id,
            'deal_order'=> 1,
            'assign_uid'=>$ass_uid,
            'order_id'  =>$order['id'],
            'order_num' =>$order['order_num_buy'],
            
        ];
        
        $save = ['update_time' =>time(),'process_uid' =>1];
        M('Feedback','work_','DB_SYS')->startTrans();
        $ret = M('Feedback','work_','DB_SYS')->add($newFeed);
        if(empty($ret)){
            M('Feedback','work_','DB_SYS')->rollback();
            return true;
        }
        
        $r = M('CcTrade')->where(['id'=>$order['id']])->save($save);
        if(empty($r)){
            M('Feedback','work_','DB_SYS')->rollback();
            return true;
        }
        
        //成功
        $this->needLog =1;
        M('Feedback','work_','DB_SYS')->commit();
        return true;
    }
    
    /**
     * @method 获取符合要求的在线客服  交易问题分组  组装客服数据
     * @author 建强  2018年10月9日12:23:44
     * @return array
     */
    protected function getAccordUser(){
        $problemGid  =  5 ;   //交易问题分组id
        $where=[
            'p.problem_gid' => $problemGid,
            'p.status'      => 0,
            'a.duty'        => 1,
            'a.status'      => 1  //賬號正常
        ];
        //获取所有在线
        $onlineUser = M('ProblemUser','work_','DB_SYS')->alias('p')
            ->join('INNER JOIN __AUTH_GROUP_ACCESS__ AS g ON g.uid = p.user_id')
            ->join('INNER JOIN __ADMIN_USER__ AS a ON a.user_id    = g.uid')
            ->field('distinct p.user_id,p.problem_gid')
            ->where($where)->select();
        
        //没有客服在线
        if(empty($onlineUser)) return [];
        
        $uids      = array_column($onlineUser, 'user_id');
        $whereDeal = [
            'custom_uid'=> ['IN',$uids],
            'status'    => 2,
            'f_pid'     => $problemGid,
        ];
        //查询客服正在处理订单数
        $dealOrder = M('Feedback','work_', 'DB_SYS')
            ->field('count(id) as c , custom_uid')
            ->where($whereDeal)
            ->group('custom_uid')
            ->select();
        
        $initProblemdata = [];
        //无正在处理的订单  所有客服均可     初始化值
        if(empty($dealOrder))  return $this->initDataProblem($uids);
        $dealOrderCount  = array_column($dealOrder, 'c','custom_uid');
        //组装数据   填充数量
        foreach ($uids as $value){
            if(isset($dealOrderCount[$value])){
                $initProblemdata[$value] = $dealOrderCount[$value] ; //数量
                continue;
            }
            $initProblemdata[$value] = 0;
        }
        return  $initProblemdata;
    }
    /**
     *@author 建强  2018年10月11日15:58:07
     *@param  $uids array
     *@return array  初始化值  填充工单数量 0
     */
    protected function initDataProblem($uids){
        $initDataProblem  = [];
        foreach($uids as $value){
            $initDataProblem[$value] =0 ;
        }
        return $initDataProblem;
    }
    /**
     * @author 建强 2018年10月9日14:22:53
     * @method 分配订单组
     * @param  $ids  array 订单id
     * @param  $uids array 客服uid
     * @return bool
     */
    protected function chunkOrder($orders,$initDataProblem){
        
        $time = time();
        $insertFeedback = [];
        $idsArr  = array_column($orders, 'id');
        
        $sql     = 'UPDATE trade_cc_trade SET update_time='.$time.', process_uid =1 where id IN ('.implode(',', $idsArr).')';
        $i       = 0;
        //单个分配订单
        foreach($orders as $order_id)
        {
            $minCount = min($initDataProblem);                     //取最小的数量
            $user_id  = array_search($minCount, $initDataProblem); //取user_id
            $initDataProblem[$user_id]+=1;                         //数量累加1
            
            //待插入工单系统 （不用客服回復）
            $insertFeedback[$i]['uid']       = $order_id['buy_id'];
            $insertFeedback[$i]['f_pid']     = self::TRADESTATUS;
            $insertFeedback[$i]['f_cid']     = self::TRADESTATUS_CID_C2C;
            $insertFeedback[$i]['describe']  = 'C2C待處理訂單号 : '  .$order_id['order_num_buy'] ;
            $insertFeedback[$i]['status']    = 2 ;                        //处理中
            $insertFeedback[$i]['add_time']  = time();
            $insertFeedback[$i]['source']    = self::ORDER_SOURCE;
            $insertFeedback[$i]['custom_uid']= $user_id;
            $insertFeedback[$i]['level_id']  = self::LEVEL_GID;
            $insertFeedback[$i]['deal_order']= 1;
            $insertFeedback[$i]['order_id']  = $order_id['id']; //订单id 
            $insertFeedback[$i]['order_num'] = $order_id['order_num_buy'] ;
            
            $i++;
        }
        
        $this->sql   = $sql;
        $this->order = $orders;
        
        M()->startTrans();
        $ret = M()->execute($sql);
        if(empty($ret))
        {
            M()->rollback();
            return [
                'code'=>206,
                'msg' =>'派单失败' ,
            ];
        }
        
        $insrtRet = M('Feedback','work_','DB_SYS')->addAll($insertFeedback);
        if(empty($insrtRet)) {
            M()->rollback();
            return [
                'code'=>207,
                'msg' =>'派单失败,二次事物操作失败' ,
            ];
        }
        
        M()->commit();
        $this->needLog = 1;
        return [
            'code'=>200,
            'msg' =>'派单成功' ,
        ];
    }
    
    //===========================如上是主体业务====================================
    protected  $templateArrKey=[
        'pay'=>[
            'buy_id'=>6,
            'sell_id'=>7
        ],
        'confirm'=>[
            'buy_id'=>8,
            'sell_id'=>9
        ],
    ];
    /**
     * @method 推送P2P信息
     * @param  array  $proType
     * @param  array  $orders
     * @return array 
     */
    protected function pushUserMsg($proType , $orders){
        
        if(empty($orders))  return '无订单推送信息';
        $pendingPush = [ "server"=>"SendMsgToPersonList"];
        $pushMsgQueen= [];
        //组装二维数据
        foreach($orders as $order){
            $pushMsgQueen[] = $this->getBuyidOrSellid($order,'buy_id',$this->templateArrKey[$proType]['buy_id'],0);
            $pushMsgQueen[] = $this->getBuyidOrSellid($order,'sell_id',$this->templateArrKey[$proType]['sell_id'],1);
        }
        
        $pendingPush['data']['send_msg_list'] = $pushMsgQueen;
        $ret = curl_api_post($pendingPush);
        return $ret;
    }
    /**
     * @method 组装推送数据
     * @param  array   $pushInfo
     * @param  string  $uidType
     * @param  int     $typeTemplate
     * @param  string  $exOrderNum
     * @return string
     */
    protected  function getBuyidOrSellid($pushInfo,$uidType='buy_id',$typeTemplate,$exOrderNum){
        
        $temp       = [];
        $temp['uid']= $pushInfo[$uidType];
        $pushInfo['orderNum']    = explode('-',$pushInfo['order_num'])[$exOrderNum];
        $pushInfo['currencyName']= $this->currencys[$pushInfo['currency_id']];
        $tempString              = explode('&&&',SceneCode::getP2PTradeTemplate($typeTemplate, '+'.$pushInfo['om'], $pushInfo));
        $temp['title']  = $tempString[0];
        $temp['content']= $tempString[1];
        $temp['extras']['send_modle']        = 'P2P';
        $temp['extras']['new_order_penging'] = '1';
        return $temp;
    }
    /**
     * @author 建强  2019年6月25日17:14:37
     * @method 单个订单分配
     * @return array 返回剩余的订单
     */
    public function checkP2PRepeatOrder($orders,$mark){
        foreach ($orders as $key => $value) {
            $bool= $this->checkP2PIsHas($value,$mark);
            if($bool) unset($orders[$key]);
        }
        return $orders;
    }
    /**
     * @method 查询该订单是否存在 并且返回 
     * @param  int $order_id P2P
     * @param  int $type
     * @return bool
     */
    protected function checkP2PIsHas($order,$mark){
         $where = [
             'order_id' => $order['id'],
             'f_cid'    => self::TRADESTATUS_CID_P2P
         ]; 
         
         $res = M('Feedback','work_','DB_SYS')->field('custom_uid,status,order_id,level_id')->where($where)->select();
         //没有重复 订单 可以进行正常分配  
         if(empty($res)) return false;
         $status_arr = array_column($res, 'status');
         if(in_array(4, $status_arr)) return true;   //存在转接 ,暂时不分配 返回true 过滤掉 
         
         //判斷客服是否在線 有權限分配
         $cust_status_uid    = array_unique(array_keys(array_column($res,'status','custom_uid')));
         $where_uids         = [
             'p.user_id'       =>['IN',$cust_status_uid],
             'p.problem_gid'   => 5,   //交易問題類型分組
             'p.status'        => 0,
         ];
         
         $cust_list = M('ProblemUser','work_','DB_SYS')->alias('p')
             ->join('INNER JOIN __AUTH_GROUP_ACCESS__ AS a ON a.uid =p.user_id')
             ->join('INNER JOIN __ADMIN_USER__ AS u ON u.user_id =a.uid')
             ->field('p.user_id,u.duty')->where($where_uids)->group('p.user_id')->select();
         
         $uidType  = $mark['uid'];
         $remark   = $mark['msg'];
         $save     = [
            'remark_info'=> '用户uid '.$order[$uidType].' , '.$remark,
            'status'=>8, 'process_uid'=>1
         ];
         
         // at 无一线客服在权限组 
         if(empty($cust_list)){
             // at 是否存在管理员接此单
             $level_user = array_column($res,'level_id','custom_uid');
             if(in_array(3,$level_user)) {
                 return $this->_dataToDBsave($save,$order[$uidType],$order['id'],$order['order_num'],array_search(3,$level_user),0,3,2);
             }
             // at 客服离职 ，没有被人接单    转介
             return $this->_dataToDBsave($save,$order[$uidType],$order['id'],$order['order_num'],0,$cust_status_uid[0], 4, 4);
         }
         
         $user_duty = array_column($cust_list,'duty','user_id');
         if(in_array(1, $user_duty)){
             //at 分配给上班的人
             return $this->_dataToDBsave($save,$order[$uidType],$order['id'],$order['order_num'], array_search(1,$user_duty),0, 4, 2);
         }
         // at 没有人在上班  转介
         return $this->_dataToDBsave($save,$order[$uidType],$order['id'],$order['order_num'],0,array_search(0,$user_duty), 4, 4);
    } 
    /**
     * @method 组装工单数据  进行数据库操作入库   
     * @param array  $save 订单处理
     * @param int    $uid  卖家还是买家
     * @param int    $order_id 订单主键id
     * @param string $order_num 订单号
     * @param int    $c_uid    分配客服uid
     * @param int    $ass_uid  指派客服uid
     * @param int    $level_id 级别
     * @param int    $status   订单状态 转介还是处理中
     * @return boolean
     */
    private function _dataToDBSave($save,$uid,$order_id,$order_num,$c_uid,$ass_uid,$level_id,$status){
        $newFeed = [
            'uid'       => $uid,
            'f_pid'     => self::TRADESTATUS,
            'f_cid'     => self::TRADESTATUS_CID_P2P,
            'describe'  => 'P2P待處理訂單号 : '.$order_num,
            'status'    => $status,      //指派状态
            'deal_order'=> 1,
            'add_time'  => time(),
            'source'    => self::ORDER_SOURCE,
            'custom_uid'=> $c_uid,
            'level_id'  => $level_id,
            'assign_uid'=> $ass_uid,
            'order_num' => $order_num,
            'order_id'  => $order_id,
        ];
        
        M('Feedback','work_','DB_SYS')->startTrans();
        $r = M('Feedback','work_','DB_SYS')->add($newFeed);
        
        if(empty($r)){
            M('Feedback','work_','DB_SYS')->rollback();
            return true;
        }
        
        $row = M('TradeTheLine')->where(['id'=>$order_id])->save($save);
        if(empty($row)){
            M('Feedback','work_','DB_SYS')->rollback();
            return true;
        }
        
        $this->needLog = 1;
        M('Feedback','work_','DB_SYS')->commit();
        
        //at 全局保留 订单信息 /推送
        $this->reptOrds[] = $order;
        return true;
    }
}