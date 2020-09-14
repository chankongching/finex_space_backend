<?php
namespace Back\Tools;
/**前台常量类-短信发送场景
 * @author 宋建强 2017年9月26日14:58
 */
class SceneCode
{     
	  // 注意redis 的key值的长度影响  
	  // 在一个32位的Redis服务器上，如果储存一百万个键，
	  // 每个值的长度是32-character，那么在使用6-character长度键名时，将会消耗大约96MB的空间，
	  // 但是如果使用12-character长度的键名时，空间消耗则会提升至111MB左右。随着键的增多，15%的额外开销将产生重大的影响
	 
	  //注册状态
	  const HOME_SMS_CODE_REGISTER='SMS_REGISTER';
	  //登录状态
	  const HOME_SMS_CODE_LOGIN='SMS_LOGIN';
	  //忘记密码
	  const HOME_SMS_CODE_FORGETPASS='SMS_FORGETPASS';
	  //忘记交易密码
	  const HOME_SMS_CODE_FORGETTRADEPASS='SMS_FORGETTRADEPASS';
	  //修改密码
	  const HOME_SMS_CODE_MODIFYPASS='SMS_MODIFYPASS';
	  //修改交易密码
	  const HOME_SMS_CODE_MODIFYTRADEPASS='SMS_MODIFYTRADEPASS';
     //繁体语言版本
     protected static $zhTwOm = ["+86","+852","+886"];

    //根据类型获取内容语言,先默认使用繁体
    protected static function getContenLangByOm($arrMsg,$type,$om){
        return in_array($om,self::$zhTwOm)?($arrMsg[$type]['zh-tw']):$arrMsg[$type]['other'];
    }

	  /**
	   * @author  建强  2018年3月29日16:54:51
	   * @method  推送语言模板匹配  -安全风险类
	   * @param   $userName string  用户名 
	   * @param   $om       string  +86 区号
	   * @param   $type     int     模板场景自定义类型    二维数组的key值
	   * return   string   &&&  标题内容的分割
	  */
	  public static function getPersonSafeInfoTemplate($userName,$om,$type)
	  {   
	  	    $date=date('Y-m-d H:i:s');
	  	    $arrMsg=[
	  	    		//1 修改登录密码
		  	    	1=>[
		  	    		"zh-cn"=>"登录密码被修改 &&&【BTCS】尊敬的用户，您的账号 {$userName} 于 {$date} 修改登录密码，若非本人操作，请注意账户安全。",
		  	    		"zh-tw"=>"登錄密碼被修改&&&【BTCS】尊敬的用戶，您的賬號 {$userName} 於 {$date} 修改登錄密碼，若非本人操作，請註意賬戶安全。",
		  	    		"other"=>"The password has been modified &&&【BTCS】Dear User,your account {$userName} modified password at {$date}, if it is not operated by yourself, please pay attention to the security of the account.",
		  	    	],
	  	    		//2 修改资金密码
	  	    		2=>[

	  	    		   "zh-cn"=>"资金密码被修改 &&&【BTCS】尊敬的用户，您的账号 {$userName} 于{$date} 修改资金密码，若非本人操作，请注意账户安全。",
	  	    		   "zh-tw"=>"資金密碼被修改 &&&【BTCS】尊敬的用戶，您的賬號 {$userName} 於{$date} 修改資金密碼，若非本人操作，請註意賬戶安全。",
	  	    		   "other"=>"Asset PIN has been modified &&&【BTCS】Dear User,your account{$userName} modified Asset PIN  at{$date}, if it is not operated by yourself, please pay attention to the security of the account.",
	  	    		],
	  	    		//3 重置登录密码
	  	    		3=>[
	  	    			"zh-cn"=>"登录密码被重置&&&【BTCS】尊敬的用户，您的账号 {$userName} 于 {$date} 重置账户登录密码，若非本人操作，请注意账户安全。",
	  	    			"zh-tw"=>"登錄密碼被重置 &&&【BTCS】尊敬的用戶，您的賬號 {$userName} 於 {$date} 重置賬戶登錄密碼，若非本人操作，請註意賬戶安全。",
	  	    			"other"=>"The password has been reset &&&【BTCS】Dear User,your account {$userName} reset  password  at {$date} ,if it is not operated by yourself, please pay attention to the security of the account.",
	  	    		],
	  	    		//4 登录密码锁定时（输入错误多次）
	  	    		4=>[
	  	    			"zh-cn"=>"登录权限被冻结 &&&【BTCS】尊敬的用户，您的账号 {$userName} 于 {$date} 因登录密码多次尝试失败被冻结登录权限(24小时后解封)，若非本人操作，请注意账户安全。",
	  	    			"zh-tw"=>"登錄權限被凍結 &&&【BTCS】尊敬的用戶，您的賬號 {$userName} 於{$date} 因登錄密碼多次嘗試失敗被凍結登錄權限(24小時後解封)，若非本人操作，請註意賬戶安全。",
	  	    			"other"=>"Login permission  is  frozen &&&【BTCS】Dear User,your account{$userName}has been frozen due to multiple login errors at {$date}( please wait for 24 hours to unfrozen account) if it is not operated by yourself, please pay attention to the security of the account.",
	  	    		],
	  	    		//5 资金密码锁定时（输入错误多次）
	  	    		5=>[
	  	    			"zh-cn"=>"交易权限被冻结 &&&【BTCS】尊敬的用户，您的账号 {$userName} 于 {$date} 因资金密码多次尝试失败被冻结交易权限，若非本人操作，请注意账户安全。",
	  	    			"zh-tw"=>"交易權限被凍結 &&&【BTCS】尊敬的用戶，您的賬號 {$userName} 於{$date} 因資金密碼多次嘗試失敗被凍結交易權限，若非本人操作，請註意賬戶安全。",
	  	    			"other"=>"Transaction permissions are frozen &&&【BTCS】Dear User,your account {$userName} has been frozen  the Transaction permissions due to multiple Asset PIN errors at {$date}( please contact customer service)if it is not operated by yourself, please pay attention to the security of the account.",
	  	    		],
	  	    		//6 手机令牌解绑
	  	    		6=>[
	  	    			"zh-cn"=>"手机令牌已解绑 &&&【BTCS】尊敬的用户，您的账号 {$userName} 于{$date} 解绑安全令牌，若非本人操作，请注意账户安全。",
	  	    			"zh-tw"=>"手機令牌已解綁  &&&【BTCS】尊敬的用戶，您的賬號 {$userName} 於 {$date} 解綁安全令牌，若非本人操作，請註意賬戶安全。",
	  	    			"other"=>"BS PASS has been unbound &&&【BTCS】Dear User,your account {$userName}Unbind BS PASS at{$date},if it is not operated by yourself,please pay attention to the security of the account.",
	  	    		],
	  	    		//7 动态口令
	  	    		7=>[
	  	    		    "zh-cn"=>"登录权限被冻结 &&&【BTCS】尊敬的用户，您的账号 {$userName} 于 {$date} 因动态口令多次尝试失败被冻结登录权限(24小时后解封)，若非本人操作，请注意账户安全。",
	  	    			"zh-tw"=>"登錄權限被凍結 &&&【BTCS】尊敬的用戶，您的賬號 {$userName} 於 {$date} 因動態口令多次嘗試失敗被凍結登錄權限(24小時後解封)，若非本人操作,請註意賬戶安全。",
	  	    			"other"=>"Login permission has been frozen &&&【BTCS】Dear User,your account{$userName} has been frozened  login authority due to multiple BS PASS errors at{$date}( unsealed after 24 hours),if it is not operated by yourself,please pay attention to the security of the account.",
	  	    		],
                    //7 实名认证通过
                    8=>[
                        "zh-cn"=>"实名认证通过 &&&【BTCS】尊敬的用户，您的账号 {$userName} 于 {$date} 通过实名认证。",
                        "zh-tw"=>"實名認證通過 &&&【BTCS】尊敬的用戶，您的賬號 {$userName} 於{$date} 通過實名認證。",
                        "other"=>"Real name authentication passed &&&【BTCS】Respected users, your account number {$userName} at{$date} Through real name authentication.",
                    ],
	  	    ];
	  	    return self::getContenLangByOm($arrMsg,$type,$om);
	   }
	  /**
	   * @method  推送语言模板匹配  财务类
	   * @param   $om  string 
	   * @param   $type int    1充值    2提币  
	   * @param   $currecnyName  string  例如BTC大写 
	   * @param   $num int   币数量
	   * return   string 
	   */
	  public static function getFinanceMoneyTemplate($userName,$type,$om,$currecnyName,$num)
	  {    
	  	    $date=date('Y-m-d H:i:s');
		  	$arrMsg=[
                     //充币		  			 
			  		 1=>[
			  		 	"zh-cn"=>"充值已到账 &&&【BTCS】尊敬的用户，您的账号 {$userName} 于 {$date} 充值 币种{$currecnyName}，个数 {$num} 个，已成功到账，请注意查收。",
			  			"zh-tw"=>"充值已到賬 &&&【BTCS】尊敬的用戶，您的賬號 {$userName} 於{$date} 充值 幣種 {$currecnyName}，個數 {$num} 個，已成功到賬，請註意查收。",
			  			"other"=>"Deposit received. &&&【BTCS】Dear customer, please check your account {$userName} On {$date}, Coin Type: {$currecnyName}, Quantity: {$num}, has been successfully transferred to your wallet. ",
			  		 ],
		  			  
		  			 //提币
		  			 2=>[
		  				"zh-cn"=>"提现已到账 &&&【BTCS】尊敬的用户，您的账号 {$userName} 于 {$date} 提现 币种 {$currecnyName}，个数  {$num} 个，已成功到账，请注意查收。",
		  				"zh-tw"=>"提現已到賬 &&&【BTCS】尊敬的用戶，您的賬號 {$userName} 於{$date} 提現 幣種 {$currecnyName}，個數  {$num} 個，已成功到賬，請註意查收。",
		  				"other"=>"Token withdrawn effected. &&&【BTCS】Dear customer, please check your account {$userName} On {$date}, Coin Type: {$currecnyName}, Quantity: {$num}, has been successfully transferred to your wallet. ",
		  			 ],
		  	];
          return self::getContenLangByOm($arrMsg,$type,$om);
	  }
	  /**
	   * @method p2p交易模式下的订单推送模板 
	   * @param  $type 获取某个场景下的模板类型
	   * @param  $om   user表om值
	   * @param  array $orderInfo （订单等信息）
	  */
	  public static function getP2PTradeTemplate($type,$om,$orderInfo)
	  {      
	  	    $orderNum=$orderInfo['orderNum'];          //订单号  注意是买家还是卖家看
	  	    $currencyName=$orderInfo['currencyName'];  //币种名称 例如BTC大写
	  	    $num=$orderInfo['num'];   //单价
            $getPriceAndMoney=self::getPriceToRateMoney($om,$orderInfo['num'],$orderInfo['rate_total_money']);//金额
	  	    $price=$getPriceAndMoney[0];   //单价
	  	    $total=$getPriceAndMoney[1];   //总价

           $arrMsg =[
		  	  		    //1 买家确认付款后的操作  对卖家进行通知
			  	  	 	1=>[
			  	  		   "zh-cn"=>"买家已付款 &&&【BTCS】您的卖出订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量:  {$num}个，总价 {$total}，对方已确认付款，请您及时查收并尽快进行确认收款操作。",
			  	  		   "zh-tw"=>"買家已付款 &&&【BTCS】您的賣出訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量: {$num}個，總價 {$total}，對方已確認付款，請您及時查收並盡快進行確認收款操作。",
			  	  		   "other"=>"Payment completed &&&【BTCS】Your Sell Form:{$orderNum}, Coin Type: {$currencyName},Price:{$price},Quantity:{$num},Total: {$total},The buyer has confirmed the payment,please Timely check and confirm as soon as possible.",
			  	  		],
		  	  		    //2买家超时未付款，对卖家进行通知
		  	  		    2=>[
		  	  		       "zh-cn"=>"您的卖出订单已自动取消 &&&【BTCS】您的卖出订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量: {$num}个，总价 {$total}，因对方超时未付款，该订单已自动取消。",
		  	  		       "zh-tw"=>"您的賣出訂單已自動取消 &&&【BTCS】 您的賣出訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量: {$num}個，總價 {$total}，因對方超時未付款，該訂單已自動取消。",
		  	  		       "other"=>"Your sell order was cancelled. &&& Your sell order: {$orderNum}, Coin Type: {$currencyName}, Price: {$price}, Quantity: {$num}, Total: {$total}, was cancelled due to buyer's failure to pay.",
		  	  		    ],
		  	  		   //3   卖家确认收款操作后，对买家进行通知
		  	  		   3=>[
		  	  		   	   "zh-cn"=>"卖家已收款 &&&【BTCS】您的买入订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量: {$num}个，总价 {$total}，对方已确认收款，货币已到账，请注意查收。",
		  	  		   	   "zh-tw"=>"賣家已收款 &&&【BTCS】您的買入訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量: {$num}個，總價 {$total}，對方已確認收款，貨幣已到賬，請註意查收。",
		  	  		   	   "other"=>"The seller has received the payment &&&【BTCS】Your Buy Order:{$orderNum}, Coin Type: {$currencyName}, Price:{$price}, Quantity: {$num}, Total: {$total}, The seller has confirmed receipt, the coin has arrived, please check.",
		  	  		   ],
		  	  		   //4 P2P24小时自动撤销订单 卖出
		  	  		   4=>[
			  	  		   "zh-cn"=>"订单已自动撤销&&&【BTCS】您的卖出订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量: {$num}个，总价 {$total}，已超过24小时未成交，系统已将该订单自动撤销。",
			  	  		   "zh-tw"=>"訂單已自動撤銷&&&【BTCS】您的賣出訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量: {$num}個，總價 {$total}，已超過24小時未成交，系統已將該訂單自動撤銷。",
			  	  		   "other"=>"Your order was automatically cancelled.&&&Your sell order: {$orderNum}, Coin Type: {$currencyName}, Price: {$price}, Quantity: {$num}, Total: {$total}, was cancelled by the system automatically as 24 hours has passed and the order was not completed.",
		  	  		   ],
		  	  		    //5 P2P 购买15分钟内没有打款，则推送信息给购买人提醒打款
		  	  		   5=>[
			  	  		   "zh-cn"=>"付款提醒&&&【BTCS】您的买入订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量: {$num}个，总价{$total}，请尽快付款。",
			  	  		   "zh-tw"=>"付款提醒&&&【BTCS】您的買入訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量: {$num}個，總價{$total}，請盡快付款。",
			  	  		   "other"=>"Payment reminder.&&&Your buy order: {$orderNum}, Coin Type: {$currencyName}, Price: {$price}, Quantity: {$num}, Total: {$total}, Please arrange for payment asap.",
		  	  		   ],
		  	  		   
		  	  		   //====================
		  	  		   //买家付款超时 待处理
		  	  		   6=>[
		  	  		       "zh-cn"=>"订单状态提醒&&&【BTCS】您的买入订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量: {$num}个，总价{$total}，付款超时，订单状态改为待处理，客服即将介入处理，请保持电话畅通。",
		  	  		       "zh-tw"=>"訂單狀態提醒&&&【BTCS】您的買入訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量: {$num}個，總價{$total}，付款超時，訂單狀態改為待處理，客服即將介入處理，請保持電話暢通。",
		  	  		       "other"=>"Order status reminder: &&&【BTCS】The payment time limit of your buy order: {$orderNum}, Coin Type: {$currencyName}, Price: {$price}, Quantity: {$num}, Total: {$total} has passed. The order status is changed to 'Problem' and our customer service will handle. Please make sure your mobile phone is connected.",
		  	  		   ],
		  	  		   //由于买家付款超时 提醒卖家
		  	  		   7=>[
	  	  		           "zh-cn"=>"订单状态提醒&&&【BTCS】您的卖出订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量: {$num}个，总价{$total}，买家付款超时，订单状态改为待处理，客服即将介入处理，请保持电话畅通。",
	  	  		           "zh-tw"=>"訂單狀態提醒&&&【BTCS】您的賣出訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量: {$num}個，總價{$total}，買家付款超時，訂單狀態改為待處理，客服即將介入處理，請保持電話暢通。",
	  	  		           "other"=>"Order status reminder: &&&【BTCS】The payment receipt time limit of your sell order: {$orderNum}, Coin Type: {$currencyName}, Price: {$price}, Quantity: {$num}, Total: {$total} has passed. The order status is changed to 'Problem' and our customer service will handle. Please make sure your mobile phone is connected.",
		  	  		   ],
		  	  		   //======================
		  	  		   //卖家收款超时 待处理
		  	  		   8=>[
    		  	  		   "zh-cn"=>"订单状态提醒&&&【BTCS】您的买入订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量: {$num}个，总价{$total}，  卖家收款超时，订单状态改为待处理，客服即将介入处理，请保持电话畅通。",
    		  	  		   "zh-tw"=>"訂單狀態提醒&&&【BTCS】您的買入訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量: {$num}個，總價{$total}，賣家收款超時，訂單狀態改為待處理，客服即將介入處理，請保持電話暢通。",
    		  	  		   "other"=>"Order status reminder: &&&【BTCS】The buyer's payment time limit of your sell order: {$orderNum}, Coin Type: {$currencyName}, Price: {$price}, Quantity: {$num}, Total: {$total} has passed. The order status is changed to 'Problem' and our customer service will handle. Please make sure your mobile phone is connected.",
		  	  		   ],
		  	  		   //由于买家付款超时 提醒卖家
		  	  		   9=>[
	  	  		           "zh-cn"=>"订单状态提醒&&&【BTCS】您的卖出订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量: {$num}个，总价{$total}，收款超时，订单状态改为待处理，客服即将介入处理，请保持电话畅通。",
	  	  		           "zh-tw"=>"訂單狀態提醒&&&【BTCS】您的賣出訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量: {$num}個，總價{$total}，收款超時，訂單狀態改為待處理，客服即將介入處理，請保持電話暢通。",
	  	  		           "other"=>"Order status reminder: &&&【BTCS】The seller's payment receipt time limited of your buy order: {$orderNum}, Coin Type: {$currencyName}, Price: {$price}, Quantity: {$num}, Total: {$total} has passed. The order status is changed to 'Problem' and our customer service will handle. Please make sure your mobile phone is connected.",
		  	  		  ],
		  	  ];
          return self::getContenLangByOm($arrMsg,$type,$om);
	  }

	  public static function getAreaTotal($om,$num,$price)
	  {
	  	   $res=M('Config')->select();
	  	   $rate=array_column($res, 'value','key');
	  	   $priceRate=[
	  	   		"+86"=>$rate['RMB_HUILV'],
	  	   		"+886"=>$rate['TW_HUILV'],
	  	   		"+852"=>$rate['HK_HUILV'],
	  	   ];

	  	   $symbolMoney=[
	  	   		"+86"=>" CNY",
	  	   		"+886"=>" TWD",
	  	   		"+852"=>" HKD",
	  	   ];
	  	   $priceRateDollar=$priceRate[$om]?$priceRate[$om]:1;
	  	   $symbol=$symbolMoney[$om]?$symbolMoney[$om]:'USD';
	  	   $price=big_digital_mul($price, $priceRateDollar,2);
	  	   $total=big_digital_mul($num, $price,2);
	  	   return [
	  	   		     $price.$symbol,
	  	   		     $total.$symbol
	  	          ];
	  }
	 /**
 * @method C2C交易模式下的订单推送模板
 * @param  $type 获取某个场景下的模板类型
 * @param  $om   user表om值
 * @param  array $orderInfo （订单等信息）
 */
    public static function getC2CTradeTemplate($type,$om,$orderInfo)
    {
        $orderNum=$orderInfo['orderNum'];          //订单号  注意是买家还是卖家看
        $currencyName=$orderInfo['currencyName'];  //币种名称 例如BTC大写
        $num=$orderInfo['num'];   //个数
        if(!empty($orderInfo['rate_total_money'])){
            //由参考总额获取单价
            $getPriceAndMoney=self::getPriceToRateMoney($om,$orderInfo['num'],$orderInfo['rate_total_money']);
        }else{
            //由单价获取参考总额
            $getPriceAndMoney=self::getAreaTotal($om,$orderInfo['num'],$orderInfo['price']);
        }
        $price=$getPriceAndMoney[0];   //单价
        $total=$getPriceAndMoney[1];   //总价

        $arrMsg=[
            //1.买家确认付款操作后，对卖家进行通知
            1=>[
                "zh-cn"=>"买家已付款  &&&【BTCS】您的卖出订单：{$orderNum}，币种{$currencyName}，   单价{$price}，数量: {$num}个，总价{$total}，对方已确认付款，请您及时查收并尽快进行确认收款操作。",
                "zh-tw"=>"買家已付款 &&&【BTCS】您的賣出訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量: {$num}個，總價 {$total}，對方已確認付款，請您及時查收並盡快進行確認收款操作。",
                "other"=>"The buyer has paid &&&【BTCS】Your Sell Form:{$orderNum}, Coin Type: {$currencyName}, Price: {$price}, Quantity: {$num}, Total: {$total}, The buyer has confirmed the payment, please Timely check and confirm as soon as possible.",
            ],
            //2.买家超时未付款，对卖家进行通知
            2=>[
                "zh-cn"=>"买家付款超时&&&【BTCS】您的卖出订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量:  {$num}个，总价 {$total}，因对方超时未付款，该订单已自动取消。",
                "zh-tw"=>"買家付款超時&&&【BTCS】您的賣出訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量:  {$num}個，總價 {$total}，因對方超時未付款，該訂單已自動取消。",
                "other"=>"Payment Expired &&&【BTCS】Your Sell Form:{$orderNum}, Coin Type: {$currencyName}, Price: {$price}, Quantity: {$num}, Total: {$total}, the order will be cancelled automatically due to Payment Expired.",
            ],
            //3.卖家确认收款操作后，对买家进行通知
            3=>[
                "zh-cn"=>"卖家已收款&&&【BTCS】您的买入订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量: {$num}个，总价{$total}，对方已确认收款，货币已到账，请注意查收",
                "zh-tw"=>"賣家已收款&&&【BTCS】您的買入訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量: {$num}個，總價 {$total}，對方已確認收款，貨幣已到賬，請註意查收",
                "other"=>"The seller has received the payment &&&【BTCS】Your Buy Order:{$orderNum}, Coin Type: {$currencyName}, Price: {$price}, Quantity:{$num}，Total: {$total}, The  seller  has  confirmed receipt, the coin has arrived, please check.",
            ],
            //卖家超时未收款，对买家进行通知 (执行自动放币)
            4=>[
                "zh-cn"=>"卖家收款超时&&&【BTCS】您的买入订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量: {$num}个，总价{$total}，因对方超时未收款，系统已自动放币，请注意查收。",
                "zh-tw"=>"賣家收款超時&&&【BTCS】您的買入訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量:{$num}個，總價 {$total}，因對方超時未收款，系統已自動放幣，請註意查收。",
                "other"=>"Receipt Expired  &&&【BTCS】Your Buy Order:{$orderNum},Coin Type: {$currencyName}, Price: {$price}, Quantity:{$num}, Total: {$total}, system will automatically put coins due to Receipt Expired, please check.",
            ],
            //买家付款
            5=>[
                "zh-cn"=>"付款提醒&&&【BTCS】您的买入订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量: {$num}个，总价{$total}，对方已卖出，请尽快付款。",
                "zh-tw"=>"付款提醒&&&【BTCS】您的買入訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量:{$num}個，總價 {$total}，對方已賣出，請盡快付款。",
                "other"=>"Payment reminder.&&&【BTCS】Your buy order: {$orderNum},Coin Type: {$currencyName},Price: {$price}, Quantity: {$num}, Total: {$total}, which the seller already effected transfer. Please arrange for payment asap.",
            ],
            //c2c交易48h自动撤销订单 (卖出)
            6=>[
                "zh-cn"=>"订单已自动撤销&&&【BTCS】您的卖出订单：{$orderNum}，币种{$currencyName}，单价{$price}， 数量: {$num}个，总价 {$total}，已超过48小时未成交，系统已将该订单自动撤销。",
                "zh-tw"=>"訂單已自動撤銷&&&【BTCS】您的賣出訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量: {$num}個，總價 {$total}，已超過48小時未成交，系統已將該訂單自動撤銷。",
                "other"=>"Your sell order:{$orderNum},Coin Type: {$currencyName}, Price: {$price}, Quantity: {$num}, Total: {$total},system has automatically cancelled the order due to without a deal exceeded 48 hours.",
            ],
            //c2c交易48h自动撤销订单 (买入)
            7=>[
                "zh-cn"=>"订单已自动撤销&&&【BTCS】您的买入订单：{$orderNum}，币种{$currencyName}，单价{$price}，数量: {$num}个，总价 {$total}，已超过48小时未成交，系统已将该订单自动撤销。",
                "zh-tw"=>"訂單已自動撤銷&&&【BTCS】您的買入訂單：{$orderNum}，幣種{$currencyName}，單價{$price}，數量: {$num}個，總價 {$total}，已超過48小時未成交，系統已將該訂單自動撤銷。",
                "other"=>"The order has been automatically cancelled&&&【BTCS】Your buy order:{$orderNum},Coin Type: {$currencyName}, Price: {$price}, Quantity: {$num}, Total: {$total}, system has automatically cancelled the order due to without a deal exceeded 48 hours.",
            ],
            //C2C交易  订单剩余总额不足以生成订单小单 调整推送格式  2019年5月27日
            8=>[
                "zh-cn"=>"订单已自动撤销&&&【BTCS】您的买入/卖出{$currencyName}的交易单，因订单剩余总额不足以生成订单，系统已将该订单自动撤销。",
                "zh-tw"=>"訂單已自動撤銷&&&【BTCS】您的買入/賣出{$currencyName}的交易單，因訂單剩余總額不足以生成訂單，系統已將該訂單自動撤銷。",
                "other"=>"The order has been automatically cancelled &&&【BTCS】Your buy/色ll order, Coin Type: {$currencyName}, system has automatically cancelled the order due to remaining quantity is insufficient to create a order.",
            ],
        ];
        return self::getContenLangByOm($arrMsg,$type,$om);
    }

    //根据参考总额，获取参考单价
    public static function getPriceToRateMoney($om,$num,$rateTotalMoney)
    {
        $symbolMoney=[
            "+86"=>" CNY",
            "+886"=>" TWD",
            "+852"=>" HKD",
        ];
        $symbol=$symbolMoney[$om]?$symbolMoney[$om]:'USD';
        $price=big_digital_div($rateTotalMoney, $num,2);
        return [
            $price.$symbol,
            $rateTotalMoney.$symbol
        ];
    }
}
