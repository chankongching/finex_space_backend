<?php
/**
 * 积分加减比例
 * @author  宋建强   2017年11月1日 17:00
 */
namespace Back\Tools;

class Point
{       
		/*
		1.绑定电话号码 
		2.绑定邮箱   
		3.绑定充值地址 
		4.绑定转出地址 
		5.绑定APP令牌  
		6.交易密码   
		7.银行卡账户  
		8.每天首次登陆  
		9.订单交易 
		10.充值钱 
		11充值币 
		12 vip充值资产额  
		13实名认证通过
		*/
	   CONST BIND_PHONE_STATUS=1;
	   CONST BIND_EMAIL_STATUS=2;
	   CONST BIND_CHARGE_URL_STATUS=3;
	   CONST BIND_TRANSFER_URL_STATUS=4;
	   CONST BIND_APP_TOKEN_STATUS=5;
	   CONST BIND_TRADE_PASS_STATUS=6;
	   CONST BIND_BANK_CARD_STATUS=7;
	   CONST BIND_FIRST_LOGIN_STATUS=8;
	   CONST BIND_TRADE_ORDER_STATUS=9;
	   CONST BIND_CHARGE_MONEY_STATUS=10;
	   CONST BIND_CHARGE_NUM_STATUS=11;
	   CONST BIND_CHARGE_ASSETS_STATUS=12;
	   CONST BIND_PASS_REAL_STATUS=13;
	 
	   /*等级多需的积分 等级0分  supervip付费升级*/
	   //VIP 1级   	
	   CONST VIP_ONE=100;
	   //VIP 2级   
	   CONST VIP_TWO=1000;
	   //VIP 3级
	   CONST VIP_THREE=3000;
	   //VIP 4级
	   CONST VIP_FOUR=6000;
	   //VIP 5级
	   CONST VIP_FIVE=16000;
	   
	   /*资料填写加分项*/
	   //绑定邮箱
	   CONST ADD_EMAIL=10; 
	   //绑定电话号码
	   CONST ADD_PHONE=10;
	   //绑定充值地址
	   CONST ADD_CHARGE_URL=10;
	   //绑定转出地址
	   CONST ADD_TRANSFER_URL=10;
	   //APP令牌
	   CONST ADD_APP_TOKEN=10;
	   //交易密码
	   CONST ADD_TRADE_PASS=10;
	   //银行账户
	   CONST ADD_BANK_CARD=10;
	   //实名认证 银行护照
	   CONST ADD_PERSON_PASS=10;
	   
	   /*固定加分*/
	   //登录每天限制加一次
	   CONST ADD_ONE_LOGIN=2;
	   
	   /*交易加减分*/
	   //VIP0 级交易加分
	   CONST ADD_TRADE_LEVEL_ZERO=1;
	   //VIP1 级交易加分
	   CONST ADD_TRADE_LEVEL_ONE=1;
	   //VIP2 级交易加分
	   CONST ADD_TRADE_LEVEL_TWO=1;
	   //VIP3 级交易加分
	   CONST ADD_TRADE_LEVEL_THREE=1;
	   //VIP4 级交易加分
	   CONST ADD_TRADE_LEVEL_FOUR=2;
	   //VIP5 级交易加分
	   CONST ADD_TRADE_LEVEL_FIVE=2;
	   
	   //VIP0 级交易减分
	   CONST DECR_TRADE_LEVEL_ZERO=1.5;
	   //VIP1级交易减分
	   CONST DECR_TRADE_LEVEL_ONE=1.5;
	   //VIP2 级交易减分
	   CONST DECR_TRADE_LEVEL_TWO=1.5;
	   //VIP3级交易减分
	   CONST DECR_TRADE_LEVEL_THREE=1.5;
	   
	   //VIP4级交易减分
	   CONST DECR_TRADE_LEVEL_FOUR=3;
	   //VIP5级交易减分
	   CONST DECR_TRADE_LEVEL_FIVE=3;
	   
	   /*加分浮动率*/
	   //充值钱
	   CONST RATE_CHARGE_MONEY=0.01;
	   //充值币
	   CONST RATE_CHARGE_NUM=0.01;
	   //user表的vip用户资产
	   CONST RATE_USER_ASSETS=0.001;
}
