<?php
/**
 * Created by PhpStorm.
 * User: 李江
 * Date: 2017/8/14
 * Time: 16:23
 * 提充币控制器
 */
namespace Back\Controller;
use Back\Common\OrderUserMoney;
use Back\Tools\Page;
use Common\Api\RedisIndex;
use Think\Model;

class PackController extends BackBaseController
{
    /**
     * @var array 組id 超級管理員權限 台灣主管
     */
    private $AdvancedArr = [1,6];
    private $currencyList = [];  //币种列表
    public $tiBiStatus=[
        ''=>'請選擇',
        '-0'=>'等待審核',
        '1'=>'提幣成功 ',
        '2'=>'等待提出',
        '-1'=>'提幣失敗',
    ];
    public $chongbiBiStatus=[
        ''  =>'请选择',
        '1'=>'为充值中',
        '2' =>'充值成功 ',
        '3' =>'充值失败',
    ];
    protected $guijiArr = [
        ''  => '请选择',
        '1' => '未归集',
        '2' => '已归集',
    ];
    /**
     * @var 當前用戶組id
     */
    private  $_group_id;

    public function __construct(){
        parent::__construct();
        $this->_group_id   = $this->getGidByUid();
        $this->currencyList = D('Currency')->getCurrencyList(); //币种
    }
    /**
     * @method 獲取當前用戶組id
     * @param string $username
     * @return number
     */
    private function getGidByUid(){
        $id  = $this->back_userinfo['id'];
        $res = M('AuthGroupAccess')->where(['uid'=>$id])->find();
        return !empty($res) ? $res['group_id']: 0 ;
    }
    /**
     * @method 获取币种信息
     * @author lirunqing 2019-03-01T15:26:44+0800
     * @return array
     */
    private function getCurrencyInfo(){
        $currencyList = M('Currency')->select();
        $list = array_column($currencyList, 'currency_name', 'id');
        return $list;
    }

    /**
     * @author 李江
     * @method 提币列表页面 2017年8月14日16:26:34
     */
    public function showTiBiLog(){
        $uid = $_SESSION['str_user']['id'];
        $group_id = M('AuthGroupAccess')->where(['uid'=>$uid])->getField('group_id');
        if(in_array($group_id,$this->AdvancedArr)){
            $this->assign('AdvancedOperation',1);
        }
        $where     = $this->getParams('tibi');

        //查詢該用戶是否是代理商
        $redis=RedisIndex::getInstance();
        $redis_data = $redis->getSessionValue("user");
        $agent =D("auth_group_access")->where("uid = {$redis_data['id']} and group_id = 11")->find();
        if ($agent){
            $admin_data = D("admin_user")->where("id = {$redis_data['id']} ")->find();
            if (empty($where)){
                $array["invite_code"] = $admin_data["invite_code"];
                $where = $array ;
            }else{
                $where["invite_code"] = $admin_data["invite_code"];
            }
        }

        $tibi_info = $this->getTiBiList($where);  //提币信息
        $curList   = $this->getCurrencyInfo();  //币种信息

        foreach ($tibi_info['list'] as $k=>$v){
            if (!empty($v['ti_id'])) {
                $v['ti_id_str']        = substr($v['ti_id'],0,5);
                $currencyName          = strtolower($curList[$v['currency_id']]);
                $urlKey                = 'coinurl.'.$currencyName;
                $v['coin_url']         = C($urlKey).$v['ti_id'];
                $tibi_info['list'][$k] = $v;
            }
            $tibi_info['list'][$k]['advanced_operate'] = 0;
            if( $v['status'] == 2 ){
                $tibi_info['list'][$k]['advanced_operate'] = 1;
            }
        }

        $bi_data = M("tibi")
            ->alias('ti')
            ->join('inner join trade_currency on ti.currency_id=trade_currency.id')
            ->join('trade_user as u on ti.uid = u.uid')
            ->join(' left join trade_admin_user as d on d.invite_code = u.invite_code')
            ->field("sum(ti.num) num,trade_currency.currency_name")
            ->where($where)
            ->group("currency_id")
            ->select();
        $this->assign('bi_data',$bi_data);//賦值數據統計
        $this->assign('showAddr',$this->showAddr());  //是否显示提币地址
        $this->assign('tibi_list',$tibi_info['list']);// 赋值数据集
        $this->assign('page',$tibi_info['page']);// 赋值分页输出
        $this->assign('currency_list',$tibi_info['currency_list']);
        $this->assign('agent',count($agent));// 传入前端判断是否是代理商
        $this->assign('statusArr',$this->tiBiStatus);
        $this->display();
    }

    /**
     * @method 是否显示提币地址
     * @return bool  1=>显示 0 不显示
     */
    protected function showAddr(){
        $where   = ['name'=>'Back/Pack/upTiId'];
        $rule_id = M('AuthRule')->field('id')->where($where)->find();
        if(empty($rule_id)) return 0;

        $where = ['au.uid' => $this->back_userinfo['id']];
        $res   = M('AuthGroupAccess')->alias('au')
            ->where($where)->field('g.rules')
            ->join(' left join __AUTH_GROUP__ as g on g.id=au.group_id')
            ->find();

        if(empty($res)) return 0;
        return in_array($rule_id['id'],explode(',', $res['rules'])) ? 1 :0 ;
    }


    protected function getTiBiList( $where,$type='' ){
        $M_Ti_Bi = M('Tibi');
        if( isset($type) && $type!=''){
            $where['ti.type'] = $type;
        }
        $count   = $M_Ti_Bi->alias('ti')->join("trade_user as u on ti.uid = u.uid")->join(' left join trade_admin_user as d on d.invite_code = u.invite_code')->where($where)->count();// 查询满足要求的总记录数
        $Page    = new Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show    = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list    = $M_Ti_Bi
            ->alias('ti')
            ->where($where)
            ->join(' left join __USER__ as u on u.uid=ti.uid')
            ->join(' left join __CURRENCY__ as c on c.id=ti.currency_id')
            ->join(' left join trade_admin_user as d on d.invite_code = u.invite_code')
            ->field('u.phone,u.username,ti.*,c.currency_name')
            ->order("ti.add_time desc ")
            ->limit($Page->firstRow.','.$Page->listRows)->select();
        $CurrencyModel = D('Currency');
        return ['list'=>$list,'page'=>$show,'currency_list'=>$CurrencyModel->getCurrencyList()];
    }


    /**
     * @method 获取实名认证姓名
     * @param  array 原始数据列表  $list
     * @return array
     */
    private static function getUserRealNameByUid($list){
        if(empty($list)) return $list;

        $uids  = array_values(array_unique(array_column($list, 'uid')));
        $names =  M('UserReal')->where(['uid'=>['IN',$uids]])
            ->field('uid,card_name')->select();
        $names = !empty($names)? array_column($names,'card_name','uid'):[];

        foreach ($list as $key=>$value) {
            $list[$key]['card_name'] = $names[$value['uid']]?:'unknow';
        }
        return $list;
    }

    public function showChongBiLog(){
        $where = $this->getParams('chongbi');
        //给分页传参数
        $M_Chong_Bi = M('Chongbi');
        $count      = $M_Chong_Bi
            ->join(' left join trade_currency as c on trade_chongbi.currency_id=c.id')
            ->join(' left join trade_user as u on u.uid = trade_chongbi.uid')
            ->join(' left join trade_admin_user as d on d.invite_code = u.invite_code')
            ->where($where)
            ->count();// 查询满足要求的总记录数
        $Page       = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        //查詢該用戶是否是代理商

        $redis=RedisIndex::getInstance();
        $redis_data = $redis->getSessionValue("user");
        $agent =D("auth_group_access")->where("uid = {$redis_data['id']} and group_id = 11")->find();
        if ($agent){
            $admin_data = D("admin_user")->where("id = {$redis_data['id']} ")->find();
            if (empty($where)){
                $array["u.invite_code"] = $admin_data["invite_code"];
                $where = $array ;
            }else{
                $where["u.invite_code"] = $admin_data["invite_code"];
            }
        }
        $show       = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = $M_Chong_Bi
            ->join(' left join trade_currency as c on trade_chongbi.currency_id=c.id')
            ->join(' left join trade_user as u on u.uid = trade_chongbi.uid')
            ->join(' left join trade_admin_user as d on d.invite_code = u.invite_code')
            ->where($where)
            ->field('trade_chongbi.*,c.currency_name,u.username,u.invite_code')
            ->order(" trade_chongbi.add_time desc ")
            ->limit($Page->firstRow.','.$Page->listRows)->select();

        $CurrencyModel = D('Currency');
        $cur_list = $CurrencyModel->getCurrencyList();

        $total = M('Chongbi')->join('left join trade_user as u on trade_chongbi.uid=u.uid')
            ->join(' left join trade_admin_user as d on d.invite_code = u.invite_code')
            ->where($where)
            ->field('currency_id,sum(trade_chongbi.num) as total')
            ->group('currency_id')
            ->select();

        $total_num[1] = 0.0;
        $total_num[4] = 0.0;
        $total_num[8] = 0.0;
        foreach ($total as $k){
            if($k['currency_id'] == 1 or $k['currency_id'] == 4 or $k['currency_id'] == 8){
                $total_num[$k['currency_id']] = $k['total'];
            }
        }
        //充幣統計
        $bi_data = M("chongbi")
            ->join('inner join trade_currency on trade_chongbi.currency_id=trade_currency.id')
            ->join('inner join trade_user as u on trade_chongbi.uid = u.uid')
            ->join(' left join trade_admin_user as d on d.invite_code = u.invite_code')
            ->field("sum(trade_chongbi.num) num,trade_currency.currency_name")
            ->where($where)
            ->group("currency_id")
            ->select();

        $this->assign('bi_data',$bi_data);//賦值數據統計
        $this->assign('chongbi_list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('agent',count($agent));// 传入前端判断是否是代理商
        $this->assign('currency_list',$cur_list);
//        $this->assign('total_num',$total_num);
        $this->display();
    }

    /**
     *@method  審核提幣請求
     */
    public function editTiBiStatus(){
        $change   = trim(I('change'));
        $id       = intval(trim(I('id')));
        $tiBiList = M('Tibi')->where(['id'=>$id])->find();

        if(empty($tiBiList)){
            $this->ajaxReturn(['status'=>404,'msg'=>'記錄不存在' ]);
        }
        $r      = [];
        $insert = [
            'ti_id' => $tiBiList['id'],
            'uid'   => $tiBiList['uid'],
        ];

        //$change == 'YES'  把提币状态改为 2   及 等待转出
        if( $change == 'YES' ){
            $data =  ['status'=>2,'update_time'=>time()];
//        	$insert['log'] = '提幣申請修改為等待转出';

            M()->startTrans();
            $r  =  M('Tibi')->where(['id'=>$id])->save($data);
            if(!$r){
                M()->rollback();
                $this->ajaxReturn(['status'=>404,'msg'=>'服務器繁忙,請稍後再試']);
            }
            M()->commit();
            $this->ajaxReturn(['status'=>200,'msg'=>'操作成功']);

        }
        if($change == 'NO'){
            $data   = ['status'=>-1,'update_time'=>time()];
//            $insert['log'] = '提幣申請修改為提幣失敗';

            M()->startTrans();
            //hlong 2017-3-29 添加的关于 提币记录中的"取消请求" =>提幣失敗  轉化功能
            $currency_id= $tiBiList['currency_id'];
            $num        = $tiBiList['num'];
            $uid        = $tiBiList['uid'];
            $UserMoneyApi = new OrderUserMoney();
            $r[] = $UserMoneyApi->setUserMoney($uid, $currency_id, $num,'num','inc');/////加回用户余额和这一次的手续费

            $after_money = M('UserCurrency')->where(['uid'=>$uid,'currency_id'=>$currency_id])->getField('num');//加回的余额  从数据库中调用
            $r[] = $UserMoneyApi->AddFinanceLog($uid, $currency_id, 1, '提幣失敗返還', 1, $num,$after_money);

            $r[] = M('Tibi')->where(['id'=>$id])->save($data);
            if(in_array(false,$r) ){
                M()->rollback();
                $this->ajaxReturn(['status'=>404,'msg'=>'服務器繁忙,請稍後再試']);
            }

            M()->commit();
            $this->ajaxReturn(['status'=>200,'msg'=>'操作成功']);
        }
    }


    protected function getParams( $flag ){
        $where = [];
        $currency_id = I('currency_id');
        $url = trim(I('url'));
        $name= trim(I('username'));
        $uid = I('uid');
        $status=trim(I('status'));  //审核状态
        $invite_code=trim(I('invite_code'));
        $agent_name=trim(I('agent_name'));

        if( $uid ){
            if($flag == 'tibi' ){
                $where['ti.uid'] = $uid;
            }else{
                $where['trade_chongbi.uid'] = $uid;
            }
        }elseif( $name && empty($uid) ){
            $uid = D('User')->getUidByUname($name);
            if( $flag == 'tibi' ){
                $where['ti.uid'] = $uid;
            }else{
                $where['trade_chongbi.uid'] = $uid;
            }
        }
        if( $url ) $where['url'] = ['like',"%".$url."%"];
        if( $currency_id != -1 && isset($currency_id) && !empty($currency_id) ){
            if( $flag == 'tibi' ){
                $where['ti.currency_id'] = $currency_id;
            }else{
                $where['trade_chongbi.currency_id'] = $currency_id;
            }
        }
        //充币时间筛选
        if( $flag  != 'tibi'){
            $star_time = strtotime(trim(I('start_time')));
            $end_time = strtotime(trim(I('end_time')));
            if (!empty($star_time)&&!empty($end_time)){
                $where["add_time"] = ["between",[$star_time,$end_time]];
            }
        }
        //充币添加状态搜索
        if (!empty($status)) $where['ti.status'] = $status;
        if(!empty($invite_code)) $where['u.invite_code']=$invite_code;
        if(!empty($agent_name)) $where['d.username']=$agent_name;
        return $where;
    }

    //修改交易哈希  lirunqing 2019年3月1日16:22:17
    public function upTiId(){
        if( IS_AJAX ){
            $id   = I('id');
            $tiId = trim(I('ti_id'));
            if (empty($tiId)) $this->ajaxReturn(['status'=>404,'msg'=>'请填写交易哈希']);
            if (empty($id)) $this->ajaxReturn(['status'=>404,'msg'=>'修改失败']);

            $res = M('Tibi')->find($id);
            if(empty($res)) $this->ajaxReturn(['status'=>406,'msg'=>'記錄不存在']);
            if($res['ti_id'] == $tiId) $this->ajaxReturn(['status'=>406,'msg'=>'请输入新的交易哈希值']);

            $insert = [
                'ti_id' => $res['id'],
                'uid'   => $res['uid'],
                'log'   =>'提币记录修改交易哈希值为  '.$tiId,
            ];
            $r    = [];

            M()->startTrans();
            $data = ['update_time'=>time(), 'ti_id' => $tiId];
            $r[]  = M('Tibi')->where(['id'=>$id])->save($data);
//            $r[]  = $this->insertLog($insert);

            if(in_array(false, $r)) {
                M()->rollback();
                $this->ajaxReturn(['status'=>404,'msg'=>'修改失败']);
            }
            M()->commit();
            $this->ajaxReturn(['status'=>200,'msg'=>'修改成功']);
        }
    }
    /**
     * @method 提幣通過   需要高級管理員填寫交易哈希
     * @return string json
     */
    public function successTibi(){
        if( IS_AJAX ){
            $id   = I('id');
            $tiId = I('ti_id');

            $r = [];
            if (empty($tiId)) $this->ajaxReturn(['status'=>404,'msg'=>'请填写交易哈希']);
            if (empty($id))   $this->ajaxReturn(['status'=>405,'msg'=>'操作失败']);

            $res = M('Tibi')->find($id);
            if(empty($res))  $this->ajaxReturn(['status'=>406,'msg'=>'記錄不存在']);

            $data=[
                'status'=>1,'update_time'=>time(),
                'ti_id' => $tiId
            ];

            $insert = [
                'ti_id' => $res['id'],
                'uid'   => $res['uid'],
                'log' =>'提幣申請修改為提幣成功',
            ];

            M()->startTrans();
            $r[] = M('Tibi')->where(['id'=>$id])->save($data);
//            $r[] = $this->insertLog($insert);

            if(in_array(false, $r)){
                M()->rollback();
                $this->ajaxReturn(['status'=>404,'msg'=>'操作失败']);
            }
            M()->commit();
            $this->ajaxReturn(['status'=>200,'msg'=>'提币成功']);
        }
    }
    /**
     * @method 插入table tibi_status_log
     * @param  array 插入数据
     * @return bool
     */
//    private function insertLog($data){
//        $data['add_time']   = time();
//        $data['admin_name'] = $this->back_userinfo['username'];
//        $data['gid']        = $this->_group_id;
//        return  M('TibiStatusLog')->add($data);
//    }
    //统计提币
    public function countTibi(){
        $data = I('get.');
        $where = $this->getTiParams($data);
        $currency_list = $this->currencyList; //币种
        $statusArr = $this->tiBiStatus;//状态
        $times = $this->getQueryTimeStr();
        $list  = $this->getCountTiList($where);
        foreach ($list as &$val){
            $val['currency_name'] = $currency_list[$val['currency_id']]?$currency_list[$val['currency_id']]:'';
        }
        $this->assign('currency_list',$currency_list);
        $this->assign('statusArr',$statusArr);
        $this->assign('times',$times);
        $this->assign('list',$list);
        $this->display();
    }
    //统计充币
    public function countChongBi(){
        $data = I('get.');
        $where = $this->getChongBiParams($data);
        $currency_list = $this->currencyList; //币种
        $statusArr = $this->chongbiBiStatus;//状态
        $times = $this->getQueryTimeStr();
        $list  = $this->getCountCbList($where);
        foreach ($list as &$val){
            $val['currency_name'] = $currency_list[$val['currency_id']]?$currency_list[$val['currency_id']]:'';
        }
        $this->assign('currency_list',$currency_list);
        $this->assign('statusArr',$statusArr);
        $this->assign('guijiArr',$this->guijiArr);
        $this->assign('times',$times);
        $this->assign('list',$list);
        $this->display();

    }
    //统计条件
    protected function getTiParams($data){
        $where = ' 1 = 1';
        if($data['currency_id']){
            $currency_id = $data['currency_id'];
            $where .= " and currency_id = $currency_id";
        }
        if($data['status']){
            $status = $data['status'];
            $where .= " and status = $status";
        }
        if($data['time']){
            $time = $data['time'];
            $where .= " and update_time >= $this->getQueryTime($time)";
        }else{
            $where .= " and update_time >= $this->getQueryTime(1)";
        }

        return $where;
    }
    //
    protected function getChongBiParams($data){
        $where = ' 1 = 1';
        if($data['currency_id']){
            $currency_id = $data['currency_id'];
            $where .= " and currency_id = $currency_id";
        }
        if($data['status']){
            $status = $data['status'];
            $where .= " and status = $status";
        }
        if($data['guiji']){
            $guiji = $data['guiji'];
            $where .= " and guiji = $guiji";
        }
        if($data['time']){
            $time = $data['time'];
            $where .= " and add_time >= $this->getQueryTime($time)";
        }else{
            $where .= " and add_time >= $this->getQueryTime(1)";
        }

        return $where;
    }
    //统计方法
    protected function getCountTiList($where){
        $sql = "select currency_id,sum(num) as `num`,sum(collier_fee) as `fee` ,sum(actual) as `actual`  from trade_tibi where".$where."  GROUP BY currency_id;";
        $list = M()->query($sql);
        return $list;
    }

    protected function getCountCbList($where){
        $sql = "select currency_id,sum(num) as `num`  from 
chongbi where".$where."  GROUP BY currency_id;";
        $list = M()->query($sql);
        return $list;
    }

    protected function getQueryTime($str){
        switch ($str){
            case 1:
                $time=strtotime('-1 day');//过去24小时
                break;
            case 2:
                $time=strtotime('-7 day');//过去一周
                break;
            case 3:
                $time=strtotime('-14 day');//过去一周
                break;
            case 4:
                $time=strtotime('-1 month');//过去一周
                break;
            case 5:
                $time=strtotime('-6 month');//过去一周
                break;
            case 6:
                $time=strtotime('-1 year');//过去一周
                break;
            case 7:
                $time=0;
                break;
            default:
                $time=strtotime('-7 day');//过去一周
        }
        return $time;
    }

    private function getQueryTimeStr(){
        $str = [
            ['id'=>'1','name'=>'24小时内'],
            ['id'=>'2','name'=>'過去一周'],
            ['id'=>'3','name'=>'過去两周'],
            ['id'=>'4','name'=>'過去一个月'],
            ['id'=>'5','name'=>'過去半年'],
            ['id'=>'6','name'=>'過去一年'],
            ['id'=>'7','name'=>'所有數據'],
        ];
        return $str;
    }

}
