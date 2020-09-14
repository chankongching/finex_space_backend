<?php
namespace Back\Common;
/*
 * 后台配置的接口选项  Table  newtrade_config  newtrade_interface_config
 * @author 宋建强
 * @Date 2017年 8月15日14:28
*/
class ConfigInterFaceConfig
{
	public  $config;
	/*
	  *构造方法赋值属性 
	*/
	public function __construct()
	{
		$this->config=$this->getConfig();
	}
   	
	/**
	 * 接口配置项 config
	 */
	private function getConfig(){
		$config=M('Config')->select();
		return array_column($config,'value','key');
	}
}

