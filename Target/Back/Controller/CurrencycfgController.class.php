<?php
namespace Back\Controller;
/**
 * @desc   币种相关控制器
 * @author 宋建强  2017年11月2日   currency配置信息
 * 
*/
class CurrencycfgController extends BackBaseController
{
    /**
     * @method 添加币种
    */
    public function Add()
    {
        if(IS_POST)
        {
        	$currency_name=trim(I('currency_name'));
        	$currency_mark=trim(I('currency_mark'));
        	$status=trim(I('status'));
        	
        	//线上交易买家麦家
        	$buy_the_line_fee=trim(I('buy_the_line_fee'));
        	$sell_the_line_fee=trim(I('sell_the_line_fee'));
            //线下交易买家麦家       	
        	$sell_off_line_fee=trim(I('sell_off_line_fee'));
        	$buy_off_line_fee=trim(I('buy_off_line_fee'));
        	
            if ($buy_the_line_fee*1<0 || ($buy_the_line_fee)*1<0  || $sell_off_line_fee*1<0  ||$buy_off_line_fee*1<0)
            {
           	  return $this->error("输入参数有误");
            }
        	$buy_the_line_fee=!empty($buy_the_line_fee)?$buy_the_line_fee:0;
        	$sell_the_line_fee=!empty($sell_the_line_fee)?$sell_the_line_fee:0;
        	
        	$sell_off_line_fee=!empty($sell_off_line_fee)?$sell_off_line_fee:0;
        	$buy_off_line_fee=!empty($buy_off_line_fee)?$buy_off_line_fee:0;
        	
        	if (empty($currency_mark) || empty($currency_name))
        	{
        		 return $this->error("幣種名或英文標識标识不能为空");
        	}
        	$ret=M('Currency')->where(['currency_name'=>$currency_name])->find();
        	if ($ret)
        	{
        		 return $this->error('该名称已被占用');
        	}
        	$logo_path=$big_logo_path='';
            $file_logo=$this->uploadOne('currency_logo');
             
            if ($file_logo['status']==true)
            {
            	$logo_path=$file_logo['info'];
            }
        	$file_big_logo=$this->uploadOne('currency_big_logo');
        	if ($file_big_logo['status']==true)
        	{
        		$big_logo_path=$file_big_logo['info'];
        	}

           $data=[
           	   	'currency_name'=>$currency_name,
           	   	'currency_mark'=>$currency_mark,
           		'buy_the_line_fee'=>$buy_the_line_fee,
           		'sell_the_line_fee'=>$sell_the_line_fee,
           		'sell_off_line_fee'=>$sell_off_line_fee,
           		'buy_off_line_fee'=>$buy_off_line_fee,
           		'status'=>$status,
           		'currency_logo'=>$logo_path,
           		'currency_big_logo'=>$big_logo_path,
           ];
           $result=M('Currency')->add($data);        
           if($result) return $this->success('添加成功',U('Currencycfg/index'));
           return $this->error('添加失败');
        }
        $this->display();
        
    }
    /**
     * @method 币种列表显示
     */
    public function index()
    {   
        $res=M('Currency')->select();
        $this->assign('data_list',$res);
        $this->display();
    }
    /**
     * @method 修改币种信息
    */
    public function edit()
    {     
    	 if(IS_POST)
    	 {
    	      $data=I('post.');	 
    	      $id=I('post.id'); 
    	      if($_FILES['currency_logo']){
	    	       $file_logo=$this->uploadOne('currency_logo');
	    	       if($file_logo['status']==true){
	    	           $data['currency_logo']=$file_logo['info'];
	    	       }
    	      }
	    	  if($_FILES['currency_big_logo']){
		    	  $file_logo=$this->uploadOne('currency_big_logo');
		    	  if($file_logo['status']==true){
		    	      $data['currency_big_logo']=$file_logo['info'];
		    	  }
	    	  }
             if($_FILES['currency_app_logo']){
                 $file_logo=$this->uploadOne('currency_app_logo');
                 if ($file_logo['status']==true){
                     $data['currency_app_logo']=$file_logo['info'];
                 }
             }
	    	 unset($data['id']);
	    	 $res=M('Currency')->where(['id'=>$id])->save($data);
	    	 if($res){
	    	     //删除缓存币种信息价格
	    	     self::delCoinInfo($id,$data['status']);
	    	     return $this->success('修改成功',U('Currencycfg/index'));
	    	 }
             return $this->error('修改失败');
    	 }
    	 $id=trim(I('id'));
         $res=M('Currency')->where(['id'=>$id])->find();
         $this->assign('data_list',$res);
         $this->display();
    }
    /**
     * @method 下架指定幣種價格牌數據
     * @param  id 幣種id
     * @param  币种上下线字段 status
     * @return bool|[] 
     */
    protected static function delCoinInfo($id,$status){
        $ret    =[];
        $redis  = \Common\Api\RedisCluster::getInstance();
        $keys   =[
            'APP_COIN_INFO_LIST_BY_BIF','COIN_INFO_LIST_BY_BIF',
        ];
        //首页行情数据
        $pcMarketkeys = ['INDEX_PAGE_MARKET_CURRS_INFO','ON_LINE_CURRENCYS'];
        $ret[]        = $redis->del($pcMarketkeys);        
        if($status==1) return $ret; //上线不删除
        //删掉缓存数据 
        foreach($keys as $key){
            $coin_info = $redis->get($key);
            if(empty($coin_info)) continue;
            $coin_info = unserialize($coin_info);
            foreach($coin_info as $k=>$value){
                if($value['currency_id']!=$id) continue;
                unset($coin_info[$k]);
                if($key == 'APP_COIN_INFO_LIST_BY_BIF') $coin_info = array_values($coin_info);
                $ret[] = $redis->setex($key,300,serialize($coin_info));
                break;
            }
        }
        return $ret;
    }
    /**
     * @method 上传图片文件的方法后台上传的logo图片
     */
    private function uploadOne($name)
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg','svg');// 设置附件上传类型
        $upload->rootPath  =     './Upload/Back/'; // 设置附件上传根目录

        $upload->saveName  =     $name.'_'.time().'_'.rand(100000,999999);
        // 上传单个文件
        $info   =   $upload->uploadOne($_FILES[$name]);
        if(!$info) {// 上传错误提示错误信息
            $data['info']=$upload->getError();
            $data['status']=false;
            return $data;
        }else{// 上传成功 获取上传文件信息
            $data['info']='/Upload/Back/'.$info['savepath'].$info['savename'];
            $data['status']=true;
            return $data;
        }
    }
}