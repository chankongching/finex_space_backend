<?php

namespace Back\Controller;

use Back\Model\CurrencyModel;
use Back\Tools\Page;

/**
 * 财务管理
 *2017-10-14
 * @author yangepng
 */
class UserMoneyController extends BackBaseController
{
    public $pagecount;
    public $typeArr = array(
        array('id' => 1, 'name' => '收入'),
        array('id' => 2, 'name' => '支出'),
    );

    public function _initialize()
    {
        parent::_initialize();
        $this->pagecount = C('PRA_PAGE_COUNT') ? C('PRA_PAGE_COUNT') : 15;
    }

    /**
     * 显示用户财务日志
     * 2017-10-14
     * @author yangpeng
     **/
    public function showFinanceLog()
    {
        if (IS_GET) {
//            	$this->DelLog("UserScoreLog",3);  //财务日志保留6个月
            $data = I('get.');
            /*1、接收表单数据,确定数据查询条件*/
            $where = $this->getWhereByInput($data);//获取查询条件
        }
        /*2、存在管理员输入时，确定数据分表、实例化分页类并拼装查询sql*/
        $model = M();
        $Page_data = $this->getPage($data, $where);
        $show = $Page_data['page']->show();// 分页显示输出
        $finance_list = $model->query($Page_data['sql']);
        /*3、格式化相关数据*/
        foreach ($finance_list as &$vo) {
            $vo['finance_type'] = formatFinanceType($vo['finance_type']);//格式化日志类型
            if ($vo['type'] == 1) {
                $vo['money'] = "+" . $vo['money'];
                $vo['color'] = 'green';
            } else {
                $vo['money'] = "-" . $vo['money'];
                $vo['color'] = 'red';
            }
            $vo['type'] = $vo['type'] == 1 ? '收入' : '支出';
        }
        $currency = $this->getCurrencyList();
        /*6、模板赋值*/
        $this->assign('type', $this->typeArr);
        $this->assign('page', $show);
        $this->assign('finance_type', getFinanceTypeList());
        $this->assign('currency', $currency);
        $this->assign('add_time', $this->getQueryTimeStr());
        $this->assign('finance_list', $finance_list);
        $this->display();
    }

    private function getQueryTime($str)
    {
        switch ($str) {
            case 1:
                $time = strtotime('-7 day');//过去一周
                break;
            case 2:
                $time = strtotime('-14 day');//过去一周
                break;
            case 3:
                $time = strtotime('-1 month');//过去一周
                break;
            case 4:
                $time = strtotime('-6 month');//过去一周
                break;
            case 5:
                $time = strtotime('-1 year');//过去一周
                break;
            case 6:
                $time = 0;
                break;
            default:
                $time = strtotime('-7 day');//过去一周
        }
        return $time;
    }

    private function getQueryTimeStr()
    {
        $str = [
            ['id' => '1', 'name' => '過去一周'],
            ['id' => '2', 'name' => '過去两周'],
            ['id' => '3', 'name' => '過去一个月'],
            ['id' => '4', 'name' => '過去半年'],
            ['id' => '5', 'name' => '過去一年'],
            ['id' => '6', 'name' => '所有數據'],
        ];
        return $str;
    }

    /**
     * 获取分页类和sql
     * yangpeng 2018年8月13日21:08:01
     * @param $data
     * @param $where
     * @return mixed
     */
    public function getPage($data, $where)
    {
        $model = M();
        if ($data['user_id'] || $data['userid'] || $data['name']) {
            $mod = $this->getMod($data['userid'], $data['user_id'], $data['username']);
            $countt = $model->query("SELECT count(*) as tcount from trade_user_finance$mod  $where[0]");
            $count = $countt[0]['tcount'];
            $Page = new \Back\Tools\Page($count, 15);// 实例化分页类 传入总记录数和每页显示的记录数(20)
            $sql = "SELECT m.*,n.username,p.currency_mark from trade_user_finance$mod as m left join trade_user as n on m.uid=n.uid left join trade_currency as p on m.currency_id=p.id $where[1] ORDER BY m.add_time desc, m.id desc limit $Page->firstRow,$Page->listRows";
        } else {
            /*4、不存在管理员输入时，实例化分页类并拼装查询sql*/
            $count0 = $model->query("select count(*) as tcount from trade_user_finance0 $where[0]");
            $count1 = $model->query("select count(*) as tcount from trade_user_finance1 $where[0]");
            $count2 = $model->query("select count(*) as tcount from trade_user_finance2 $where[0]");
            $count3 = $model->query("select count(*) as tcount from trade_user_finance3 $where[0]");
            $count4 = $model->query("select count(*) as tcount from trade_user_finance4 $where[0]");
            $count5 = $model->query("select count(*) as tcount from trade_user_finance5 $where[0]");
            $count6 = $model->query("select count(*) as tcount from trade_user_finance6 $where[0]");
            $count = $count0[0]['tcount'] + $count1[0]['tcount'] + $count2[0]['tcount'] + $count3[0]['tcount'] +
                $count4[0]['tcount'] + $count5[0]['tcount'] + $count6[0]['tcount'] ;
            $Page = new \Back\Tools\Page($count, 15);// 实例化分页类 传入总记录数和每页显示的记录数(20)
            $sql = "SELECT m.*,n.username,p.currency_mark"
                . " from  (SELECT *  from trade_user_finance0 $where[0]
                union all SELECT *  from trade_user_finance1 $where[0]
                union all SELECT *  from trade_user_finance2 $where[0]
                union all SELECT *  from trade_user_finance3 $where[0]
                union all SELECT *  from trade_user_finance4 $where[0]
                union all SELECT *  from trade_user_finance5 $where[0]
                union all SELECT *  from trade_user_finance6 $where[0]
                )as m "
                . "left join trade_user as n on m.uid=n.uid "
                . "left join trade_currency as p on m.currency_id=p.id"
                . " ORDER BY m.add_time desc, m.id desc"
                . " limit $Page->firstRow,$Page->listRows";
        }
        $page['page'] = $Page;
        $page['sql'] = $sql;
        return $page;
    }

    /**
     * @Author yangpeng  2018/7/11
     * @return 结果集|mixed
     */
    protected function getCurrencyList()
    {
        return M('Currency')->Field('id,currency_name')->select();
    }

    /*
     * 根據页面传递或输入的数据确定where查询条件
     * $userid 管理员通过表单输入的用户id
     * $user_id 从用户列表传递过来的用户id
     * $name 管理员通过表单输入的用户名
     * @author yangpeng
     * 2017-10-15
     */
    protected function getWhereByInput($data)
    {
        $type = $data['type'];//判断是表单提交还是从用户列表链接过来的标识（1是本页面表单提交）
        $data['add_time'] = $data['add_time'] ? trim($data['add_time']) : 1;//默认查询过去一周的数据
        if (!$type) {
            $data['user_id'] = I('user_id') ? trim(I('user_id')) : NULL;  //通过用户列表带过来的uid
        }
        if ($data['userid']) {//如果用户输入表单uid，则只按uid查询
            $data['userid'] = trim($data['userid']);
            $where_a = "where uid=" . $data['userid'];
            $where_b = "where m.uid=" . $data['userid'];
        } else {
            if ($data['user_id']) {//通过用户列表带过来的uid
                $data['user_id'] = trim($data['user_id']);
                $where_a = "where uid=" . $data['user_id'];
                $where_b = "where m.uid=" . $data['user_id'];
            }
            if ($data['username']) {////同时存在用户名和用户id时，优先用户名进行搜索
                $name = trim(str_replace('+', '', $data['username']));//剔除'+'和去除空格
                $model = new \Back\Model\UserModel();
                $uid = $model->getUidByUname($name);     //根據用戶名獲取用戶id
                $this->assign('username', $name);
                if ($uid) {
                    $where_a = "where uid=$uid ";
                    $where_b = "where m.uid=$uid ";
                } else {
                    $where_a = ' where 1 != 1 ';
                    $where_b = ' where 1 != 1 ';
                }
            }
        }
        if (!$data['username'] && !$data['user_id'] && !$data['userid']) {//没有任何条件时
            $where_a = ' where 1 = 1 ';
            $where_b = ' where 1 = 1 ';
        }
        if (!empty($data['currency_id']) && $data['currency_id'] != -1) {
            $where_a .= ' and ';//空格不能删除
            $where_b .= ' and ';
            $where_a .= " currency_id = " . $data['currency_id'];
            $where_b .= " m.currency_id = " . $data['currency_id'];
        }
        if (!empty($data['finance_type'])) {
            if ($data['finance_type'] != 0) {
                $where_a .= ' and ';//空格不能删除
                $where_b .= ' and ';
                $where_a .= " finance_type =" . $data['finance_type'];
                $where_b .= " m.finance_type = " . $data['finance_type'];
            }
        }
        if ($data['getout'] > 0) {
            $where_a .= " and type =" . $data['getout'];
            $where_b .= " and m.type =" . $data['getout'];
        }
        if ($data['add_time'] > 0) {
            $where_a .= " and add_time > " . $this->getQueryTime($data['add_time']);
            $where_b .= " and m.add_time > " . $this->getQueryTime($data['add_time']);
        }
        $where[0] = $where_a;
        $where[1] = $where_b;
        return $where;
    }

    /*
     * 根据传入的不同类型的uid确定sql中的数据表尾号
     * @author yangpeng 2017-10-12
     */
    protected function getMod($userid, $user_id, $name)
    {
        if ($userid) {
            $mod = $userid % 10;
        } else {
            if ($name) {
                $model = new \Back\Model\UserModel();
                $uid = $model->getUidByUname($name);     //根據用戶名獲取用戶id
                $mod = $uid % 10;
            } else {
                $mod = $user_id % 10;
            }
        }
        return $mod;
    }

    /**
     * 用户获得奖励
     * @author wangfuw
     * @time  17点00分
     */
    public function rewardLog()
    {
        if (IS_GET) {
//            	$this->DelLog("UserScoreLog",3);  //财务日志保留6个月
            $data = I('get.');
            /*1、接收表单数据,确定数据查询条件*/
            $where = $this->getWhereRewardByInput($data);//获取查询条件
        }
        /*2、存在管理员输入时，确定数据分表、实例化分页类并拼装查询sql*/
        $model = M();
        $Page_data = $this->getRewardPage($data, $where);

        $show = $Page_data['page']->show();// 分页显示输出
        $reward_list = $model->query($Page_data['sql']);
        /*3、格式化相关数据*/
        foreach ($reward_list as &$vo) {
            $vo['reward_type'] = formatRewardType($vo['reward_type']);//格式化类型
            $vo['type'] = formatType($vo['type']);//格式化来源类型
            $vo['from_id'] = D('User')->getUserNameByUid($vo['from_id']);
            $vo['color'] = 'green';
        }
        $currency = $this->getCurrencyList();

        /*6、模板赋值*/
        $this->assign('page', $show);
        //奖金来源
        $this->assign('type', getRewardType());
        $this->assign('reward_type', getRewardTypeList());
        $this->assign('currency', $currency);
        $this->assign('add_time', $this->getQueryTimeStr());
        $this->assign('reward_list', $reward_list);
        $this->display();

    }

    //奖金查询条件
    protected function getWhereRewardByInput($data)
    {
        $type = $data['type'];//判断是表单提交还是从用户列表链接过来的标识（1是本页面表单提交）
        $data['add_time'] = $data['add_time'] ? trim($data['add_time']) : 1;//默认查询过去一周的数据
        if (!$type) {
            $data['user_id'] = I('user_id') ? trim(I('user_id')) : NULL;  //通过用户列表带过来的uid
        }
        if ($data['userid']) {//如果用户输入表单uid，则只按uid查询
            $data['userid'] = trim($data['userid']);
            $where_a = "where user_id=" . $data['userid'];
            $where_b = "where m.user_id=" . $data['userid'];
        } else {
            if ($data['user_id']) {//通过用户列表带过来的uid
                $data['user_id'] = trim($data['user_id']);
                $where_a = "where user_id=" . $data['user_id'];
                $where_b = "where user_id=" . $data['user_id'];
            }
            if ($data['username']) {////同时存在用户名和用户id时，优先用户名进行搜索
                $name = trim(str_replace('+', '', $data['username']));//剔除'+'和去除空格
                $model = new \Back\Model\UserModel();
                $uid = $model->getUidByUname($name);     //根據用戶名獲取用戶id
                $this->assign('username', $name);
                if ($uid) {
                    $where_a = "where user_id=$uid ";
                    $where_b = "where m.user_id=$uid ";
                } else {
                    $where_a = ' where 1 != 1 ';
                    $where_b = ' where 1 != 1 ';
                }
            }
        }
        //用户名
        if (!$data['username'] && !$data['user_id'] && !$data['userid']) {//没有任何条件时
            $where_a = ' where 1 = 1 ';
            $where_b = ' where 1 = 1 ';
        }
        //币种id
        if (!empty($data['currency_id']) && $data['currency_id'] != -1) {
            $where_a .= ' and ';//空格不能删除
            $where_b .= ' and ';
            $where_a .= " currency_id = " . $data['currency_id'];
            $where_b .= " m.currency_id = " . $data['currency_id'];
        }

        //奖金矿机来源
        if (!empty($data['reward_type'])) {
            if ($data['reward_type'] != 0) {
                $where_a .= ' and ';//空格不能删除
                $where_b .= ' and ';
                $where_a .= " reward_type =" . $data['reward_type'];
                $where_b .= " m.reward_type = " . $data['reward_type'];
            }
        }
        //奖金来源类型
        if ($data['getout'] > 0) {
            $where_a .= " and type =" . $data['getout'];
            $where_b .= " and m.type =" . $data['getout'];
        }
        if ($data['add_time'] > 0) {
            $where_a .= " and add_time > " . $this->getQueryTime($data['add_time']);
            $where_b .= " and m.add_time > " . $this->getQueryTime($data['add_time']);
        }
        $where[0] = $where_a;
        $where[1] = $where_b;
        return $where;
    }

    //查询数据
    protected function getRewardPage($data, $where)
    {
        $model = M();
        if ($data['user_id'] || $data['userid'] || $data['name']) {
            $mod = $this->getMod($data['userid'], $data['user_id'], $data['username']);
            $countt = $model->query("SELECT count(*) as tcount from trade_reward_log$mod  $where[0]");
            $count = $countt[0]['tcount'];
            $Page = new \Back\Tools\Page($count, 15);// 实例化分页类 传入总记录数和每页显示的记录数(20)
            $sql = "SELECT m.*,n.username,p.currency_mark from trade_reward_log$mod as m left join trade_user as n on m.user_id=n.uid left join trade_currency as p on m.currency_id=p.id $where[1] ORDER BY m.add_time desc, m.id desc limit $Page->firstRow,$Page->listRows";
        } else {
            /*4、不存在管理员输入时，实例化分页类并拼装查询sql*/
            $count0 = $model->query("select count(*) as tcount from trade_reward_log0 $where[0]");
            $count1 = $model->query("select count(*) as tcount from trade_reward_log1 $where[0]");
            $count2 = $model->query("select count(*) as tcount from trade_reward_log2 $where[0]");
            $count3 = $model->query("select count(*) as tcount from trade_reward_log3 $where[0]");
            $count4 = $model->query("select count(*) as tcount from trade_reward_log4 $where[0]");
            $count5 = $model->query("select count(*) as tcount from trade_reward_log5 $where[0]");
            $count6 = $model->query("select count(*) as tcount from trade_reward_log6 $where[0]");
            $count7 = $model->query("select count(*) as tcount from trade_reward_log7 $where[0]");
            $count8 = $model->query("select count(*) as tcount from trade_reward_log8 $where[0]");
            $count9 = $model->query("select count(*) as tcount from trade_reward_log9 $where[0]");
            $count = $count0[0]['tcount'] + $count1[0]['tcount'] + $count2[0]['tcount'] + $count3[0]['tcount'] +
                $count4[0]['tcount'] + $count5[0]['tcount'] + $count6[0]['tcount'] + $count7[0]['tcount'] +
                $count8[0]['tcount'] + $count9[0]['tcount'];
            $Page = new \Back\Tools\Page($count, 15);// 实例化分页类 传入总记录数和每页显示的记录数(20)
            $sql = "SELECT m.*,n.username,p.currency_mark"
                . " from  (SELECT *  from trade_reward_log0 $where[0]
                union all SELECT *  from trade_reward_log1 $where[0]
                union all SELECT *  from trade_reward_log2 $where[0]
                union all SELECT *  from trade_reward_log3 $where[0]
                union all SELECT *  from trade_reward_log4 $where[0]
                union all SELECT *  from trade_reward_log5 $where[0]
                union all SELECT *  from trade_reward_log6 $where[0]
                union all SELECT *  from trade_reward_log7 $where[0]
                union all SELECT *  from trade_reward_log8 $where[0]
                union all SELECT *  from trade_reward_log9 $where[0]
                )as m "
                . "left join trade_user as n on m.user_id=n.uid "
                . "left join trade_currency as p on m.currency_id=p.id"
                . " ORDER BY m.add_time desc, m.id desc"
                . " limit $Page->firstRow,$Page->listRows";
        }
        $page['page'] = $Page;
        $page['sql'] = $sql;
        return $page;
    }

    //查看资金划转日志
    public function currencyToMoney()
    {
        $uid = I('get.uid');
        if ($uid) {
            $where['m.uid'] = intval(trim($uid));
        }
        $data = I('get.');
        $where = [];
        if ($data['uid']) {
            $where['m.uid'] = intval(trim($data['uid']));
        }
        if ($data['type']) {
            $where['m.type'] = intval(trim($data['type']));
        }
        if ($data['currency_id']) {
            $where['m.currency_id'] = intval(trim($data['currency_id']));
        }
        $count = M('CurrencyToMoney')->alias('m')->where($where)->count();
        $page = new Page($count, 15);
        $show = $page->show();
        $list = M('CurrencyToMoney')->alias('m')->where($where)
            ->join('LEFT JOIN __USER__ as u on u.uid = m.uid')
            ->field('*,u.username')
            ->order('m.add_time desc')
            ->limit($page->firstRow, $page->listRows)->select();
        $currency = $this->getCurrencyList();
        $this->assign('page', $show);
        $this->assign('currency', $currency);
        $this->assign('list', $list);
        $this->assign('typeArr', $this->toType());
        $this->display();
    }

    private function toType()
    {
        $arr = [
            '' => '===请选择划转类型===',
            '1' => '奖金账户转资金账户',
            '2' => '资金账户转奖金账户',
        ];
        return $arr;
    }

    /**
     * 统计
     */
    public function statistics()
    {
        $where = ['status' => CurrencyModel::STATUS_ENABLED];
        $list = M('Currency')->where($where)->select();
        $currencyList = [];
        foreach ($list as $v) {
            $currencyList[$v['id']] = $v['currency_name'];
        }
        $this->assign('currency_list', $currencyList);

        $where = [];
        $currencyId = I('get.currency_id', null);
        if ($currencyId > 0) {
            $where['um.currency_id'] = $currencyId;
        }

        $um = M('UserMoney')->getTableName();
        $u = M('User')->getTableName();
        $c = M('Currency')->getTableName();

        $data = M('UserMoney')->table($um.' um')->join($u.' u ON u.uid = um.uid', 'left')->join($c.' c ON c.id = um.currency_id', 'left')->where($where)->field([
            'SUM(um.num) AS num', 'SUM(um.forzen_num) AS forzen_num',
            'u.username', 'u.nickname', 'c.currency_name'
        ])->group('currency_id')->select();

        $this->assign('data', $data);

        $this->display();
    }

}
