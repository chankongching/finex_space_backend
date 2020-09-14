<?php

if (!function_exists('big_digital_div')) {
	/**
	 * 大数相除
	 * 将参数整理成整数相除
	 * @param $first_num  分子
	 * @param $last_num  分母
	 * @param int $decimal_len 保留小数点位数
	 * @return string
	 * 刘富国
	 * 20180104
	 */
    function big_digital_div($first_num, $last_num, $decimal_len = 8){
        $arr_first_num = explode('.', $first_num);
        $arr_last_num = explode('.', $last_num);
        $int_first_num = $arr_first_num[0].$arr_first_num[1];
        $int_last_num = ($arr_last_num[0].$arr_last_num[1]);
        //分母的小数部分大于分子，要将分子往右移位
        if(strlen($arr_last_num[1]) > strlen($arr_first_num[1])){
            $item_num = (strlen($arr_last_num[1]) - strlen($arr_first_num[1]));
            $item_len = '1';
            for($i = 0;$i<$item_num;$i++) {
                $item_len  = $item_len.'0';
            }
            $int_first_num =  big_digital_mul($int_first_num,$item_len);
        }elseif(strlen($arr_last_num[1]) < strlen($arr_first_num[1])){
            $item_num = (strlen($arr_first_num[1]) - strlen($arr_last_num[1]));
            $item_len = '1';
            for($i = 0;$i<$item_num;$i++) {
                $item_len  = $item_len.'0';
            }
            $int_last_num =  big_digital_mul($int_last_num,$item_len);
        }
        $ret_div = bcdiv($int_first_num, $int_last_num, $decimal_len);
        return $ret_div;
    }
}

if (!function_exists('big_digital_mul')) {
	/**
	 * 大数相乘
	 * 将参数整理成整数相乘，然后分别获取整数位和小数位相加
	 * @param $first_num
	 * @param $last_num
	 * @param int $decimal_len  保留小数点位数
	 * @return string
	 * 刘富国
	 * 20180104
	 */

    function big_digital_mul($first_num,$last_num,$decimal_len=8){
        $arr_first_num = explode('.',$first_num);
        $arr_last_num  = explode('.',$last_num);
        $int_first_num =  $arr_first_num[0].$arr_first_num[1];
        $int_last_num =  $arr_last_num[0].$arr_last_num[1];
        $mul_decimal_len = strlen($arr_first_num[1])+ strlen($arr_last_num[1]); //乘积小数位数
        $ret_mul = bcmul($int_first_num,$int_last_num); //乘积结果
        $ret_first_value = 0 ;
        $ret_last_value = 0;
        //整数部分
        if(strlen($ret_mul) > $mul_decimal_len){
            $ret_first_value = substr($ret_mul,0,strlen($ret_mul)-$mul_decimal_len);
        }
        //小数部分
        if($mul_decimal_len > 0 ){
            //如果乘积小数位数小于乘积结果，用截取方式获取小数部分，否则用除的方式获取
            if(strlen($ret_mul) > $mul_decimal_len){
                $ret_last_value = substr($ret_mul,strlen($ret_mul)-$mul_decimal_len);
                $ret_last_value = '0.'.$ret_last_value;
            }else{
                $item_len = '1';
                for($i = 0;$i<$mul_decimal_len;$i++) {
                    $item_len  = $item_len.'0';
                }
                $ret_last_value = bcdiv($ret_mul,$item_len,$decimal_len);
            }
        }
        $ret = bcadd($ret_first_value,$ret_last_value,$decimal_len);
        return $ret;
    }
}


if (!function_exists('check_watchword')) {
    /**
     * 校驗手機登入口令
     * 劉富國
     * 2017-10-19
     * @param $uid  用戶ID
     * @param $check_watch_code  手機登入口令
     * @return bool
     */
    function check_watchword($uid,$check_watchword){
        return true;
        $uid = $uid*1;
        $check_watchword = trim($check_watchword);
        if($uid<1 or empty($check_watchword)) return false;
        $user_token_model = new Common\Model\UserTokenModel();
        $ret = $user_token_model->checkWatchword($uid,$check_watchword);
        return $ret;
    }
}

if (!function_exists('formatBankType')) {
    /**
     * 格式化银行
     * @param unknown $num
     */
    function formatBankType($num)
    {
    	switch ($num) {
    		case 1 :
    			$arr = '中国工商银行';
    			break;
    		case 2 :
    			$arr = '中国农业银行';
    			break;
    		case 3 :
    			$arr = '中国建设银行';
    			break;
    		case 4 :
    			$arr = '中国银行';
    			break;
    		case 5 :
    			$arr = '香港汇丰银行';
    			break;
    		case 6 :
    			$arr = '中国银行（香港）';
    			break;
    		case 7 :
    			$arr = '香港东亚银行';
    			break;
    		case 8 :
    			$arr = '恒生银行';
    			break;
    		case 9 :
    			$arr = '台湾银行';
    			break;
    		case 10 :
    			$arr = '土地银行';
    			break;
    		case 11 :
    			$arr = '合作金库商业银行';
    			break;
    		case 12 :
    			$arr = '第一商业银行';
    			break;
                case 13 :
    			$arr = '华南商业银行';
    			break;
                case 14 :
    			$arr = '彰化商业银行';
    			break;
                case 15 :
    			$arr = '上海商业储蓄银行';
    			break;
                case 16 :
    			$arr = '台北富邦商业银行';
    			break;
                case 17 :
    			$arr = '国泰世华商业银行';
    			break;
                case 18 :
    			$arr = '兆豐國際商業銀行';
    			break;
                case 19 :
    			$arr = '高雄银行';
    			break;
                case 20 :
    			$arr = '中国信托商业银行';
    			break;
                case 21 :
    			$arr = '花旗（台灣）商業銀行';
    			break;
                case 22 :
    			$arr = '澳盛（台湾）商业银行';
    			break;
                case 23 :
    			$arr = '王道商业银行';
    			break;
                case 24 :
    			$arr = '台湾中小企业银行';
    			break;
                case 25 :
    			$arr = '渣打國際商業銀行';
    			break;
                case 26 :
    			$arr = '台中商業銀行';
    			break;
                case 27 :
    			$arr = '京城商業銀行';
    			break;
                case 28 :
    			$arr = '匯豐（台灣）商業銀行';
    			break;
                case 29 :
    			$arr = '瑞興商業銀行';
    			break;
                case 30 :
    			$arr = '華泰商業銀行';
    			break;
                case 31 :
    			$arr = '臺灣新光商業銀行';
    			break;
                case 32 :
    			$arr = '陽信商業銀行';
    			break;
                case 33 :
    			$arr = '板信商业银行';
    			break;
                case 34 :
    			$arr = '三信商业银行';
    			break;
                case 35 :
    			$arr = '联邦商业银行';
    			break;
                case 36 :
    			$arr = '远东国际商业银行';
    			break;
                case 37 :
    			$arr = '元大商业银行';
    			break;
                case 38 :
    			$arr = '永丰商业银行';
    			break;
                case 39 :
    			$arr = '玉山商业银行';
    			break;
                case 40 :
    			$arr = '凯基商业银行';
    			break;
                case 41 :
    			$arr = '星展（台湾）商业银行';
    			break;
                case 42 :
    			$arr = '台新国际商业银行';
    			break;
                case 43 :
    			$arr = '日盛国际商业银行';
    			break;
                case 44 :
    			$arr = '安泰商业银行';
    			break;    
    	}
    	return $arr ? $arr : false;
    }
}

if (!function_exists('p')) {
    /**格式化打印函数
     * 刘富国
     * * 2017-10-19
     */
    function p($var)
    {
        echo "<br><pre>";
        if (empty($var)) {
            var_dump($var);
        } else {
            if (!is_array($var)) {
                echo($var);
            } else {
                print_r($var);
            }
        }
        echo "</pre><br>";
    }
}

if( !function_exists('pp') ){
    function pp($arr){
        echo '<pre>';
        print_r($arr);
        die;
    }
}
/**
 * 获取用户userid
 * @author lirunqing 2017-09-30T11:23:19+0800
 * @return int
 */
function getUserId(){
    $sessionObj = \Common\Api\RedisIndex::getInstance(); 
    $loginInfo  = $sessionObj->getSessionValue('LOGIN_INFO');
    $userid     = !empty($loginInfo['USER_KEY_ID']) ? $loginInfo['USER_KEY_ID'] : 0;
	return $userid;
}


/**
 * 通过用户名获取用户id
 * author zhanghanwen 2017年10月17日11:12:57
 * return int
 **/
function getUserIdForUserName( $username, $addition = false ){
    $whereArr['username'] = $username;
    if($addition){
        $whereArr+=$addition;
    }
    $data = M('user')->where(array('username'=>$username))->field('uid')->find();
    return isset($data['uid'])?$data['uid']:0;
}

/**
 * @param unknown $overtime_num
 * @param unknown $overtime_time
 * @return number 
 * 渲染模板
 */
function credibilityTtlTemplate($overtime_num,$overtime_time,$uid)
{
    $time=time();
    $arr=[
            '1'=>24*3600,
            '2'=>7*24*3600,
            '3'=>30*24*3600,
    ];
    $overTime=$arr[$overtime_num];
    if($overtime_num>3)
    {
        $overTime=$arr[3];
    }
    if($overtime_time+$overTime<$time)
    {
        return '<span  disabled="true"   class="btn btn-danger btn-xs">無需</span>';
    }
    return  "<span  data-uid='{$uid}'  class='btn btn-success btn-xs solveUser'>解封</span>";
}


/**
 * @param unknown $overtime_num
 * @param unknown $overtime_time
 * @return number
 */
function credibilityTtl($overtime_num,$overtime_time,$type='c2c')
{
    $time=time();
    $pass=$time-$overtime_time;  //过了多久
    $arr=[
         '1'=>24*3600,
         '2'=>7*24*3600,
         '3'=>30*24*3600,
    ];
    
    $overTime=$arr[$overtime_num];
    if($overtime_num>3)
    {
        $overTime=$arr[3];
    }
    
    if($overtime_time+$overTime<$time)
    {
         return '-';
    }
    $secs=$overTime-$pass;
    return  secsToStr($secs);
}



/**
 * 
 * @param unknown $overtime_num
 * @param unknown $overtime_time
 * @param unknown $uid
 * @return string
 */
function credibilityTtlTemplateCC($overtime_num,$overtime_time,$uid)
{
    $time=time();
    if($overtime_num<3)
    {
    	return '<span  disabled="true"   class="btn btn-danger btn-xs">無需</span>';
    }
    
    $overTime=24*3600;
    if($overtime_time+$overTime<$time)
    {
    	return '<span  disabled="true"   class="btn btn-danger btn-xs">無需</span>';
    }
    return  "<span  data-uid='{$uid}'  class='btn btn-success btn-xs solveUser'>解封</span>";
} 


/***
 * @method 建强 格式化时间参数
 * @param unknown $secs
 */
function secsToStr($secs)
{  
	$r='';
	if($secs>=86400)
	{
		$days=floor($secs/86400);
		$secs=$secs%86400;
		$r=$days.'day';
	}
	if($secs>=3600)
	{
		$hours=floor($secs/3600);
		$secs=$secs%3600;
		$r.=$hours.'h';
	}
	if($secs>=60)
	{
		$minutes=floor($secs/60);
		$secs=$secs%60;
		$r.=$minutes.'min';
	}
    return $r;
}

/**
 * C2C模式下时间剩余解封
 * @param unknown $overtime_num
 * @param unknown $overtime_time
 * @return string
 */
function credibilityTtlC2C($overtime_num,$overtime_time)
{
	$time=time();
	$pass=$time-$overtime_time;  //过了多久
	if($overtime_num<3)
	{
		return '-';
	}
	$overTime=24*3600;
	if($overtime_time+$overTime<$time)
	{
       return '-';
	}
	$secs=$overTime-$pass;
	return secsToStr($secs);
}
/**
 *  给分页传参数
 * @param Object  $Page 分页对象 
 * @param array $parameter 传参数组
 */
function setPageParameter($Page,$parameter)
{
    foreach ($parameter as $k=> $v)
    {
        if (isset($v))
        {
            $Page->parameter[$k]=$v;
        }
    }
}

/**密码加密
 * @author 宋建强
 * @param  string $password
 * @return string  
 */
function passwordEncryption($password)
{
	return md5(md5($password).C('PASSWORDSUFFIX'));
}
/**加密密码验证 $pwd   
 * @param unknown $pwd
 * @param unknown $password
 * @return boolean
 */
function passwordVerification($pwd,$password)
{
		
	if(passwordEncryption($pwd)==$password)
	{
		return true;
	}
	return false;
}

/**
 * 发送邮件
 * @author lirunqing 2017-10-19T14:20:56+0800
 * @param  array  $fromData 发送人邮件信息
 *         string $fromData['emailHost'] 必传 企业邮局域名
 *         string $fromData['emailPassWord'] 必传 邮局密码
 *         string $fromData['emailUserName'] 必传 邮件发送者email地址
 *         string $fromData['formName'] 必传 邮件发送者名称
 * @param  string $email  收件人邮箱
 * @param  string $title  邮件标题
 * @param  string $body   邮件内容
 * @return bool
 */
function sendEmail($fromData=array(), $email, $title, $body){

    $emailHost     = $fromData['emailHost'];
    $emailPassWord = $fromData['emailPassWord'];
    $emailUserName = $fromData['emailUserName'];
    $formName      = $fromData['formName'];
    
    
    
    /*以下内容为发送邮件  update by 建强*/
    require_once(APP_PATH.'Common/PHPMailer/class.phpmailer.php'); //下载的文件必须放在该文件所在目录
    $mail=new PHPMailer();                                    
    //$mail->SMTPDebug = 2;   关闭debug调式模式
    //配置邮件选项 免除ssl证书效验
    $mail->SMTPOptions = array(
    		'ssl' => array(
    				'verify_peer' => false,
    				'verify_peer_name' => false,
    				'allow_self_signed' => true
    		)
    );
    
    $mail->IsSMTP();//使用SMTP方式发送 设置设置邮件的字符编码，若不指定，则为'UTF-8
    $mail->Host=$emailHost;//'smtp.qq.com';//您的企业邮局域名
    $mail->SMTPAuth=true;//启用SMTP验证功能   设置用户名和密码。
    $mail->Timeout=60;
    $mail->Username=$emailUserName;//'mail@koumang.com'//邮局用户名(请填写完整的email地址)
    $mail->Password=$emailPassWord;//'xiaowei7758258'//邮局密码
    $mail->From=$emailUserName;//'mail@koumang.com'//邮件发送者email地址

    $mail->FromName=$formName;//邮件发送者名称
    $mail->AddAddress($email);// 收件人邮箱，收件人姓名
    $mail->IsHTML(true); // set email format to HTML //是否使用HTML格式
    
    $mail->Subject="=?UTF-8?B?".base64_encode($title)."?=";
    $mail->Body=$body; //邮件内容
    $mail->AltBody = "这是一封HTML格式的电子邮件。"; //附加信息，可以省略
    $res=$mail->Send();  //发送邮件
    
    return  $res;  //bool
}

/*
 * 获取分表  
 * author 宋建强
 * Date  2017年8月10日
 * @parame string  table
 * @parame int  
 * @parame uid  
 * return string
 */
function  getTbl($table,$uid,$mod=10)
{
     return  $table.$uid%$mod;
}

/*
 * 注册信息正则验证字段
 * author 宋建强
 * Date  2017年8月10日
 * @parame string  value
 * @parame string  reg
 * return  bool
 */
function regex($value,$rule)
{
	$validate = [
			'email'     =>  '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
			'phone'		=>  '#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#',
			'password'  =>  '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,18}$/',
			'interphone'=>  '/^[0-9]{6,11}$/',
			// 'username'  =>  '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,18}$/',
            'username'  =>  '/^[a-zA-Z][A-Za-z0-9]{5,17}$/',      
			'double'    =>  '/^[-\+]?\d+(\.\d+)?$/',
		   	'bankcard'  =>  '/^(\d{16,19})$/',
			'card'      =>  '/^[A-Za-z0-9]{4,20}$/',
			'qq_num'    =>  '/^[0-9]{4,15}/',
            'addurl'    =>  '/^[a-z\d]*$/i',
			'qq'        =>  '/^[1-9][0-9]{4,15}$/',
            'passport'  =>  '/^[a-zA-Z0-9]{6,12}$/'
	];
     $rule=$validate[$rule];
     $sb  =preg_match($rule,$value);
     if($sb === 1)
     {
         return 1;
     }
     else
     {
         return 0;
     }
}
/**
 * 检验短信验证码   redis   时间120s  宋建强 21:05 
 * @param1  string 场景   $scene
 * @param2  string $code 
 * @return boolean 
 */
function checkSmsCode($uid, $phone, $scene, $code)
{     
	 $redisClient=Common\Api\RedisCluster::getInstance();
	 $key=$scene.'_'.$uid.'_'.$phone;
	 $res_code= $redisClient->get($key);
	 if($res_code)
	 {
	 	if($res_code==$code)
	 	{   
	 		//验证后删除 及时清除内存  所以短信验证码放在最后验证
            // $redisClient->del($key);
	 		return true;
	 	}
	 }
	 return false;
}
/**
 * 格式化財務日誌搜鎖時間
 * @param $num 
 * @author yangpeng 
 * 2017-8-17
 */
function formatAddTime($num){
    $where = "";
        switch ($num){
            case 1 :   
                $time =time()- 7*24*3600; //進一個星期
                $where .= 'and';
                $where.=" add_time >=$time ";  
                break;
             case 2 :   
                $time =time()- 30*24*3600; //進一個月
                $where .= 'and';
                $where.=" add_time >=$time ";  
                break;
             case 3 :   
                $time =time()- 3*30*24*3600; //進三個月
                $where .= 'and';
                $where.=" add_time >=$time ";  
                break;
             case 4 :   
                break;
        }
        return $where;
}

//格式化资金划转类型

function forToMoneyType($type){
    switch ($type) {
        case 1 :
            $arr = "奖金账户转资金账户";
            break;
        case 2 :
            $arr = "资金账户转奖金账户";
            break;
    }
    return $arr;
}
/**
 * curl get请求
 * @author lirunqing 2017-10-17T11:09:54+0800
 * @param  string $url 请求url地址
 * @return json
 */
function vget($url) { // 模拟获取内容函数
    $curl = curl_init (); // 启动一个CURL会话
    curl_setopt ( $curl, CURLOPT_URL, $url ); // 要访问的地址
    curl_setopt ( $curl, CURLOPT_HTTPGET, 1 ); // 发送一个常规的Post请求
    curl_setopt ( $curl, CURLOPT_TIMEOUT, 120 ); // 设置超时限制防止死循环
    curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 ); // 获取的信息以文件流的形式返回
    $tmpInfo = curl_exec ( $curl ); // 执行操作
    if (curl_errno ( $curl )) {
        echo 'Errno' . curl_error ( $curl );
    }
    curl_close ( $curl ); // 关闭CURL会话
    return $tmpInfo; // 返回数据
}
/**
 * 后台-格式化日志类型
 * @param 交易类型 $num
 * @author yangpeng 
 * 2017-8-14
 * 1提币，2充币，3系统(充值),4系统(扣除)，5线下交易撤销返款，6线下交易购买人获取，7线下交易挂售人扣除，8线下交易手续费扣除，9线下交易手续费返还
 * 10扣除币币交易手续费，11返还币币交易手续费，12扣除币币交易数量，13返还币币交易数量，14线下交易(管理员)手续费返还，15线下交易(管理员)撤销返款，16币币交易(管理员)撤销返还
 * 17线下交易撤销返款(系统)，18线下交易撤销手续返还(系统)
 */
function formatFinanceType($num){
    switch ($num){
        case 1 : $arr = "提幣";
            break;
        case 2 : $arr = "充幣";
            break;
        case 3 : $arr = "系統(充值)";
            break;
        case 4 : $arr = "系統(扣除)";
            break;
//        case 5 : $arr = "線下交易撤銷返款";
//            break;
//        case 6 : $arr = "線下交易購買人獲取";
//            break;
//        case 7 : $arr = "線下交易掛售人扣除";
//            break;
//        case 8 : $arr = "線下交易手續費扣除";
//            break;
//        case 9 : $arr = "線下交易手續費返還";
//            break;
//        case 10 : $arr = "扣除幣幣交易手續費";
//            break;
//        case 11 : $arr = "返還幣幣交易手續費";
//            break;
//        case 12 : $arr = "扣除幣幣交易數量";
//            break;
//        case 13 : $arr = "幣幣交易成交入賬";
//            break;
//        case 14 : $arr = "線下交易（管理員）手續費返還";
//            break;
//        case 15 : $arr = "線下交易（管理員）撤銷返款";
//            break;
//        case 16 : $arr = "幣幣交易（管理員）撤銷返還";
//            break;
//        case 17 : $arr = "線下交易撤銷返款（系統）";
//            break;
//        case 18 : $arr = "線下交易撤銷手續費返還（系統）";
//            break;
//        case 19 : $arr = "C2C掛單扣除幣";
//            break;
//        case 20 : $arr = "C2C掛單撤銷返還幣";
//            break;
//        case 21 : $arr = "C2C保證金扣除";
//            break;
//        case 22 : $arr = "C2C掛單保證金返還";
//            break;
//        case 23 : $arr = "C2C交易手續費扣除";
//            break;
//        case 24 : $arr = "C2C交易手續費返還";
//            break;
//        case 25 : $arr = "C2C交易訂單扣除";
//            break;
//        case 26 : $arr = "C2C交易訂單撤銷返還";
//            break;
//        case 27 : $arr = "C2C交易訂單入賬";
//            break;
//        case 28 : $arr = "C2C管理員操作放幣給買家";
//            break;
//        case 29 : $arr = "C2C管理員操作扣除買家手續費";
//            break;
//        case 30 : $arr = "C2C管理員操作退幣給賣家";
//            break;
//        case 31 : $arr = "C2C管理員操作退還賣家手續費";
//            break;
//        case 32 : $arr = "C2C買家獲取幣（系統）";
//            break;
//        case 33 : $arr = "C2C買家獲取幣扣除手續費（系統）";
//            break;
//        case 34 : $arr = "C2C掛單返還幣（系統）";
//            break;
//        case 35 : $arr = "C2C挂单返还手续费(系统)";
//            break;
//        case 36 : $arr = "C2C挂单返还保证金(系统)";
//            break;
//        case 37 : $arr = "幣幣交易撤銷返還";
//            break;
    }
    return $arr;
}

//格式化来源类型
function formatType($num){
    switch ($num) {
        case 1 :
            $arr = "30u报单";
            break;
        case 2 :
            $arr = "3000u矿机";
            break;
    }
    return $arr;
}
//格式化奖金类型
function formatRewardType($num){
    switch ($num) {
        case 1 :
            $arr = "直推奖";
            break;
        case 2 :
            $arr = "见点奖";
            break;
    }
    return $arr;
}
//奖金类型
function getRewardType(){
    $arr = array(
        array('id'=>1,'name'=>'直推奖'),
        array('id'=>2,'name'=>'见点奖'),
    );
    return $arr;
}
//奖金来源
function getRewardTypeList(){
    $arr = array(
            array('id'=>1,'name'=>'30u报单'),
            array('id'=>2,'name'=>'3000u矿机'),
        );
    return $arr;
}
/**
 * 后台显示日志类型
 * 杨鹏
 * 2017年8月29日12:07:04
 */
function getFinanceTypeList(){
    $arr = array(
        array('id'=>1,'name'=>'提幣'),
        array('id'=>2,'name'=>'充幣'),
        array('id'=>3,'name'=>'系統(充值)'),
        array('id'=>4,'name'=>'系統(扣除)'),
//        array('id'=>5,'name'=>'線下交易撤銷返款'),
//        array('id'=>6,'name'=>'線下交易購買人獲取'),
//        array('id'=>7,'name'=>'線下交易掛售人扣除'),
//        array('id'=>8,'name'=>'線下交易手續費扣除'),
//        array('id'=>9,'name'=>'線下交易手續費返還'),
//
//        array('id'=>14,'name'=>'線下交易（管理員）手續費返還'),
//        array('id'=>15,'name'=>'線下交易（管理員）撤銷返款'),
//
//        array('id'=>17,'name'=>'線下交易撤銷返款（系統）'),
//        array('id'=>18,'name'=>'線下交易撤銷手續費返還（系統）'),
//
//        array('id'=>10,'name'=>'扣除幣幣交易手續費'),
//        array('id'=>11,'name'=>'返還幣幣交易手續費'),
//        array('id'=>12,'name'=>'扣除幣幣交易數量'),
//        array('id'=>13,'name'=>'幣幣交易成交入賬'),
////        array('id'=>14,'name'=>'線下交易（管理員）手續費返還'),
////        array('id'=>15,'name'=>'線下交易（管理員）撤銷返款'),
//        array('id'=>37,'name'=>'幣幣交易撤銷返還'),// 37.幣幣交易撤銷入賬
//
//        array('id'=>16,'name'=>'幣幣交易（管理員）撤銷返還'),
////        array('id'=>17,'name'=>'線下交易撤銷返款（系統）'),
////        array('id'=>18,'name'=>'線下交易撤銷手續費返還（系統）'),
//        array('id'=>19,'name'=>'C2C掛單扣除幣'),
//        array('id'=>20,'name'=>'C2C撤銷返還幣'),
//        array('id'=>21,'name'=>'C2C掛單保證金扣除'),
//        array('id'=>22,'name'=>'C2C掛單保證金返還'),
//        array('id'=>23,'name'=>'C2C交易手續費扣除'),
//        array('id'=>24,'name'=>'C2C交易手續費返還'),
//        array('id'=>25,'name'=>'C2C交易訂單扣除'),//25.C2C交易订单扣除
//        array('id'=>26,'name'=>'C2C交易訂單撤銷返還'),//26.C2C交易订单撤销返还
//        array('id'=>27,'name'=>'C2C交易訂單入賬'),//27.C2C交易订单入账
//        array('id'=>28,'name'=>'C2C管理員操作放幣給買家'),//28.C2C管理员操作放币给买家
//        array('id'=>29,'name'=>'C2C管理員操作扣除買家手續費'),//29.C2C管理员操作扣除买家手续费
//        array('id'=>30,'name'=>'C2C管理員操作退幣給賣家'),//30.C2C管理员操作退币给卖家
//        array('id'=>31,'name'=>'C2C管理員操作退還賣家手續費'),// 31.C2C管理员操作退还卖家手续费
//        array('id'=>32,'name'=>'C2C買家獲取幣（系統）'),//32.C2C买家获取币(系统)
//        array('id'=>33,'name'=>'C2C買家獲取幣扣除手續費（系統）'),//33.C2C买家获取币扣除手续费(系统)
//        array('id'=>34,'name'=>'C2C掛單返還幣（系統）'),// 34.C2C掛單返還幣(系統)'
//        array('id'=>35,'name'=>'C2C挂单返还手续费(系统)'),// 35.C2C挂单返还手续费(系统)
//        array('id'=>36,'name'=>'C2C挂单返还保证金(系统)'),// 36.C2C挂单返还保证金(系统)',
//        array('id'=>37,'name'=>'幣幣交易撤銷返還'),// 37.幣幣交易撤銷入賬
    );
    return $arr;
}


/**
 * 币币交易区 BTC
 *@author  建强 2017年8月29日12:07:04
 */
function getBtcAreaType($entrust_type)
{
	 $arr=[
			"1"=>"ltc/usdt",
			"2"=>"etc/usdt",
			"3"=>"eth/usdt",
			"4"=>"bch/usdt",
            "5"=>'eos/usdt'
	];
	return  $arr[$entrust_type]?strtoupper($arr[$entrust_type]):'';
}


/**
 * 币币交易区 BCC
 *@author  建强 2017年8月29日12:07:04
 */
function getVpAreaType($entrust_type)
{
	$arr=[
			 "1"=>"btc/vp",    
    		 "2"=>"ltc/vp",
    		 "3"=>"etc/vp", 
    		 "4"=>'eth/vp',
    		 "5"=>'bch/vp'
	];
	return  $arr[$entrust_type]?strtoupper($arr[$entrust_type]):'';
}

function getBiBiStatus($status)
{
	$arr=[
		    "1"=>"买",
			"2"=>"卖",
			"3"=>"已完成",
			"4"=>"已撤销",
	];
    return  $arr[$status]?$arr[$status]:'';
}
/*
 * 李江 2018年2月28日16:33:34
 */
function getCtoCStatus($status){
    $arr=[
        "1"=>"挂单中",
        "2"=>"完成",
        "3"=>"用户撤销",
        "4"=>"系统自动撤销",
    ];
    return  $arr[$status] ? $arr[$status] : '';
}
/*
 * 李江 2018年2月28日16:33:34
 */
function getCtoCTradeStatus($status){
    $arr=[
        "1"=>"買入成功",
        "2"=>"買家確認打款",
        "3"=>"賣家確認收款",
        "4"=>"超時自動撤銷",
        '5'=>"待处理",
        '6'=>'管理员撤销订单',
        '7'=>'管理员完成订单',
        '8'=>'系统自动完成（自动放币）'

    ];
    return  $arr[$status] ? $arr[$status] : '';
}
/**
 * 格式化币种
 * @param 币种类型 $currency_id
 * @author yangpeng 
 * 2017-8-15
 */
function getCurrencyName($currency_id){
    $currencys = M('Currency')->select();
    foreach($currencys as $v){
        if($currency_id == $v['id']){
            return $v['currency_name'];
        }
    }
}

function formatCardType($type){
    switch ($type){
        case 1 : $arr = '護照'; 
            break;
    }
	return $arr?$arr:'其他';
}

/**
 * 格式化日志类型
 * @param int $type
 * 黎玲  2017年10月25日10:20:57
 */
function formatLogType1($type){
    switch ($type){
        case 1 : $type = '登錄';
            break;
        case 2 : $type = '修改密碼';
            break;
        case 3 : $type ='修改交易密碼';
            break;
        case 4 : $type = '找回密碼';
            break;
        case 5 : $type ='找回交易密碼';
            break;
		case 6 : $type = '用戶註冊';
		 break;
    }
    return $type;
}
/**
 * 获取某个IP地址所在的位置
 * @param string $ip	ip地址
 * @return Ambigous <multitype:, NULL, string>
 * 黎玲 2017 10 12
 */
function getIpArea($ip){
    $Ip = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
    $area = $Ip->getlocation($ip); // 获取某个IP地址所在的位置
    return $area?$area['country'].$area['area']:"未知地址";
}

/*
 *  短信发送类型记录
 *  1.登录场景 2找回密码 3找回交易密码
 */
function  FormatSmsType($type)
{
    $arr=[
            '1'=>'註冊',
            '2'=>'提幣',
            '3'=>'解綁',
            '4'=>'綁定',
            '5'=>'App登陸',
	];

	return $arr[$type]?$arr[$type]:'';
}
/**
 * 格式化等级类型
 * @authore 宋建强  2017年10月25日10:20:57
 * @param int $level
 */
function formatLevel($level)
{
	$vip_level=[
			'0'=>'0级',
			'1'=>'V1级',
			'2'=>'V2级',
			'3'=>'V3级',
			'4'=>'V4级',
			'5'=>'V5级',
	];  
	return $vip_level[$level]?$vip_level[$level]:'0級';
}
/**
 * ip转换成地理位置
 * @author 后台专用
 */
function GetIPAreaInfo($ip){
	$IpLocation = new Org\Net\IpLocation();
	$res = $IpLocation->getlocation($ip);
	return !empty($res['country'])?$res['country']:'位置未知';
}



/**
 * @param int $system_reply
 * @return string
 */
function  formatUserRealReply($system_reply)
{
	$word=[
	    "1"=>"您的證件已註冊綁定，請勿重複註冊。",
	    "2"=>"您的手持證件照片證件不清晰，無法查看證件姓名和證件號碼，請重新拍照上傳。",
	    "3"=>"證件號碼或姓名被遮擋。",
	    "4"=>"照片格式錯誤(標準格式為.jpg,照片體積不能超過 3MB)",
	    "5"=>"您所提交的實名信息與手持證件照不相符或被判定為後期處理照片。",
	    "6"=>"您的證件年齡超限。",
	    "7" =>"身份認證待審核",
	    "8"=> "符合條件給予通過",
	    "9"=>"您使用的證件不正確，請使用護照進行注册認證。",
	];
	return $word[$system_reply]?$word[$system_reply]:'';
}

/**
 * 格式化订单的输出日志
 * @author 建强 2017年11日20日
 * @param unknown $orderType
 * @return string
*/
function   OrderStatus($orderType)
{
		$arr=[
			'0'=>'掛單',
			//三個狀態是前臺的用戶
			'1'=>'匹配成功',
			'2'=>'買家確認打款',
			//賣家確認收款
			'3'=>'完成',
			//後臺確認訂單完成
			'4'=>'後台完成',
			//前臺用戶撤銷
			'5'=>'用戶撤銷',
			//后台客服撤銷
			'6'=>'后台撤銷',
			//后台客服撤銷
			'7'=>'系統自動撤銷',
			'8'=>'待處理',
		];
		
	   return $arr[$orderType]?$arr[$orderType]:'';
}

/**
 * 刷单交易区配置
 * @param $entrust_type
*/
function  entrustTypeBiBibrush($entrust_type)
{ 
	$arr=[
			"1"=>"ltc/btc",
			"2"=>"etc/btc",
			"3"=>"eth/btc",
			"4"=>"bcc/btc",
			 //5-9代表vp的兑换
			 "5"=>"btc/vp",    
		     "6"=>"ltc/vp",
		     "7"=>"etc/vp", 
		     "8"=>'eth/vp',
		     "9"=>'bch/vp'		
	];
	return $arr[$entrust_type]?$arr[$entrust_type]:'';
}


/**
 * 根据积分获取用户等级
 * @author 2017-12-06T12:23:05+0800
 * @param  [type] $integral [description]
 * @return [type]           [description]
 * 李江 拷贝
 */
function getUserLevel($integral){
    switch ($integral) {
        case $integral >= 100 && $integral < 1000:
            $level = 1;
            break;
        case $integral >= 1000 && $integral < 3000:
            $level = 2;
            break;
        case $integral >= 3000 && $integral < 6000:
            $level = 3;
            break;
        case $integral >= 6000 && $integral < 16000:
            $level = 4;
            break;
        case $integral >= 16000:
            $level = 5;
            break;
        default:
            $level = 0;
            break;
    }

    return $level;
}

if (!function_exists('tokenType')) {
	/**
	 * 格式化Token使用类型 
	 * @author  建强  2017年12月7日12:19:06
	 * @param  $type 
	 * @return string
	 */
	function tokenType($type)
	{    
		  $typeArr=[
		  		'1'=>'登錄  ',
		  		'2'=>'修改資金密碼',
		  		'3'=>'修改登錄密碼',
		  ];
		  return $typeArr[$type]?$typeArr[$type]:'unknown';
	}
}

if (!function_exists('UserBindApp')) {
	/**
	 * 格式化绑定APP使用类型
	 * @author  建强  2017年12月7日14:21:31
	 * @param  $type
	 * @return string
	 */
	function UserBindApp($type)
	{
		$typeArr=[
				'1'=>'令牌綁定 ',
				'2'=>'令牌解綁'
		];
		return $typeArr[$type]?$typeArr[$type]:'unknown';
	}
}
/**
 * 币所属市场级别
 * @param unknown $flag
 */
function classTypeFormat($flag)
{
	$classMarket=[
			'1'=>'一級市場幣 ',
			'2'=>'二級市場幣'
	];
	return $classMarket[$flag]?$classMarket[$flag]:'unknown';
}
function FormatAction($type){
     switch ($type){
        case 1 : $type = '綁定電話號碼';
            break;
        case 2 : $type = '綁定郵箱';
            break;
        case 3 : $type ='綁定充值地址';
            break;
        case 4 : $type = '綁定轉出地址';
            break;
        case 5 : $type ='綁定APP令牌';
            break;
        case 6 : $type = '交易密碼';
            break;
        case 7 : $type = '銀行卡賬戶';
            break;
        case 8 : $type = '每天首次登陸';
            break;
        case 9 : $type = '訂單交易';
            break;
        case 10 : $type = '充值錢';
            break;
        case 11 : $type = '充值幣';
            break;
        case 12 : $type = 'vip充值資產額';
            break;
        case 13 : $type = '實名認證通過';
            break;
    }
    return $type;
}

/**
 * APP版本管理
 *@author  建强 2017年8月29日12:07:04
 */
function getAppLevel($level)
{
    $arr=[

        "1"=>"最新版本",
        "2"=>"有新版本更新",
        "3"=>"需要强制更新",
    ];
    return  $arr[$level]?strtoupper($arr[$level]):'';
}

/**
 * @param $os
 * @return string app版本系统
 */
function getAppOs($os)
{
    $arr=[

        "1" => 'IOS',
        "2" => 'Andriod'
    ];
    return  $arr[$os]?strtoupper($arr[$os]):'';
}

/*
 * 李江
 * 2018年5月21日11:18:35
 * 比较两个表的数据差集 如果没有记录 添加
 */
function checkUserCurrencyRecord($uid){
    $userCurrencyList = M('UserCurrency')->where(['uid'=>$uid])->field('currency_id')->select();
    $currencyList = M('Currency')->where(['status'=>1])->field('id')->select();

    $bbb = array_column($userCurrencyList,'currency_id');
    $sss = array_column($currencyList,'id');

    $diff = array_diff($sss,$bbb);
    $res = true;
    if( count($diff) > 0 ){
        $data['uid'] = $uid;
        foreach ($diff as $currencyId){
            $data['currency_id'] = $currencyId;
            $lastData[] = $data;
        }
        $res = M('UserCurrency')->addAll($lastData);
    }
    return $res;
}

function formatBankStatus(){

}

/**
 * @method 生成实名认证提交token 防止重复提交
 * @author 杨鹏 2019年4月1日12:17:23
 * @param null
 * @retrun null
 */
function createToken(){

    $code = md5(md5(microtime()));
    $redis=\Common\Api\RedisIndex::getInstance();
    $redis->setSessionRedis('REAL_NAME_TOKEN',$code);
    $value = $redis->getSessionValue('REAL_NAME_TOKEN');
    return $value;
}
/**
 * @method 验证实名认证token
 * @author 杨鹏 2019年4月1日12:17:44
 * @param $token
 * @retrun bool
 */
function checkToken($token){
    $redis=\Common\Api\RedisIndex::getInstance();
    $value = $redis->getSessionValue('REAL_NAME_TOKEN');
    if($token == $value) {
        $redis->delSessionRedis('REAL_NAME_TOKEN');
        return true;
    }
    return false;
}

/**
 * @param int $int
 * @method 转日期 转化大于2038年的时间戳 
 * @return string | int 
 */
function bigIntTimeStampeToDate($timeStampe){
    if($timeStampe <= 0 ) return $timeStampe;
    
    $time     = new DateTime("@$timeStampe"); 
    $timezone = timezone_open('Asia/Shanghai'); 
    $time->setTimezone($timezone);
    return $time->format("Y-m-d H:i:s"); 
}

