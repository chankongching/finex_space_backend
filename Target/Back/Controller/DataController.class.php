<?php
namespace Back\Controller;
use Think\Model;

class DataController extends BackBaseController
{
	
	/**
	 * 币种统计管理每天的数据定时任务跑
	 * @author 宋建强  2017年10月25日
	*/ 
    public function tradeData()
    {    
    	//获取时间
    	$times=I('start_time');
    	$currency_id = I('currency_id');
    
    	$where=[];
        if (!empty($times))
        {   
        	$time=explode(" - ",$times);
        	$start=date('Y-m-d',strtotime($time[0]));
        	$end  =date('Y-m-d',strtotime($time[1]));
            $where['trade_data_statistic.date']=array('between',"{$start},{$end}"); //注意只能between
        }
        if($currency_id!='-1' && !empty($currency_id))
        {   
            $where['trade_data_statistic.currency_id']=$currency_id;         
        }
        
        if(count($where))
        {  
            $where['type']=1;  //P2P交易类型
        	$count = M('DataStatistic')->join('left join trade_currency on trade_data_statistic.currency_id=trade_currency.id')->where($where)->count();
        	$Page = new \Back\Tools\Page($count,10);
        	$data_list = M('DataStatistic')
        	            ->join('left join trade_currency on trade_data_statistic.currency_id=trade_currency.id')
        	            ->where($where)
        	            ->field('trade_currency.currency_name,trade_data_statistic.*')
        	            ->order('trade_data_statistic.date desc,trade_data_statistic.id asc')->limit($Page->firstRow.','.$Page->listRows)->select();
        }
        else 
        {   
            $map['type']=1;  
        	$count = M('DataStatistic')->join('left join trade_currency on trade_data_statistic.currency_id=trade_currency.id')->where($map)->count();
        	$Page = new \Back\Tools\Page($count,10);
        	$data_list = M('DataStatistic')->join('left join trade_currency on trade_data_statistic.currency_id=trade_currency.id')
        	            ->field('trade_currency.currency_name,trade_data_statistic.*')
        	            ->where($map)
        	            ->limit($Page->firstRow.','.$Page->listRows)
        	            ->order('date desc,id asc')
        	            ->select();
        }
        $show  = $Page->show();
        $currency_list = D('Currency')->getCurrencyList();
        
        $this->assign('currency_list',$currency_list);
        $this->assign('data_list',$data_list);
        $this->assign('page',$show);
        $this->display();
    }
    
    
    /**
     *@method 建强  C2C交易每天数据统计  
    */
    public function CCtradeData()
    {
        //获取时间
        $times=I('start_time');
        $currency_id = I('currency_id');
    
        $where=[];
        if (!empty($times))
        {
            $time=explode(" - ",$times);
            $start=date('Y-m-d',strtotime($time[0]));
            $end  =date('Y-m-d',strtotime($time[1]));
            $where['trade_data_statistic.date']=array('between',"{$start},{$end}"); //注意只能between
        }
        if($currency_id!='-1' && !empty($currency_id))
        {
            $where['trade_data_statistic.currency_id']=$currency_id;
        }
    
        if(count($where)>0)
        {  
            $where['type']=2;  //C2C交易类型
            
            $count = M('DataStatistic')->join('left join trade_currency on trade_data_statistic.currency_id=trade_currency.id')->where($where)->count();
            $Page = new \Back\Tools\Page($count,10);
            $data_list = M('DataStatistic')
            ->join('left join trade_currency on trade_data_statistic.currency_id=trade_currency.id')
            ->where($where)
            ->field('trade_currency.currency_name,trade_data_statistic.*')
            ->order('trade_data_statistic.date desc,trade_data_statistic.id asc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        }
        else
        {   
            $map['type']=2;
            $count = M('DataStatistic')->join('left join trade_currency on trade_data_statistic.currency_id=trade_currency.id')->where($map)->count();
            $Page = new \Back\Tools\Page($count,10);
            $data_list = M('DataStatistic')
                        ->join('left join trade_currency on trade_data_statistic.currency_id=trade_currency.id')
                        ->where($map)
                        ->field('trade_currency.currency_name,trade_data_statistic.*')
                        ->limit($Page->firstRow.','.$Page->listRows)
                        ->order('date desc,id asc')
                        ->select();
        }
        $show  = $Page->show();
        $currency_list = D('Currency')->getCurrencyList();
        $this->assign('currency_list',$currency_list);
        $this->assign('data_list',$data_list);
        $this->assign('page',$show);
        $this->display();
    }
    
    
    
    /**
     * 数据统计 统计平台到目前为止各种币的总数量
     * @author 宋建强 2017年10月19日
     */
    public function getTotalNum()
    {    
    	 $model=new Model();
    	 $sql= "select c.currency_name,sum(u.num) as num  from trade_currency as c
    	 		left join  trade_user_currency as u  on u.currency_id=c.id 
    	 		group by c.currency_name";
    	 $total=$model->query($sql);
    	 $this->assign("data_list",$total);
    	 $this->display('sum');
    }
}