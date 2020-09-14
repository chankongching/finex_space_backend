<?php
namespace Back\Controller;
/*
 * 登录后台网站配置页
 * @author hanwenzhang
 * @Date   2017年8月11日11:26:36
 * 
*/
class ConfigController extends BackBaseController
{
    /**
     *  网站基本设置
     *  POST :
     * @param $_POST
     * author hanwenzhang
     * time   2017年8月11日11:51:57
     */
	
	public function changeInterfaceConfig(){
	    if(IS_GET) {
            $list = M('Interface_config')->select();
            $config = array_column($list,'value','key');
            $this->assign('config',$config);
            $this->display();
        } elseif(IS_POST){
            $arr=I('post.');
            foreach ($arr as $k=>$v){
                $where['key']=$k;
                M('Interface_config')->where($where)->setField('value',$v);
                unset($where);
            }
            $this->success('更新成功');exit();
        }
	}

    /**
     * 网站基本信息设置
     * author hanwenzhang
     * time   2017年8月11日11:54:11
     */
	public function changeConfig(){
        if(IS_GET) {
            $list=M('Config')->select();
            $this->assign('config',$list);
            $this->display();
        }
        elseif(IS_POST){
            $arr=I('post.');
            foreach ($arr as $k=>$v){
                $where['key']=$k;
                M('Config')->where($where)->setField('value',$v);
                unset($where);
            }
            $this->success('更新成功');exit();
        }
    }
    
    /**
     * @c2c交易配置列表
     * @author 建强  2018年2月26日17:26:35
     */
    public function ccfonfig()
    {   
    	   $list =M('CcConfig')
    	   ->alias('t')
    	   ->field('t.*,c.currency_name')
    	   ->join('LEFT JOIN __CURRENCY__ as c ON t.currency_id=c.id')
    	   ->where(['c.status'=>1])
    	   ->select();
    	   $this->assign("list",$list);
    	   $this->display();
    }
    /**
     *  添加c2c交易配置表
     *  @author 建强 2018年2月26日16:56:49
     */
     public function addccConfig()
     {
     	  if(IS_POST)
     	  {
     	  	   $params=I('post.');
     	  	   foreach ($params as $k=>$v)
     	  	   {
     	  	      if(!is_numeric($v))
     	  	      {
     	  	      	  return $this->error("数据格式不正确");
     	  	      }
     	  	      $params[$k]=strip_tags(trim($v));
     	  	   }
     	  	   $params['time']=time();  
     	  	   $res=M('CcConfig')->where(['currency_id'=>$params['currency_id']])->find();
     	  	   if($res) return $this->error("該幣種配置已存在");
     	  	   $result=M('CcConfig')->add($params);	
     	  	   if($result)
     	  	   {
     	  	   	   return $this->success("添加成功,",U("Config/ccfonfig"));
     	  	   }
     	  	   else 
     	  	   {
     	  	   	   return $this->error("添加失败,请稍后");
     	  	   }
     	  }
     	  $list=M('Currency')->field('id,currency_name')->select();
     	  $this->assign('currency_type',array_column($list, 'currency_name','id'));
          $this->display();	
     }
     
     /**
      * @edit修改c2c交易配置
      * @author 建强 2018年2月26日17:14:22 
      */
     public function editCcconfig()
     {
     	 if(IS_POST)
     	 {
     	 	$params=I('post.');
     	 	$currency_id=trim(I('post.currency_id'));
     	 	foreach ($params as $k=>$v)
     	 	{
     	 		if(!is_numeric($v))
     	 		{
     	 			return $this->error("数据格式不正确");
     	 		}
     	 		$params[$k]=strip_tags(trim($v));
     	 	}
     	    unset($params['currency_id']);
     	    $params['time']=time();
     	 	$result=M('CcConfig')->where(['currency_id'=>$currency_id])->save($params);
     	 	if($result)
     	 	{
     	 		return $this->success("修改成功,",U("Config/ccfonfig"));
     	 	}
     	 	else
     	 	{
     	 		return $this->error("修改失败,请稍后");
     	 	}
     	 }
     	 $id=trim(I('id'));
     	 $res =M('CcConfig')
    	 ->alias('t')
    	 ->field('t.*,c.currency_name')
    	 ->join('LEFT JOIN __CURRENCY__ as c ON t.currency_id=c.id')
    	 ->where("t.id={$id}")
    	 ->find();
     	 if(!$res)
     	 {
     	 	return $this->error("记录不存在");
     	 }
     	 $this->assign("res",$res);
     	 $this->display();
     }
     /**
      * delete 删除c2c配置
      */
      public function deleteCcconfig($id)
      {
      	  if(!is_numeric($id)) return $this->error("参数不正确");
      	  $result=M('CcConfig')->delete(trim($id));
          if($result)
          {
             return $this->success("删除成功",U("Config/ccList"));	
          }
          else 
          {
          	 return $this->error("删除失败");
          }
      }
}