<?php

namespace Back\Model;

use Think\Model;

class ArticleModel extends Model
{
    /**
     * 类型：文章
     */
    const TYPE_ARTICLE = 1;

    /**
     * 类型：广告
     */
    const TYPE_ADS = 2;

    /**
     * 类型：新手指引
     */
    const TYPE_GUIDE = 3;

    /**
     * 类型：商学院
     */
    const TYPE_BUSINESS = 4;

    /**
     * 状态：禁用
     */
    const STATUS_DISABLED = 0;

    /**
     * 状态：启用
     */
    const STATUS_ENDBALED = 1;
}