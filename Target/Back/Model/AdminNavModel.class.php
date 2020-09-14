<?php
namespace Back\Model;

/**
 * 菜单操作model
 */
class AdminNavModel extends BaseModel{

	/**
	 * 删除数据
	 * @param	array	$map	where语句数组形式
	 * @return	boolean			操作是否成功
	 */
	public function deleteData($map){
		$count=$this
			->where(array('pid'=>$map['id']))
			->count();
		if($count!=0){
			return false;
		}
		$this->where(array($map))->delete();
		return true;
	}

	/**
	 * 获取全部菜单
	 * @param  string $type tree获取树形结构 level获取层级结构
	 * @return array       	结构数据
	 */
	public function getTreeData($type='tree',$order='',$user=''){
		// 判断是否需要排序
		if(empty($order)){
			$data=$this->select();
		}else{
			$data=$this->order('order_number is null,'.$order)->select();
		}
		// 获取树形或者结构数据
		if($type=='tree')
		{
			$data=\Org\Nx\Data::tree($data,'name','id','pid');
		}
		elseif($type=="level")
		{
			//数据处理 导航表中的数据
 			$data=\Org\Nx\Data::channelLevel($data,0,'&nbsp;','id');
 		
			$auth=new \Think\Auth();
			foreach ($data as $k => $v) 
			{
			    //如果拥有顶级菜单栏
				if ($auth->check($v['mca'],$user['id']))
				{
				
					 //循坏判二维数组的二级菜单  
					foreach ($v['_data'] as $m => $n) 
					{   
						// 如果二级菜单用户没有权限   删掉 
						if(!$auth->check($n['mca'],$user['id'])) 
						{
							unset($data[$k]['_data'][$m]);
						}
					}
				}
				else
				{
					// 删除的顶级菜单栏
					unset($data[$k]);
				}
			}
		}
		return $data;
	}
}
