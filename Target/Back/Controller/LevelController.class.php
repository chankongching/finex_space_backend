<?php
namespace Back\Controller;
/**
 * @author 宋建强  2017年11月1日 16:37
 * @desc   後台幣種配置  
 */
class LevelController extends BackBaseController
{    
	 //用户等级
	 public $level=[
	 	  	'0'=>'0級', 
	 	  	'1'=>'VIP1級',
	 	  	'2'=>'VIP2級',
	 	  	'3'=>'VIP3級',
	 	  	'4'=>'VIP4級',
	 	  	'5'=>'VIP5級',
	 	  	'6'=>'SUPERVIP級',  //特别注意6为vip等级
	 ];
	 
     //搜索参数转换
     public $search=[
     		'-0'=>'0级',  //10 转化
     		'1'=>'VIP1級',
	 	  	'2'=>'VIP2級',
	 	  	'3'=>'VIP3級',
	 	  	'4'=>'VIP4級',
	 	  	'5'=>'VIP5級',
     		'6'=>'SUPERVIP級',  //特别注意6为vip等级
     ];

	 public function _initialize()
	 {   
	 	 $res=M('Currency')->field('id,currency_name')->select();
	 	 $this->assign('currency_list',array_column($res, 'currency_name','id'));
	 	 $this->assign('level_vip',$this->level);
	 	 $this->assign('level_vip_search',$this->search);
	 	 parent::_initialize();
	 }
	 /**
	  * 等级配置列表显示
	 */
	 public function index()
	 {    
	 	  $currency_id=trim(I('currency_id')); 
	 	  $level=trim(I('level')); 
	 	  $where = [];
	 	  if(!empty($currency_id)) $where['currency_id']=$currency_id;
	 	  if(!empty($level))$where['vip_level']=$level;
	 	  
	 	  if(count($where)>0){
	 	  	 $count = M('LevelConfig')->where($where)->count();
	 	  	 $Page = new \Back\Tools\Page($count,15);
	 	  	 $list= M('LevelConfig')->order('add_time desc')->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
	 	  }else{
	 	  	 $count = M('LevelConfig')->count();
	 	  	 $Page = new \Back\Tools\Page($count,15);
	 	  	 $list = M('LevelConfig')->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
	 	  }
	 	  
	 	  $show  = $Page->show();
	 	  $this->assign('data_list',$list);
	 	  $this->assign('page',$show);     
	 	  $this->display();
	 }
	 /**
	  * 添加等级配置
	 */
    public function add()
    {    
    	 if(IS_POST)
    	 {  
    	 	$level=trim(I('post.level'));
    	 	$currency_id=trim(I('post.currency_id'));
    	 	$day_max_sell_amount=trim(I('post.day_max_sell_amount'));
    	 	$day_max_tibi_amount=trim(I('post.day_max_tibi_amount'));
    	 	$buy_order=trim(I('post.buy_order'));
    	 	$sell_order=trim(I('post.sell_order'));
    	 	$ti_usd_fee=trim(I('post.ti_usd_fee'));
    	 	$coin_fee=trim(I('post.coin_fee'));
    	 	$min_tibi_amount=trim(I('post.min_tibi_amount'));
    	 	 
    	 	if(empty($level) && $level!=0) return $this->error($this->ErrMsg['level']);
    	 	if(empty($currency_id)) return $this->error($this->ErrMsg['currency_id']);
    	 	if(empty($day_max_sell_amount) && $day_max_sell_amount!=0) return $this->error($this->ErrMsg['day_max_sell_amount']);
    	 	if(empty($day_max_tibi_amount) && $day_max_tibi_amount!=0) return $this->error($this->ErrMsg['day_max_tibi_amount']);
    	 	if(empty($buy_order) && $buy_order!=0) return $this->error($this->ErrMsg['buy_order']);
    	 	if(empty($sell_order) && $sell_order!=0) return $this->error($this->ErrMsg['sell_order']);
    	 	if(empty($ti_usd_fee) && $ti_usd_fee!=0) return $this->error($this->ErrMsg['ti_usd_fee']);
    	 	if(empty($coin_fee) && $coin_fee!=0) return $this->error($this->ErrMsg['coin_fee']);
    	 	//判断是否重复添加
    	 	$res=M('LevelConfig')->where(["vip_level"=>$level,'currency_id'=>$currency_id])->find();
    	 	if($res) return $this->error('該幣種該等級配置已經配置,請勿重復添加');
    	    $data=[
    	    		"vip_level"=>$level,
    	    		"currency_id"=>$currency_id,
    	    		"coin_fee"=>$coin_fee,
    	    		"day_max_sell_amount"=>$day_max_sell_amount,
    	    		"day_max_tibi_amount"=>$day_max_tibi_amount,
    	            'min_tibi_amount'=>$min_tibi_amount,
    	    		"buy_order"=>$buy_order,
    	    		"sell_order"=>$sell_order,
    	    		"ti_usd_fee"=>$ti_usd_fee,
    	    		"add_time" =>time(),
    	    ];
    	    $ret=M('LevelConfig')->add($data);
    	    if($ret) return $this->success('添加成功',U('Level/index'));
    	    return $this->error('添加失敗');
    	 }
    	 $this->display();
    }	
    public $ErrMsg=[
    	  "level"=>'等級名稱必須填寫',	
    	  "currency_id"=>'幣種名稱必須填寫',	
    	  "coin_fee"=>'幣種提幣手續費稱必須填寫',	
    	  "day_max_sell_amount"=>'每日最大掛單數量稱必須填寫',	
    	  "day_max_tibi_amount"=>'每日提幣數量名稱必須填寫',	
    	  "buy_order"=>'買單數必須填寫',	
    	  "sell_order"=>'賣單數稱必須填寫',	
    	  "ti_usd_fee"=>'提幣美金手續費',	
    	 
    ];
    /**
     * @method 修改编辑等級配置
     * @author 建強  2019年2月28日15:23:31 
     *  
    */
    public function edit()
    {
    	   //修改表单
    	   if($_POST)
    	   {
    	   	   $id=I('post.l_id');
    	   	   $searchLevel=I('post.serachLevel');
    	   	   $cid=I('post.cid');
    	   	   $data=I('post.');
    	   	   unset($data['l_id']);
    	   	   unset($data['serachLevel']);
    	   	   unset($data['cid']);
    	   	   foreach ($data as $k=>$v)
    	   	   {
    	   	   	   if (!is_numeric($v) || $v*1<0)
    	   	   	   {  
    	   	   	   	   return $this->error("参数输入的格式有误");
    	   	   	   }
    	   	   }
    	   	   $data['update_time']=NOW_TIME;
    	   	   $res=M('LevelConfig')->where(['id'=>$id])->save($data);
    	   	   if ($res) return $this->success('修改成功',U('Level/index',['level'=>$searchLevel,'currency_id'=>$cid]));
               return $this->error("修改失敗");
    	   }
	   	   $id=trim(I('id'));
    	   $c_id=trim(I('currency_id'));
    	   $level=trim(I('level'));
    	   $res= M('LevelConfig')->where(['id'=>$id])->find();
    	   $this->assign('data',$res);
    	   $this->assign('cid',$c_id);
    	   $this->assign('level',$level);
    	   $this->display();
    }
    /**
     * @method 删除记录  
     * @author 建強  2019年2月28日15:26:19 
     */
    public  function delete()
    { 
    	  $id=trim(I('id'));
          if($id)
          { 
          	  $res=M('LevelConfig')->where(['id'=>$id])->delete();
          	  if($res) return $this->success('刪除成功',U('Level/index'));
          	  return $this->erro('刪除失敗');
          }
    }
}