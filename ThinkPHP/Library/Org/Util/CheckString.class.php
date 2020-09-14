<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Org\Util;
class CheckString { 
	 /** 
	* 过滤sql与php文件操作的关键字 
	* @param string $string 
	* @return string 
	*/ 
	private function filter_keyword( $string ) { 
		$keyword = 'select|insert|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|and|union|order|or|into|load_file|outfile'; 
		$arr = explode( '|', $keyword ); 
		$result = str_ireplace( $arr, '', $string ); 
		return $result; 
	} 

	/** 
	* 检查输入的数字是否合法，合法返回对应id，否则返回false 
	* @param integer $id 
	* @return mixed 
	*/ 
	public function check_id( $id ) {
		$result = false; 
		if ( $id !== '' && !is_null( $id ) ) { 
			$var = $this->filter_keyword( $id ); // 过滤sql与php文件操作的关键字 
		if ( $var !== '' && !is_null( $var ) && is_numeric( $var ) ) { 
			 $result = intval( $var ); 
			 } 
		} 
	  return $result; 
	} 

	/** 
	* 检查输入的字符是否合法，合法返回对应id，否则返回false 
	* @param string $string 
	* @return mixed 
	*/ 
	public function check_str( $string ) {
			$result = false;
			$var = $this->filter_keyword( $string ); // 过滤sql与php文件操作的关键字 
				if ( !empty( $var ) ) { 
					if ( !get_magic_quotes_gpc() ) { // 判断magic_quotes_gpc是否为打开 
					  $var = addslashes( $string ); // 进行magic_quotes_gpc没有打开的情况对提交数据的过滤 
					} 
					$var = str_replace( "_", "\_", $var ); // 把 '_'过滤掉 
					$var = str_replace( "%", "\%", $var ); // 把 '%'过滤掉 
					$var = str_replace(">", "", $var);
					$var = str_replace("<", "", $var);
					$var=str_replace("select","se32le24c5t",$var);
					$var=str_replace("join","jo2i45n",$var);
					$var=str_replace("union","u24n2ion",$var);
					$var=str_replace("where","w45rhe2re",$var);
					$var=str_replace("insert","i21n2s2ert",$var);
					$var=str_replace("delete","de2l551ete",$var);
					$var=str_replace("update","u4pd2ate",$var);
					$var=str_replace("like","l2i2k4e",$var);
					$var=str_replace("drop","d2r4o1p",$var);
					$var=str_replace("create","cre21a4te",$var);
					$var=str_replace("modify","mod2ilfy",$var);
					$var=str_replace("rename","re1na2me",$var);
					$var=str_replace("alter","al32t1er",$var);
					$var=str_replace("cas","ca2s123t",$var);
					
					$var=str_replace("SELECT","se32le24c5t",$var);
					$var=str_replace("JOIN","jo2i45n",$var);
					$var=str_replace("UNION","u24n2ion",$var);
					$var=str_replace("WHERE","w45rhe2re",$var);
					$var=str_replace("INSERT","i21n2s2ert",$var);
					$var=str_replace("DELETE","de2l551ete",$var);
					$var=str_replace("UPDATE","u4pd2ate",$var);
					$var=str_replace("LIKE","l2i2k4e",$var);
					$var=str_replace("DROP","d2r4o1p",$var);
					$var=str_replace("CREATE","cre21a4te",$var);
					$var=str_replace("modifyMODIFY","mod2ilfy",$var);
					$var=str_replace("RENAME","re1na2me",$var);
					$var=str_replace("ALTER","al32t1er",$var);
					$var=str_replace("CAS","ca2s123t",$var);
					
					$var=str_replace("&","345",$var);
					$var=str_replace("'",chr(39),$var);
					$var=str_replace("''","'",$var);
					$var=str_replace("css","'",$var);
					$var=str_replace("CSS","'",$var);
					$var = nl2br( $var ); // 回车转换 
					$var = htmlspecialchars( $var ); // html标记转换 
					$result = $var;
				} 
			return $result; 
		} 

}