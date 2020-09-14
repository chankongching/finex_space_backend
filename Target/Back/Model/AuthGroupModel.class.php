<?php
namespace Back\Model;
/**
 * 权限规则model
 */
class AuthGroupModel extends BaseModel{

	/**
	 * 传递主键id删除数据
	 * @param  array   $map  主键id
	 * @return boolean       操作是否成功
	 */
	public function deleteData($map){
		
		$group_map=array(
			'group_id'=>$map['id']
			);
		//判断该组是否存在用户
	    $count=M('AuthGroupAccess')->where($group_map)->count();
	    //判断该组是不是存在关联的用户 
	    if($count>0)
	    {
	    	return false;
	    }
		// 删除用户组的时候 需要首先删除组下成员
	    $result=$this->where($map)->delete();   
		return $result;
	}
}
