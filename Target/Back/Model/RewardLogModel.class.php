<?php

namespace Back\Model;

use Think\Model;

class RewardLogModel extends Model
{
    /**
     * 类型：30u报单
     */
    const TYPE_30U = 1;

    /**
     * 类型：3000u矿机
     */
    const TYPE_3000U = 2;

    /**
     * 奖励类型：直推奖
     */
    const REWARD_TYPE = 1;

    /**
     * 奖励类型：见点奖
     */
    const REWARD_TYPE_POINT = 2;
}