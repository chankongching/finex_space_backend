<?php
namespace Back\Controller;
use Back\Tools\Page;
use Back\Common\EntrustType;
use Common\Api\RedisCluster;

/**
 * @desc 币币交易  配置数据
 * @author 建强   2017年11月22日
 */
class  CtwoCController extends  BackBaseController
{     
	  /**
	   * @method 列表配置项显示
	  */
      public function config(){  
           $ret=M('BiBiConfig')->order('add_time desc')->select();
      	   $this->assign('data_list',$ret);
           $this->display('index');
      }
      /**
       *  @method 添加币币交易区 以及配置 
      */
      public function addConfig(){  
	      	if(IS_POST)
	      	{
	      		$priceRate= trim(I('float_price_rate'));
	      		$sellFee  = trim(I('sell_fee'));
	      		$buyFee   = trim(I('buy_fee'));
	      		$currency_id=intval(trim(I('currency_id')));
	      		
	      		if(!is_numeric($priceRate) || !is_numeric($sellFee) || !is_numeric($buyFee)){
	      		    return $this->error('只能填写数字');
	      		}  		
	      		if(!is_numeric($currency_id)) return $this->error('请選擇交易區主幣種');
	      		if ($priceRate<0) return $this->error('價格浮動比例參數設置有誤');
	      	    if ($sellFee<0) return $this->error("賣家費的比例設置有誤");
	      	    if ($buyFee<0) return $this->error("買家費的比例設置有誤");
	      	    
	      	    $res=M('BiBiConfig')->where(['currency_id'=>$currency_id])->find();
	      	    if($res) return $this->error("該交易區名稱已存在");
	      	    $data=[
	      	        'coin_name'=>self::getCurrencyById($currency_id), 	
	      	    	'float_price_rate'=>$priceRate, 	
	      	    	'sell_fee'=>$sellFee, 	
	      	    	'buy_fee'=>$buyFee, 
	      	    	'add_time'=>NOW_TIME,
	      	        
	      	         //交易区id 值用主币id代替
	      	    	'currency_id'  =>$currency_id,
	      	        'trade_area_id'=>$currency_id,
	      	    ];
	      	    M()->startTrans();
	      	    $ret=[];
	      	    //添加可兑换币种 
	      	    $exchange_curr= [
	      	        'entrust_id'=>$currency_id,
	      	        'entrust_currency_id'=>$currency_id,
	      	        'add_time' =>time(),
	      	    ];
	      	    $ret[]=M('CanExchangeConfig')->add($exchange_curr);
	      	    $ret[]=M('BiBiConfig')->add($data);
	      	    if(in_array(false,$ret)){
	      	        M()->rollback();
	      	        return $this->error("配置添加失敗");
	      	    }
	      	    M()->commit();
	      	    return $this->success("配置添加成功",U('CtwoC/Config'));
	      	}
	      	
	        //获取配置列表 
	      	$trade_ids = M('BiBiConfig')->field('currency_id')->select();
	      	$trade_ids = array_column($trade_ids, 'currency_id');
	        $res       = M('Currency')->field('id,currency_name')->select(); 
	        $currName  = array_column($res, 'currency_name','id');
	        
	        //排除已经添加的交易币种
	        $curr_ids = array_keys($currName);
	        if(!empty($trade_ids)){
	            foreach ($curr_ids as $value){
	                if(in_array($value, $trade_ids)){
	                    unset($currName[$value]);
	                }
	           }   
	        }
	        $this->assign('curr_names',$currName);
        	$this->display('add');
      }
      /**
       *  @method 修改编辑币种配置信息
      */
      public function  editConfig()
      {
      	  if(IS_POST)
      	  {
      	  	  $priceRate=trim(I('float_price_rate'));
      	  	  $sellFee  =trim(I('sell_fee'));
      	  	  $buyFee   =trim(I('buy_fee'));
      	  	  
      	  	  if(!is_numeric($priceRate) || !is_numeric($sellFee) || !is_numeric($buyFee)){
      	  	      return $this->error('只能填写数字');
      	  	  }     	  	  
      	  	  if($priceRate<0) return $this->error('價格浮動比例參數設置有誤');
      	  	  if($sellFee<0)   return $this->error("賣家費的比例設置有誤");
      	  	  if($buyFee<0)	   return $this->error("買家費的比例設置有誤");
      	  	  $trade_area_id=trim(I('trade_area_id'));
      	  	  $data=[
      	  	      'float_price_rate'=>$priceRate, 'sell_fee'=>$sellFee,
      	  	      'buy_fee'=>$buyFee,'update_time'=>NOW_TIME,
      	  	  ];
      	  	  $result=M('BiBiConfig')->where(['trade_area_id'=>$trade_area_id])->save($data);
      	  	  if($result)  return $this->success("修改成功",U('CtwoC/Config'));
      	  	  return $this->error("修改失敗");
      	  }
      	  $aid =trim(I('trade_area_id'));
      	  $ret=M('BiBiConfig')->where(['trade_area_id'=>$aid])->find();
      	  $this->assign('data',$ret);
      	  $this->display('edit');
      }
      /**
       *  @method 删除币币配置
      */
      public function  deleteConfig()
      {
      	   $aid=intval(trim(I('trade_area_id')));
      	   if($aid<=0)  return $this->error("參數有誤");
      	   $ret=M('BiBiConfig')->where(['trade_area_id'=>$aid])->delete();
           if(empty($ret)) return $this->error("刪除失敗");
           return $this->success('刪除成功',U('CtwoC/Config'));
      }
      
      
     /* ============================刷单配置项 ============================*/
     /**
      * @method 机器人刷单数据配置列表
     */
     public function brushConfig()
     {
     	  $ret=M('BibiBrushConfig')->order('add_time desc')->select();
     	  $this->assign('data_list',$ret);
     	  $this->display('brushConfig');
     }
     /**
      * @method 添加刷单配置
      * @return bool
     */
     public function  brushConfigAdd()
     {
     	  if(IS_POST)
     	  {
     	  	    $type   =trim(I('entrust_type'));
	     	  	$s_price=trim(I('s_price'));
	     	  	$e_price=trim(I('e_price'));
	     	  	$s_num  =trim(I('s_num'));
	     	  	$e_num  =trim(I('e_num'));
	     	  	
	     	  	if(!is_numeric($type) || !is_numeric($s_price) ||  !is_numeric($e_price)  
	     	  	   ||  !is_numeric($s_num)  || !is_numeric($e_num)){
	     	  	    return $this->error('只能填写数字');
	     	  	}
	     	  	
	     	  	if($s_price<0) return $this->error("價格參數輸入有誤");
	     	  	if($e_price<0) return $this->error("價格參數輸入有誤");
	     	  	if($e_num<0)   return $this->error("數量參數有誤");
	     	  	if($s_num<0)   return $this->error("數量參數有誤");
	     	  	if($type<0)    return $this->error("類型參數錯誤");
	     	  	$ret=M('BibiBrushConfig')->where(['entrust_type'=>$type])->find();
	     	  	if($ret)  return $this->error("該委托類型已經存在,請勿重復添加");
	     	  	$data=[
	     	  		'entrust_type'=>$type,
	     	  		'price'=>$s_price.'-'.$e_price,
	     	  		'num'=>$s_num.'-'.$e_num,
	     	  		'add_time'	=>NOW_TIME,
	     	  	];
                $res=M('BibiBrushConfig')->add($data);	  	
                if($res) return $this->success("添加成功",U('CtwoC/brushConfig'));
                return $this->error("添加失敗");
     	  }    	  
     	  //获取交易对配置       	  
     	  $this->assign("entrust_type",$this->entrust_type());
     	  $this->display();
     }
     /**
      * @method 编辑刷单配置 
     */
     public function brushConfigEdit(){
     	  if(IS_POST)
     	  {  
     	  	 $s_price=trim(I('s_price'));
     	  	 $e_price=trim(I('e_price'));
     	  	 $s_num  =trim(I('s_num'));
     	  	 $e_num  =trim(I('e_num'));
           
     	  	 if( !is_numeric($s_price) ||  !is_numeric($e_price)
     	  	     || !is_numeric($s_num)  || !is_numeric($e_num)){
     	  	     return $this->error('只能填写数字');
     	  	 }
     	  	
     	  	 if($s_price<0) return $this->error("價格參數輸入有誤");
             if ($e_price<0) return $this->error("價格參數輸入有誤");
             if ($e_num<0)  return  $this->error("數量參數有誤");
             if($s_num<0) return $this->error("數量參數有誤");
             
             $id=trim(I('id'));
             $data=[
                 'price'=>$s_price.'-'.$e_price,'num'=>$s_num.'-'.$e_num,
                 'update_time'=>NOW_TIME,
	     	 ];
             
             $res=M('BibiBrushConfig')->where(['id'=>$id])->save($data);
             if ($res) return $this->success("修改成功",U('CtwoC/brushConfig'));
             return $this->error("修改失敗");
     	  }
     	  
     	  $id=I('id');
          $ret=M('BibiBrushConfig')->find($id);
          $arrPrice=explode('-', $ret['price']);
          $arrNum  =explode('-', $ret['num']);
          $ret['s_price']=$arrPrice[0];
          $ret['e_price']=$arrPrice[1]?$arrPrice[1]:0;
          $ret['s_num']  =$arrNum[0];
          $ret['e_num']  =$arrNum[1]?$arrNum[1]:0;
          
          $this->assign("data_list",$ret);
          $this->assign("entrust_type",$this->entrust_type());
          $this->display("brushConfigEdit");
     }
     
     /**
	 * @method 获取比特幣交易币种对信息 
	 * @return array  LTC/BTC ....
	 */
     protected function entrust_type(){
	     $entrust_type = EntrustType::getEntrustTypeList();
	     $entrust_type[''] = '請選擇';
	     ksort($entrust_type);
	     return $entrust_type;
	}
     /**
      * @method 删除刷单配置币种数据
     */
     public function brushConfigDel()
     {
     	  $id=I('id');
     	  if($id<0) return $this->error("參數錯誤");
     	  $ret=M('BibiBrushConfig')->delete($id);
     	  if($ret)  return $this->success("刪除成功",U('CtwoC/brushConfig'));
     	  return $this->error("刪除成功");
     }
     
     /* ============================能够兑换的币种 ============================*/
    /*
       * 可兑换币种配置
       * 李江
       * 2017年11月29日18:50:38
       */
    public function canExchange(){
        $all_exchange = M('BiBiConfig')->field('trade_area_id,coin_name')->select();
        $entrust_id = intval(I('entrust_id'));
        if( $entrust_id && $entrust_id != -1 ){
            $where['c2c.entrust_id'] = $entrust_id;
        }

        if( $where ){
            $all_can_exchange = M('CanExchangeConfig')->alias('c2c')
                ->join('__CURRENCY__ as c on c2c.entrust_id=c.id','left')
                ->where($where)
                ->limit(1)
                ->field('c.currency_name,c2c.*')
                ->order('c2c.id desc')
                ->select();
        }else{
            $count = M('CanExchangeConfig')->count();
            $page = new Page($count,10);
            $all_can_exchange = M('CanExchangeConfig')
                                ->alias('c2c')
				                ->join(' left join __CURRENCY__ as c on c2c.entrust_currency_id=c.id')
				                ->limit($page->firstRow.','.$page->listRows)
				                ->field('c.currency_name,c2c.*')
				                ->order('c2c.id desc')
				                ->select();
            $show = $page->show();
            $this->assign('page',$show);
        }

        foreach ($all_can_exchange as $k=>$v){
            $all_can_exchange[$k]['can_exchange_str'] = $this->getCurrencyNameByIdStr($v['can_exchange_currencys']);
        }
        $this->assign('list',$all_can_exchange);
        $this->assign('change_list',$all_exchange);
        $this->display();
    }

    private function getCurrencyNameByIdStr($str){
        $res = M('Currency')->where(['id'=>['in',$str]])->field('id,currency_name')->select();
        $new_str = '';
        foreach ($res as $k=>$v){
            $new_str .= $v['currency_name'].',';
        }
        return trim($new_str,',');
    }
    /*
     * 修改可兑换币种
     * 李江
     */
    public function editCanExchange(){
        if( IS_POST ){
            $data = I('post.');
            $id = $data['id'];
            $can_exchange_currencys_arr = $data['can_exchange_currencys'];
            $can_exchange_currencys = implode(',',$can_exchange_currencys_arr);
            $res = M('CanExchangeConfig')->where(['id'=>$id])->save(['can_exchange_currencys'=>$can_exchange_currencys]);
            if($res) {
                //可兑换币种调整成功 删除前台缓存
                self::delCurrencyInfoKey();
                $this->success('修改成功',U('CtwoC/editCanExchange',['id'=>$id]));
            }
            $this->error('修改失敗');
        }else {
            $id = I('id');
            $record = M('CanExchangeConfig')->alias('c2c')
                ->join('left join __CURRENCY__ as c on c.id=c2c.entrust_id')
                ->field('c.currency_name,c2c.*')
                ->where(['c2c.id' => $id])->find();
            $record['can_exchange_str'] = $this->getCurrencyNameByIdStr($record['can_exchange_currencys']);
            $entrust_id = $record['entrust_id'];
            $where['id'] = ['neq',$entrust_id];
            $all_can_exchange = M('Currency')->where($where)->select();
            foreach ($all_can_exchange as $k => $v) {
                $all_can_exchange[$k]['flag'] = 0;
                if (stripos($record['can_exchange_currencys'], $v['id']) !== false) {
                    $all_can_exchange[$k]['flag'] = 1;
                }
            }
            $this->assign('record', $record);
            $this->assign('all_can', $all_can_exchange);
            $this->display();
        }
    }

    /**
     * @method 刪除币币交易价格牌数据 2019年3月14日10:32:06 
     * @return bool
     */
    protected static function delCurrencyInfoKey(){
        $redis = RedisCluster::getInstance();
        $keys  = ['CURTENCY_INFO_LIST_BY_OKEX','CURTENCY_INFO_LIST_LOING_BY_OKEX'];
        return $redis->del($keys);
    }
    /*
     * 删除
     * 李江
     */
    public function delCanExchange(){
        //删除可兑换的币种的信息  关联删除交易区的配置
        $id = I('id');
        $ret = [];
        $coin_info = M('CanExchangeConfig')->find($id);
        if(empty($coin_info)) $this->ajaxReturn(['status'=>403,'msg'=>'刪除失敗']);
        
        M()->startTrans();
        $ret[] = M('BiBiConfig')->where(['trade_area_id'=>$coin_info['entrust_currency_id']])->delete();
        $ret[] = M('CanExchangeConfig')->where(['id'=>$id])->delete();
        
        if(in_array(false, $ret)){
            M()->rollback();    
            $this->ajaxReturn(['status'=>404,'msg'=>'刪除失敗']);
        }
        M()->commit();
        $this->ajaxReturn(['status'=>200,'msg'=>'刪除成功']);
    }
    /*
     * vp币币交易
     * 李江
     * 2017年12月5日14:32:33
     */
    public function vpConfig(){
    	
        if( I('exchange_currency_id') ){
            $where['exchange_currency_id'] = intval(I('exchange_currency_id'));
        }
        if( $where ){
            $data_list = M('SecondClassCoinConfig')->alias('sccc')->join('left join __CURRENCY__ as c on c.id=sccc.exchange_currency_id left join __CURRENCY__ as cc on sccc.main_currency_id=cc.id')
                ->field('cc.currency_name as main_name,c.currency_name,sccc.*')->where($where)
                ->order('add_time desc')->select();
        }else{
            $data_list = M('SecondClassCoinConfig')->alias('sccc')->join(' left join __CURRENCY__ as c on c.id=sccc.exchange_currency_id  left join __CURRENCY__ as cc on sccc.main_currency_id=cc.id')
                ->field('cc.currency_name as main_name,c.currency_name,sccc.*')->order('add_time desc')
                ->select();
        }
        $this->assign('data_list',$data_list);
        $this->display();
    }

    /*
     * 编辑vpConfig
     */
    public function editVpConfig(){
        if( IS_POST )
        {
            $data = I('post.');
            $id = $data['id'];
            
            if(!is_numeric($data['buy_fee']) || !is_numeric($data['sell_fee'])  || !is_numeric($data['main_coin_price']) ||  !is_numeric($data['float_price_rate']))
            {
                 return  $this->error("参数只能输入数字")	;
            }
            
            if ($data['buy_fee']<0 || $data['sell_fee']<0  ||  $data['main_coin_price']<0 ||  $data['float_price_rate']<0 )
            {
            	return $this->error("参数比例格式不正确");
            }
            $data['update_time']=NOW_TIME;
            unset($data['id']);
            $res = M('SecondClassCoinConfig')->where(['id'=>$id])->save($data);
            if($res)
            {
                $this->success('修改成功',U('/Back/CtwoC/vpConfig'));
            }
            else
            {
                $this->error('修改失敗');
            }
        }
        else
        {
            $id = I('id');
            $info = M('SecondClassCoinConfig')->alias('sccc')
                ->join('left join __CURRENCY__ as c on sccc.exchange_currency_id=c.id left join __CURRENCY__ as cc on sccc.main_currency_id=cc.id' )
                ->field('sccc.*,c.currency_name as exchange_name,cc.currency_name as main_name')
                ->where(['sccc.id'=>$id])->find();
            
            $this->assign('data',$info);
            $this->display();
        }
    }
    /*
     * 删除vpConfig
     */
    public function delVpConfig(){
        $id = I('id');
        if( $id ){
            $res = M('SecondClassCoinConfig')->where(['id'=>$id])->delete();
            if( $res ){
                $this->success('刪除成功',U('/Back/CtwoC/vpConfig'));
            }else{
                $this->error('刪除失敗');
            }
        }else{
            $this->error('服務器繁忙');
        }
    }
    /*
     * 添加vpConfig记录
     */
    public function addVpConfig(){
        if(IS_POST){
            $main_currency=trim(I('main_currency_id'));       
            $exchange_currency=trim(I('exchange_currency_id'));
            $sellFee=trim(I('sell_fee'));
            $buyFee=trim(I('buy_fee'));
            $price=trim(I('main_coin_price'));
            $FloatRatePrice=trim(I('float_price_rate'));
            
            if ($main_currency==$exchange_currency)
            {
                return  $this->error('同种币无法 进行换币交易');            	
            }
            if ($sellFee<0 || $buyFee<0  || $price<0 || $FloatRatePrice<0 )
            {
            	return $this->error("参数比例不能为负数");
            }
           
            $data=[
            	'main_currency_id'=>$main_currency,
            	'exchange_currency_id'=>$exchange_currency,
            	'sell_fee'=>$sellFee?$sellFee:0,	
            	'buy_fee'=>$buyFee?$buyFee:0,
            	'main_coin_price'=>$price?$price:0,
            	'float_price_rate'=>$FloatRatePrice?$FloatRatePrice:0,
            	'add_time'=>NOW_TIME,
            ];
          
            $ret=M('SecondClassCoinConfig')->where(['main_currency_id'=>$main_currency,'exchange_currency_id'=>$exchange_currency])->find();
            if($ret)
            {
            	return $this->error('该配置已经存在，勿重复添加');
            }
            $res = M('SecondClassCoinConfig')->add($data);
            if( $res ){
                return $this->success('添加成功',U('/Back/CtwoC/vpConfig'));
            }else{
                return $this->error('添加失敗');
            }
        } 
        $currency_list = M('Currency')->field('id,currency_name')->select();
        $this->assign('currency_list',$currency_list );
        $this->display();
    }
}