<?php
return array(
		'TMPL_PARSE_STRING' => array (
			'__ADMIN_CSS__' => '/Public/Back/css',
			'__ADMIN_JS__' => '/Public/Back/js',
			'__ADMIN_IMG__' => '/Public/Back/images',
		),
  		'URL_MODEL' => '2',
		
		//'配置项'=>'配置值'
	    'DEFAULT_CONTROLLER'    =>  'Login',     // 默认控制器名称
	    'DEFAULT_ACTION'        =>  'showLogin', // 默认操作名称
        'DB_FIELDS_CACHE'=>false,                //关闭数据库字段缓存
     	//每页显示数量
		'PRE_PAGE_COUNT'=>15,
       'URL_CASE_INSENSITIVE' =>FALSE, 
);
