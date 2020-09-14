<?php
/**
 * Created by PhpStorm.
 * User: 劉富國
 * Date: 2017/9/26
 * Time: 17:04
 * 線下交易 訂單 處理
 */
namespace Back\Logic;
use Back\Common\OrderUserMoney;
use Back\Common\OperateLog;
use Common\Api\RedisIndex;
use Common\Api\RedisCluster;
use Think\Model;
use Back\Tools\Point;

class SubOrder  extends BaseLogic {
    const   ST_ACTIVITY_NONE    = 3; //不需要處理
    const   ST_BUY_SEALED   = 1; //買家封號
    const   ST_SELL_SEALED  = 2; //賣家封號
    const   ST_CREDIT_LEVEL_INC = 1; //加積分
    const   ST_CREDIT_LEVEL_DEC = 2; //減積分
    const   ST_SELL_CURRENCY_INC    = 1; //賣家對應訂單返錢
    const   ST_BUY_CURRENCY_INC = 2; //買家對應訂單返錢
    const   ST_FINANCE_INCOME   = 1;
    const   ST_FINANCE_EXPENSE  = 2;
    protected   $sell_id    = 0;
    protected   $buy_id     = 0;
    protected   $order_id   = 0;
    protected   $currency_id = 0;
    protected   $num = 0;
    protected   $buy_fee = 0; //买入手续费
    protected   $sell_fee = 0; //卖出手续费
    protected   $order_num  = '';
    protected   $order_info = array();
    protected   $sealed_id = 0; //封号的用户ID
    protected   $buyer_credit_level = 0; //买家积分
    protected   $seller_credit_level = 0; //卖家积分
    protected   $redis_cluster_obj =NULL;

    protected   $sealed_act = 0;  //封号操作
    protected   $buy_credit_Level_act   = 0;  //买家积分操作
    protected   $sell_credit_Level_act  = 0;  //卖家积分操作
    protected   $order_act  = 0; // 訂單處理，6後臺客服撤銷訂單,4後臺確認訂單完成,8訂單待處理
    protected   $capital_flow_act   = 0;  //资金流向操作，（1為退回賣家，2為增加買家）

    protected   $_order_user_money_obj = null;
    
    
    // 2019年5月23 原始订单状态 
    protected   $order_orginal_status = null; 
    protected   $rate_total_money     = 0;
    //汇率
    public      $rate_usd = null;
    
    
    //訂單操作數組：鍵值為操作狀態，值為訂單狀態
    protected $st_order_act_status_arr = array(
        6 => '6', //後臺客服撤銷訂單
        4 => '4', //後臺確認訂單完成
        8 => '8' //後臺待處理
    );
    //日誌狀態名稱
    protected $log_order=[
        '6'=>'後臺客服撤銷訂單',
        '4'=>'後臺確認訂單完成',
        '8'=>"後臺待處理",
    ];
    //對出售的金蔽的處理
    protected $log_capital_flow=[
        '1'=>'退回賣家',
        '2'=>'增加買家',
        '3'=>'無',
    ];

    public  function  __construct(array $data = []){
        parent::__construct($data);
        $this->_order_user_money_obj = new OrderUserMoney();
        $this->redis_cluster_obj  = RedisCluster::getInstance();
    }

    /**
     * 编辑订单状态
     * @author 刘富国 2018-06-12T11:59:28+0800
     * @return [type] [description]
     */
    public function editOrder(){
        $data       = $this->getData();
        $order_id   = $data['id']*1;  //訂單id
        if($order_id < 1)  return $this->return_error(__LINE__,'無訂單ID');
        $order_info = M("TradeTheLine")->where(array('id'=>$order_id))->find();
        if(empty($order_info))  return $this->return_error(__LINE__,'無訂單信息');
        //前臺賣家若點擊撤銷訂單   或者賣家確認收款完成訂單     禁止後臺管理員操作該訂單
        if(in_array($order_info['status'],[3,4,5,6,7]) ){
            return $this->return_error(__LINE__,'訂單已經撤銷訂單或者已完成');
        }
        $this->order_info               = $order_info;
        $this->sealed_act               = $data['sealed_act']*1;
        $this->buy_credit_Level_act     = $data['buy_credit_Level_act']*1;
        $this->sell_credit_Level_act    = $data['sell_credit_Level_act']*1;
        $this->order_act                = $data['order_act']*1;
        $this->capital_flow_act         = $data['capital_flow_act']*1;

        $this->sell_id      = $order_info['sell_id']*1;
        $this->buy_id       = $order_info['buy_id']*1;
        $this->order_id     = $order_info['id']*1;
        $this->currency_id  = $order_info['currency_id']*1;
        $this->num          = $order_info['num']*1;
        $this->order_num    = $order_info['order_num'];
        $this->buy_fee      = $order_info['buy_fee'];
        $this->sell_fee     = $order_info['sell_fee'];
        
        //原始订单状态 
        $this->order_orginal_status = $order_info['status'];
        $rate  = $this->rate_usd[$order_info['om']];
        $money = bcmul($order_info['price'], $order_info['num']);
        $this->rate_total_money    = bcmul($money,$rate ,2);

        $flag=true;
        $model=new Model();
        $model->startTrans();
        try {
            $ret   = [];
            $ret[] = $this->_userSealedAct();    //封號處理
            $ret[] = $this->_buyCreditLevelAct();    //買家積分處理
            $ret[] = $this->_sellCreditLevelAct();   //賣家積分處理
            $ret[] = $this->_tradeTheLineAct();  //訂單狀態處理
            $ret[] = $this->_userCurrencyAct();  //金額處理
            $ret[] = $this->_addOrderAdminRunLog();  //記錄後臺操作日誌
            $ret[] = $this->_setLog();//修改訂單的狀態 日誌記錄
        }catch (\Exception $e){
            $flag=false;
        }
        if(in_array(false,$ret)) {
            $flag=false;
        }
        
        if($flag==true){
            $model->commit();
            return true;
        }else{
            $model->rollback();
            return false;
        }
    }

    /**
     *  根據封號條件，對買家或者賣家進行封號
     */
    protected function _userSealedAct(){
        if (!$this->_checkActStatus($this->sealed_act)) return true;
        //對買家封號
        if ($this->sealed_act == $this::ST_BUY_SEALED) return $this->_setUserSealed($this->buy_id);
        //對賣家封號
        if ($this->sealed_act == $this::ST_SELL_SEALED) return $this->_setUserSealed($this->sell_id);
    }

    /**用戶封號處理
     * @param $user_id
     */
    protected function _setUserSealed($user_id){
        if( $user_id< 1)  return $this->return_error(__LINE__, '封號處理:無用戶ID');
        $ret =  M('User')->where(array('uid'=>$user_id))->setField('status','-1');
        if(!$ret) return $this->return_error(__LINE__,'用戶ID:'.$user_id.'封號失敗');
        $this->sealed_id = $user_id;
        return true;
    }

    /** 對買家進行積分處理
     */
    protected function _buyCreditLevelAct(){
        if(!$this->_checkActStatus($this->buy_credit_Level_act)) return true;
        $integral =  $this->_setUserCreditLevel($this->buy_id,$this->buy_credit_Level_act,'买家');
        //设置日志积分
        $cal_type = 1;
        if(!$integral) return false;
        if($this->buy_credit_Level_act == $this::ST_CREDIT_LEVEL_DEC) $cal_type = -1;
        $this->buyer_credit_level = $cal_type*$integral;
        return true;

    }

    /** 對賣家進行積分處理
     */
    protected function _sellCreditLevelAct(){
        if(!$this->_checkActStatus($this->sell_credit_Level_act) ) return true;
        $integral =  $this->_setUserCreditLevel($this->sell_id,$this->sell_credit_Level_act,'卖家');
        if(!$integral) return false;
        //设置日志积分
        $cal_type = 1;
        if($this->sell_credit_Level_act == $this::ST_CREDIT_LEVEL_DEC) $cal_type = -1;
        $this->seller_credit_level = $cal_type*$integral;
        return true;
    }

    /** 用戶積分處理
     * @param $user_id
     * @param $credit_level_act
     * todo  現在版本，加分只加1分，扣分扣1.5分。以後如有更高級的VIP，去讀配置表來扣減分
     */
    protected function _setUserCreditLevel($user_id,$credit_level_act,$credit_str=''){
        if( $user_id< 1)  return $this->return_error(__LINE__, '用戶積分處理:無相關用戶');
        $score_obj = new \Back\Tools\Score();
        $extArr['status']  =  9; //交易场景状
        $integral = 0;
        //用戶加積分
        if($credit_level_act == $this::ST_CREDIT_LEVEL_INC){
            $integral = 1; //积分
            $extArr['operationType'] = 'inc'; //算符,inc表示加;dec表示减
            $extArr['scoreInfo'] = '後臺客服處理，'.$credit_str.'交易加積分';
            $extArr['status'] =  Point::BIND_TRADE_ORDER_STATUS;
            $extArr['remarkInfo'] =  $this->order_num;
        }
        //用戶減積分
        if($credit_level_act == $this::ST_CREDIT_LEVEL_DEC ){
            $integral= 1.5; //积分
            $extArr['operationType'] = 'dec'; //算符,inc表示加;dec表示减
            $extArr['scoreInfo'] = '後臺客服處理，'.$credit_str.'交易減積分';
            $extArr['isOverTime'] = 1; //失信计算标记；0表示不计算;1表示计算
            $extArr['status'] =  Point::BIND_TRADE_ORDER_STATUS;
            $extArr['remarkInfo'] =  $this->order_num;
        }
        $ret = $score_obj->calUserIntegralAndLeavl($user_id, $integral,$extArr);
        if(!$ret) {
            return $this->return_error(__LINE__,'用戶積分處理失敗');
        }
        return $integral;
    }

    /**
     *  訂單處理
     */
    protected function _tradeTheLineAct(){
        if(!$this->_checkActStatus($this->order_act) ) return true;
        if( $this->order_id <1 or !array_key_exists($this->order_act,$this->st_order_act_status_arr)){
            return $this->return_error(__LINE__, '訂單處理:參數有誤');
        }
        $order_status = $this->st_order_act_status_arr[$this->order_act];
        if( $this->order_info['status'] == $order_status) return true;
        $order_data['status'] = $order_status;
        if(in_array($order_status,array(4,6))){
            $order_data['end_time'] = time();
        }
        
        // 2019年5月23日 撤销订单 计算余额
        if($order_status == 6 && $this->order_orginal_status == 0){
            $order_data['rate_total_money'] = $this->rate_total_money;
        }
        
        $ret = M('TradeTheLine')->where(array('id'=>$this->order_id))->save($order_data);
        if(!$ret) return $this->return_error(__LINE__,'訂單處理失敗');
        return true;
    }

    /**
     *  金額處理
     */
    protected function _userCurrencyAct(){
        $ret = array();
        if(!$this->_checkActStatus($this->capital_flow_act)  ) return true;
        if( $this->num <= 0) return $this->return_error(__LINE__, '金額處理:無訂單金額');
        if( $this->currency_id < 1) return $this->return_error(__LINE__, '金額處理:無貨蔽類型');
        //撤銷訂單,對應訂單返錢
        if($this->capital_flow_act == $this::ST_SELL_CURRENCY_INC){
            if( $this->sell_id< 1) return $this->return_error(__LINE__, '金額處理:無賣家用戶');
            //避免重复撤销操作
            $isTrue =  $this->redis_cluster_obj->get('OFF_LINE_IS_REVOKE_ORDER'.$this->order_id);
            if (!empty($isTrue)) return $this->return_error(__LINE__,'当前订单不支持此操作');
            $this->redis_cluster_obj->setex('OFF_LINE_IS_REVOKE_ORDER'.$this->order_id, 10, true);

            //退還訂單金額費給賣家
            $this->_setUserCurrency($this->sell_id,$this->num,
                $this->currency_id,15,'線下交易（管理員）撤銷返款',$this::ST_FINANCE_INCOME);
            //退還手續費給賣家
            if($this->sell_fee >0){
                $this->_setUserCurrency($this->sell_id,$this->sell_fee,
                    $this->currency_id,14,'線下交易（管理員）手續費返還',$this::ST_FINANCE_INCOME);
            }
        }
        //增加買家,并扣手续费
        if($this->capital_flow_act == $this::ST_BUY_CURRENCY_INC){
            if( $this->buy_id< 1)  return $this->return_error(__LINE__, '金額處理:無買家用戶');
            $this->_setUserCurrency($this->buy_id,$this->num,
                $this->currency_id,6,'線下交易購買人獲取(管理員)',$this::ST_FINANCE_INCOME);
            //扣买家手续费
            if($this->buy_id > 0 and $this->buy_fee >0 ){
                $this->_setUserCurrency($this->buy_id,$this->buy_fee,
                    $this->currency_id,8,'線下交易買入扣除手續費(管理員)',
                    $this::ST_FINANCE_EXPENSE);
            }
        }
        if(in_array(false,$ret)) {
            return $this->return_error(__LINE__,'金額處理處理失敗');
        }
         $this->redis_cluster_obj->rpop('OFF_LINE_SELL_ORDER'.$this->order_id);
        return true;
    }

    /**
     * 用戶收入或者支出金額處理
     * @param $user_id
     * @param $pawn_num     金額數量
     * @param $currency_id  貨幣類型
     * @param $finance_type
     * @param $desc
     * @param $expense_or_income_type 1為收入，2為支出
     * @return bool
     */
    protected function _setUserCurrency($user_id,$currency_num,$currency_id,
                                        $finance_type,$desc,$expense_or_income_type ){
        if( $user_id< 1 or $currency_num<=0 or $currency_id<1
            or $finance_type<1 or empty($desc) or $expense_or_income_type<1){
            return $this->return_error(__LINE__, '用戶金額收支操作:參數有誤');
        }
        //设置金额是增加还是减少
        $operationType = 'inc';
        if($expense_or_income_type == self::ST_FINANCE_EXPENSE)  $operationType = 'dec';

        $ret    = array();
        $ret[]  = $this->_order_user_money_obj->setUserMoney($user_id, $currency_id,
                                                                $currency_num,'num',$operationType);
        //日誌的記錄體現余額
        $before_currency=M('UserCurrency')->where(['uid'=>$user_id,'currency_id'=>$currency_id])->find();
        $after_money = !empty($before_currency['num']) ? $before_currency['num'] : 0.0000;
        $ret[]  = $this->_order_user_money_obj->AddFinanceLog($user_id,$currency_id,$finance_type,
            $desc,$expense_or_income_type,
            $currency_num,$after_money,$this->order_num);
        if(in_array(false,$ret)) {
            return $this->return_error(__LINE__,'用戶ID：'.$user_id.':'.$desc.'處理失敗');
        }
        return true;
    }

    /**
     * 後臺操作日誌處理
     * @return bool
     */
    protected function _addOrderAdminRunLog(){
        if(!$this->_checkActStatus($this->order_act) ){
            $order_status = $this->order_info['status'];
        }else{
            $order_status = $this->st_order_act_status_arr[$this->order_act];
        };
        $data['order_id']   = $this->order_id;
        $data['sealed_id']    = $this->sealed_id;
        $data['buyer_credit_level']   = $this->buyer_credit_level;
        $data['seller_credit_level']  = $this->seller_credit_level;
        $data['order_status']    =   $order_status;
        $data['capital_flow']       = $this->capital_flow_act;
        $data['add_time']       = time();
        $ret = M('OrderAdminRunLog')->add($data);
        if(!$ret) {
            return $this->return_error(__LINE__,$this->order_id.':後臺訂單日誌記錄失敗');
        }
        return true;
    }

    /**
     * 修改訂單的狀態 日誌記錄
     * @param $sell_id
     * @param $buy_id
     * @param $order_num
     * @param $jine
     * @param $dingdan
     * @return bool
     */
    protected  function _setLog()
    {
        $sell_name  =   $this::getUserNameByUid($this->sell_id);
        $buy_name   =   $this::getUserNameByUid($this->buy_id);
        if(empty($this->order_num))   return $this->return_error(__LINE__,'修改訂單的狀態日誌記錄:無訂單號');
        if(!array_key_exists($this->order_act,$this->log_order)){
            return $this->return_error(__LINE__,'修改訂單的狀態日誌記錄:無相關訂單狀態');
        }
        $order_status_name    =   $this->log_order[$this->order_act];
        $capital_flow_name       =   $this->log_capital_flow[$this->capital_flow_act];
        $obj_redis  =   RedisIndex::getInstance();
        $userinfo   =   $obj_redis->getSessionValue('user');
        $username   =   $userinfo['username'];
        if(empty($username)) return $this->return_error(__LINE__,'修改訂單的狀態日誌記錄:無管理員用戶');
        $log="線下交易訂單-狀態修改為：{$order_status_name},金額處理{$capital_flow_name}";
        $res=OperateLog::insert_off_trade_moneyORorder_log($sell_name,$buy_name,$username,$this->order_num,$log,1);
        if(!$res) {
            return $this->return_error(__LINE__,'修改訂單的狀態 日誌記錄 失敗');
        }
        return true;
    }


    /**檢測操作狀態，如果不需要操作，返回false
     * @param $act_status
     * @return bool
     */
    protected  function _checkActStatus($act_status){
        if ( $act_status == $this::ST_ACTIVITY_NONE
            or  $act_status == 0
            or  empty($act_status)
        ){
            return false;
        }
        return true;
    }

    //獲取用戶名
    static  function   getUserNameByUid($uid) {
        $uid=intval($uid);
        $username='無人購買';
        if($uid>0){
            $res= M('User')->where(['uid'=>$uid])->find();
            if($res){
                $username=$res['username'];
            }
        }
        return  $username;
    }

}