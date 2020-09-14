<?php
namespace Back\Controller;
use Back\Tools\Page;

class TibiLogController extends BackBaseController
{
    private $_group_id,$_admin_name; 
    
    public function __construct(){
        parent::__construct();   
        $this->_group_id   = $this->getGidByUid();
        $this->_admin_name = $this->back_userinfo['username'];
    }
    /**
     * @method 获取列表首页 
     * @return void
     */
    public function index(){
        $where  = $this->getParams();
        
        $model  = M('TibiStatusLog');
        $count  = $model->alias('t')
             ->join('left join trade_user as u on t.uid = u.uid')
             ->where($where)->count();
        $Page   = new Page($count,15);
        
        $list = $model ->where($where)
            ->alias('t')
            ->join('left join trade_user as u on t.uid = u.uid')
            ->field('t.*,u.username')
            ->order('t.add_time desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        
        $this->assign('list',$list);
        $this->assign('page',$Page->show());
        $this->display();
    }
    /**
     * @method 获取查询参数
     * @return array 
     */
    private function getParams(){
        $where      = [];
        $ti_id      = strip_tags(trim(I('ti_id')));
        $uid        = strip_tags(trim(I('uid')));
        $name       = strip_tags(trim(I('username')));
        $admin      = strip_tags(trim(I('admin_user')));
        
        if(!empty($ti_id)) $where['t.ti_id']      = $ti_id;
        if(!empty($uid))   $where['t.uid']        = $uid;
        if(!empty($name))  $where['u.username']   = $name;
        if(!empty($admin)) $where['t.admin_name'] = $admin;
        
        $condition = [];
        if($this->_group_id == 10 ){
            //at 一線客服只能等於自己
            $condition['t.admin_name'] = $this->_admin_name;
            $condition['t.gid']        = $this->_group_id;
            $condition['_logic']       = 'AND';
            $where[] = $condition;
        }
        if($this->_group_id == 6) {
            //at 台灣主管
            $condition['t.admin_name'] = $this->_admin_name;
            $condition['t.gid']        = 10;
            $condition['_logic']       = 'OR';
            $where[] = $condition;
        }
        
        if($this->_group_id == 1) {
            //at 超級管理員 
            $condition['t.admin_name'] = $this->_admin_name;
            $condition['t.gid']        = ['IN',[10,6]];
            $condition['_logic']       = 'OR';
            $where[] = $condition;
        }
        return $where;
    }
    /**
     * @method 获取组id 
     * @param  string $username
     * @return int
     */
    private function getGidByUid(){
        $id  = $this->back_userinfo['id'];
        $res = M('AuthGroupAccess')->where(['uid'=>$id])->find();
        return !empty($res) ? $res['group_id']: 0 ;
    }
    
    /**
     * @author 建强  2018年12月5日18:04:13
     * @method excel 導出審核提幣記錄
     * @return string json
     */
    public function excel(){
        $data = [
            'code'=>400,
            'msg'=>'操作失敗',
            'data'=>[]
        ];
        if(!IS_AJAX && !IS_POST) $this->ajaxReturn($data);
        
        $s_time = I('s_time',0);
        $e_time = I('e_time',0);
        if($s_time<=0 || $e_time<=0){
            $data['msg']= '請選擇起始時間';
            $this->ajaxReturn($data);
        }
        if(date('Y-m-d H:i', strtotime($s_time))!=$s_time ||
           date('Y-m-d H:i', strtotime($e_time))!=$e_time)
        {
            $data['msg']="起始時間格式不正確";
            return $this->ajaxReturn($data);
        }
        
        $s_time  = ((strtotime($s_time))>0)?(strtotime($s_time)):0;
        $e_time  = ((strtotime($e_time))>0)?(strtotime($e_time)):time();
        
        $time_wh = ['t.add_time'=>['BETWEEN',[$s_time,$e_time]]]; 
       
        $where   = $this->getParams();
        $where   = array_merge($time_wh,$where);
        
        $res = M('TibiStatusLog')->alias('t')
            ->join('left join trade_user as u on t.uid = u.uid')
            ->where($where)
            ->field('t.*,u.username')
            ->order('t.add_time desc')
            ->select();
        if(empty($res)){
            $data['code']=406;
            $data['msg'] ='沒有符合要求的數據' ;
            $this->ajaxReturn($data);
        }
        
        $data['code']= 200;
        $data['msg'] ='success';
        $data['data']=[
            'title'=>'BTCS提幣審核日誌 ',
            'th'   => ['提幣記錄ID','用戶名ID','用戶姓名','管理員名稱','審核時間' ,'日誌'],
            'res'  => self::formatData($res),
        ] ;
        $this->ajaxReturn($data);
    }
    /**
     * @method excle 数据时间日期转换 
     * @param 日志历史
     * @return array 
     */
    private static function formatData($data){
        foreach ($data  as $key=>$value) {
            $data[$key]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
        }
        return $data; 
    }
}