<?php
namespace Back\Controller;

/**
 * @author 建强  2019年6月10日
 * @desc   获取用户相关的数据
 */
class UserInfoController extends BackBaseController
{
    /**
     * @var array where
     * @var int   mod 表取模
     */
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
     * @author 建强  2019年6月10日12:04:15
     * @return void
     */
    public function UserInfoAbout(){
        $admin=0;

         if($this->back_userinfo['id']==self::$adminUid)
         {
         	   $admin=1;
         }
        $data = [
             //用户信息
            'user_info'   => $this->getUserRealInfo(),
            //登录日志
            'user_login'  => $this->getUserLoginLogs(),
            //财务日志
            'user_finance'=> $this->getUserFinanceList(),
            //提币日志
            'user_tibi'   => $this->getUserTibiList(),
            //充币日志
            'user_charge' => $this->getUserChargeList(),
            //短信日志
            'user_sms'    => $this->getUserSmsLog(),
            //银行卡
            'user_banks'  => $this->getUserBankCardList(),
        ];
        $this->assign('data',$data);
        $this->assign('user',['uid'=>$this->_uid,'name'=>$this->_username]);
        $this->display('userInfo');
    }
    /**
     * @author 建强   2019年6月10日
     * @method 获取用户信息
     * @return array field (证件类型,证件姓名,银行卡开户行姓名,证件号码,证件图像,申请时间,护照过期时间)
     */
    protected function getUserRealInfo(){
        $field = 'ur.uid,ur.card_type,ur.status,ur.card_name,ur.card_num,ur.bank_name,ur.expire_time,
            ur.all_img,ur.up_img,ur.add_time,ur.expire_time,u.phone,u.om,u.username';

        $where = ['ur.uid' => $this->_where['uid']];
        $user  = M('UserReal')->alias('ur')->join('LEFT JOIN __USER__ AS u ON u.uid=ur.uid')
             ->where($where)->field($field)->find();

        if(empty($user)) return $user;

        $status = [
            '0'=>  '審核中'  , '1'=>'審核通過 '   , '-1'=> '審核未通過'
        ];
        $user['pass_type']   = '護照';
        $user['user_phone']  = $user['om'].'_'.$user['phone'];
        $user['add_time']    = date('Y-m-d H:i:s', $user['add_time']);
        $user['expire_time'] = date('Y-m-d H:i:s', $user['expire_time']);
        $user['status']      = $status[$user['status']];

        return $user;
    }
    /**
     * @author 建强   2019年6月10日
     * @method 获取用户登录日志最近
     * @return array
     */
    protected function getUserLoginLogs(){
        $field = 'ip,add_time,type,url';
        $res   = M('UserLog'.$this->_mod)->where($this->_where)->order('add_time desc')
            ->field($field)->limit(5)->select();
        if(empty($res)) return [];

        foreach ($res as $key=>$value) {
            $res[$key]['type_str']   = formatLogType1($value['type']);
            $res[$key]['area']       = getIpArea($value['ip']);
            $res[$key]['add_time']   = date('Y-m-d H:i:s', $value['add_time']);
            $res[$key]['uid']        = $this->_where['uid'];
            $res[$key]['username']   = $this->_username;

        }
        return $res;
    }
    /**
     * @author 建强   2019年6月10日
     * @method 获取用户财务日志订单报表
     * @return array
     */
    protected function getUserFinanceList(){
        $res   =  M('UserFinance'.$this->_mod)->where($this->_where)
            ->order('add_time desc')->limit(5)->select();
        if(empty($res)) return [];

        foreach ($res as $key=>$value) {
            $res[$key]['finance_type'] = formatFinanceType($value['finance_type']);
            $res[$key]['color']        = $value['type'] ==1 ?'green':'red';
            $flag                      = $value['type'] ==1 ?'+':'-';
            $res[$key]['money']        = $flag.$value['money'];
            $res[$key]['type_str']     = $value['type']== 1 ?'收入':'支出';
            $res[$key]['add_time']     = date('Y-m-d H:i:s', $value['add_time']);
            $res[$key]['curr_name']    = $this->_currencyName[$value['currency_id']];
            $res[$key]['uid']          = $this->_where['uid'];
            $res[$key]['username']     = $this->_username;
            $res[$key]['remark_info']  = '-';
            if(strlen($value['remark_info'])>3) $res[$key]['remark_info'] = $value['remark_info'];
        }
        return $res;
    }

    /**
     * @author 建强   2019年6月10日
     * @method 获取用户提币日志
     * @return array
     */
    protected function getUserTibiList(){
        $res =  M('Tibi')->where($this->_where)->order('add_time desc')->limit(5)->select();
        if(empty($res)) return [];

        $status = [
            '0' =>'等待審核中','1' =>'提幣成功',
            '-1'=>'提幣失敗', '2' =>'等待提出',
        ];
        foreach ($res as $key=>$value) {
            $res[$key]['curreny_name'] = $this->_currencyName[$value['currency_id']];
            $res[$key]['add_time']     = date('Y-m-d H:i:s', $value['add_time']);
            $res[$key]['check_time']   = $value['check_time']==0 ?'-': date('Y-m-d H:i:s', $value['check_time']);
            $res[$key]['update_time']  = $value['update_time']==0 ?'-': date('Y-m-d H:i:s', $value['update_time']);
            $res[$key]['uid']          = $this->_where['uid'];
            $res[$key]['username']     = $this->_username;

            $res[$key]['ti_id']        = '-';
            $res[$key]['coin_url']     = '';
            if(!empty($value['ti_id'])){
                $res[$key]['ti_id']    = substr($value['ti_id'],0,5);
                $urlKey                = 'coinurl.'.strtolower($res[$key]['curreny_name']);
                $res[$key]['coin_url'] = C($urlKey).$value['ti_id'];
            }

            if($value['status'] =='1')  $res[$key]['color'] ='green';
            if($value['status'] =='-1') $res[$key]['color'] ='red';
            $res[$key]['status'] =$status[$value['status']];
        }

        return $res;
    }
    /**
     * @author 建强   2019年6月10日
     * @method 获取用户财务日志订单报表
     * @return array
     */
    protected function getUserChargeList(){
        $res =  M('Chongbi')->where($this->_where)->order('add_time desc')->limit(5)->select();
        if(empty($res)) return [];

        $status = ['1'=>'充值中','2'=>'充值成功','3'=>'充充值失敗'  ];
        foreach ($res as $key=>$value) {
            $res[$key]['curreny_name'] = $this->_currencyName[$value['currency_id']];
            $res[$key]['add_time']     = date('Y-m-d H:i:s', $value['add_time']);
            $res[$key]['check_time']   = ($value['check_time']>0) ? date('Y-m-d H:i:s', $value['check_time']):'-';
            $res[$key]['uid']          = $this->_where['uid'];
            $res[$key]['username']     = $this->_username;
            $res[$key]['color'] ='red';
            if($value['status'] =='2')  $res[$key]['color'] ='green';
            $res[$key]['status']       = $status[$value['status']];

        }
        return $res;
    }

    /**
     * @author 建强  2019年6月10日14:15:33
     * @method 查询发送短信记录
     * @return array
     */
    protected function getUserSmsLog() {
        $res =  M('SmsLog')->where($this->_where)->order('add_time desc')->limit(5)->select();
        if(empty($res)) return [];

        foreach ($res as $key=>$value) {
            $res[$key]['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
            $res[$key]['type_str'] = FormatSmsType($value['type']);
            $res[$key]['uid']      = $this->_where['uid'];
            $res[$key]['username'] = $this->_username;
        }
        return $res;
    }

    /**
     * @author 建强  2019年6月10日14:15:33
     * @method 查询用户银行卡信息
     * @return array
    */
    protected function getUserBankCardList() {
        $res = M('UserBank')->where($this->_where)->select();

        if(empty($res)) return $res;
        $bank_names = self::getBankName($res);
        foreach ($res  as $key=>$val){
            $res[$key]['bank_name']= $bank_names[$val['bank_list_id']];
            $res[$key]['date']     = date('Y-m-d H:i:s', $val['add_time']);
            $res[$key]['uid']      = $this->_where['uid'];
            $res[$key]['username'] = $this->_username;
        }
        return $res;
    }
    /**
     * @method 获取银行卡名称
     * @param  array $banks
     * @return array
     */
    protected static function getBankName($banks) {
        $ids   = array_unique(array_column($banks, 'bank_list_id'));
        $where = ['id'=> ['IN',$ids]];
        $res   =  M('BankList')->field('id,bank_name')->where($where)->select();
        if(!empty($res)) $res = array_column($res,'bank_name','id');
        return $res;
    }
}
