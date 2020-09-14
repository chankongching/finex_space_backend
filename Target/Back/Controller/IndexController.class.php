<?php
namespace Back\Controller;
use Think\Cache\Driver\Redisd;
use Back\Tools\Utils;
/**后台首页控制器
 * @author 宋建强
 * @Date   2017年10月9日 10：30
 */
class IndexController extends BackBaseController 
{
	/** 
	 * 首页
	*/
	public function Index()
	{
		$this->display();
	}
	
}