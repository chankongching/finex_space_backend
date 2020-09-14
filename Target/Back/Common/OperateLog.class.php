<?php
namespace Back\Common;
/**
 * 后台管理员客服操作日志记录
 * @author 宋建强 2017年8月22日
 */
class  OperateLog
{
    /*
     * 线上交易撤销订单日志
     * table   newtrade_changeuser_money_log
     * type   7.线上交易撤销订单
    */
	public static function insert_line_trade_order_log($uid,$order_id,$admin_user,$log,$type=7)
	{   
         $data['user_id']=$uid;  		        //用户id
         $data['username']=$order_id;       //用户名
         $data['admin_user']=$admin_user;   //后台管理人员
         $data['type']=$type;  		        //类型 type=7
         $data['log']=strip_tags($log);  	//描述记录
         $data['add_time']=time();          //操作时间 		
         
         $res=M('ChangeuserMoneyLog')->add($data);
         if($res)
         {
         	 return true ;
         }
         return false;
	}
	
	/*
	 * 线下交易修改金额 | 修改订单的状态 日志记录 
	 * table newtrade_changeuser_money_log 
	 * type类型  1.线下交易订单状态修改，2.线下交易金额修改
    */
	public static function insert_off_trade_moneyORorder_log($sell_name,$buy_name,$username,$order_num,$log,$type=1)
	{  
		$data['sell_name']=$sell_name;  	//卖家
		$data['buy_name']=$buy_name;  		//买家
		$data['admin_user']=$username;      //后台操作用户名
		$data['order_num']=$order_num;      //订单号
		$data['type']=$type;  		        //类型
		$data['log']=$log;                  //描述记录
		$data['add_time']=time();           //操作时间
		
		$res=M('OrderStatusLog')->add($data);
		if($res)
		{
		   return true ;
		}
		return false;
	}
	
   /**
    * 提币到钱包操作日志记录
    * table   newtrade_changeuser_money_log
    * type  8.提币记录修改状态
    */
	public static function insert_tibi_log($uid,$username,$admin_user,$log,$type=8)
	{  
		$data['user_id']=$uid;  		    //用户id
		$data['username']=$username;    //用户名
		$data['admin_user']=$admin_user;//后台管理人员
		$data['type']=$type;  		    //类型 type=5 | 6
		$data['log']=strip_tags($log);  //描述记录
		$data['add_time']=time();       //操作时间
			
		$res=M('ChangeuserMoneyLog')->add($data);
		if($res)
		{
			return true ;
		}
		return false;
	}
	
	/**
	 * 身份证认证的修改操作日志记录
	 *table   newtrade_changeuser_log
	 *type 10 身份认证状态修改
	*/ 
	public static function inser_user_authentication_log($uid,$username,$admin_user,$log,$type=9)
	{
		$data['user_id']=$uid;  		    //用户id
		$data['username']=$username;       //用户名
		$data['admin_user']=$admin_user;   //后台管理人员
		$data['type']=$type;  		       //类型 type=10
		$data['log']=strip_tags($log);     //描述记录
		$data['add_time']=time();          //操作时间
			
		$res=M('ChangeUserLog')->add($data);
		if($res)
		{
			return true ;
		}
		return false;
	}
}