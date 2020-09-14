<?php
namespace Back\Controller;

/**
 * 后台菜单管理模块
 * author 宋建强 2017年8月11日
 */
class NavController extends BackBaseController{
	/**
	 * 菜单列表
	 */
	public function index()
	{ 
		$data=D('AdminNav')->getTreeData('tree','order_number,id');
		$assign=array(
			'tabledata'=>$data
		);
		$this->assign($assign);
		$this->display('meun');
	}

	/*
	 * 邮箱配置
	 */
	public function email()
    {
        if (IS_POST){
            $data=I('post.');
            $map=array(
                'id'=>$data['id']
            );
            unset($data["id"]);
            $res =  M('EmailConf')->where($map)->save($data);
            if ($res){
                $this->success('修改成功',U('Back/Nav/email'));
            }
        }
        $data = M("EmailConf")->find();
        $this->assign("user_list",$data);
        $this->display();
    }
	/**
	 * 添加菜单
	 */
	public function add()
	{
		$data=I('post.');
		unset($data['id']);
	    //添加一个防止重名
	    $mca=trim(I('post.mca'));
	    $res= M('AdminNav')->where(['mca'=>$mca])->find();

	    if($res)
	    {
	    	 $this->error('該鏈接已存在請更換');
             exit();	    	 
	    }
	   
		D('AdminNav')->addData($data);
		$this->success('添加成功',U('Back/Nav/index'));
	}

	/**
	 * 修改菜单
	 */
	public function edit(){
		$data=I('post.');
		$map=array(
			'id'=>$data['id']
        );
		D('AdminNav')->editData($map,$data);
		$this->success('修改成功',U('Back/Nav/index'));
	}

	/**
	 * 删除菜单
	 */
	public function delete(){
		$id=I('get.id');
		$map=array(
			'id'=>$id
		);
		$result=D('AdminNav')->deleteData($map);
		if($result){
			$this->success('刪除成功',U('Back/Nav/index'));
		}else{
			$this->error('請先刪除子菜單');
		}
	}

	public function getNavAjax(){
		$id=I('post.id');
		$data = D('AdminNav')->where(array('id'=>$id))->find();
		die(json_encode($data));
	}
	
	/**
	 * 菜单排序
	 */
	public function order(){
		$data=I('post.');
		D('AdminNav')->orderData($data);
		$this->success('排序成功',U('Back/Nav/index'));
	}
}
