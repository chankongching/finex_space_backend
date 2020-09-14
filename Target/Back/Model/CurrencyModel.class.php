<?php
/**
 * Created by PhpStorm.
 * User: 李江
 * Date: 2017/10/12
 * Time: 15:24
 */

namespace Back\Model;

use Think\Model;

class CurrencyModel extends Model
{
    /**
     * 状态：禁用
     */
    const STATUS_DISABLED = 0;

    /**
     * 状态：启用
     */
    const STATUS_ENABLED = 1;

    public function getCurrencyByCurrencyid($currency_id, $field = '*')
    {
        $res = $this->where(['id' => $currency_id])->find();
        return $res['currency_mark'] ? $res['currency_mark'] : '';
    }

    public function getCurrencyList()
    {
        return $this->getField('id,currency_name');
    }
}