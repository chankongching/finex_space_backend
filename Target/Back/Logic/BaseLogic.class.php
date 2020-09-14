<?php
/** *
 * User: 刘富国
 * Date: 2017/9/26
 * Time: 17:01
 */
namespace Back\Logic;

class BaseLogic
{

    /**
     * @var array
     */
    public $data;

    public function __construct($data=[])
    {
        $this->setData($data);
    }

    //最后一次错误代码
    public $errno = 0;

    //最后一次错误信息
    public $errmsg = '';
    /**
     * 设置数据
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * 获取数据
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 设置错误信息
     *
     * @param int $errno
     * @param string $errmsg
     * @return bool
     * 刘富国
     */
    public function return_error($errno = 0, $errmsg = '' ){
        $this->errno  = $errno;
        $this->errmsg =	$errmsg;
        return false ;
    }

    /**
     * 获取最后一次错误代码
     * 刘富国
     */
    public function last_error(){
        if($this->errno > 0 ){
            return   $this->errmsg ? $this->errmsg : 'UNKNOW_ERROR';
        }
        else{
            return '';
        }
    }

}