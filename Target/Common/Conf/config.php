<?php
return array(
        'LOAD_EXT_CONFIG'   => 'configdb,SysConfigdb,coinurlconfig', // 加载扩展配置文件
        'PASSWORDSUFFIX'    => 'awioghiowqegoqajhgoi32jgo23',
		'default_module'     => 'Back',    //默认模块
        'MODULE_ALLOW_LIST' => array('Back','Timer'),
        'MODULE_DENY_LIST'  =>  array('Common','Runtime','Api'),
		'URL_MODEL' => 2,         ///默认是1 PATHIFO路由
	    'URL_ROUTER_ON' => true,  // 是否开启URL路由
	  // 'SHOW_PAGE_TRACE'=>true,  //页面追踪
        'IS_DEBUG_VER'         => false, // 测试环境，上线后要设置为false
        'SYS_AUTO_SECRET_KEY'  => '7V/s-h<@Lrk+EV1/y&}-sY=4YQZc-v!O', // 系统加密随机字符串，不能改动
        'LOAD_EXT_FILE'    => 'func_app_common',
		'API_DOMAIN' => 'http://192.168.2.228:1338/app/api', //测试环境前端API，上线后需要改为生成环境
        'API_TOKEN_SUFFIX'    => 'Lrk+@EV1/y&@k#$aEKf@$45E6', //api接口密钥,要跟前端一致
		//===============特别注意此配置不要随便动========================//
		'SESSION_AUTO_START' => false,

     //   'TMPL_EXCEPTION_FILE'=>'./Target/Common/404.html' ,//关闭debug模式错误跳入404
       // 'TMPL_ACTION_ERROR'  =>'./Target/Common/404.html', // 默认错误跳转对应的模板文件'
        'URL_CASE_INSENSITIVE' =>FALSE,         //严格区分大小写
        'LOG_RECORD' => true,                   // 开启日志记录
        'LOG_LEVEL'  =>'EMERG,ALERT,CRIT,ERR,SQL',  // 只记录EMERG ALERT CRIT ERR 错误
);
