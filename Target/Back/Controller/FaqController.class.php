<?php
namespace Back\Controller;
use Back\Tools\Page;


/* *****************
 * FAQ标题列表控制器
 * @author fuwen
 * 2017年11月24日10:41:37
 *********************/
class FaqController extends BackBaseController{
    /*****************************
     * FAQ列表显示页面
     * @author fuwen
     * @Date   2017年11月24日10:44:26
     ******************************/
    public function showFaqList(){
        $model = M('FaqTitle');
        $count = $model->where(['pid'=>0])->count();
        $page = new Page($count,2);
        $list = $model->order('order_number asc')
            ->where(['pid'=>0])
            ->limit($page->firstRow,2)
            ->select();
        foreach($list as $key => $item) {
            $list[$key]['data'] = M('FaqTitle')
                ->where(['pid' => $item['id']])
                ->select();
        }
        $this->assign('list',$list);
        $this->assign('page',$page->show());
        $this->display();
    }
    /**
     * 排序方法
     */
    public function orderList(){
        $data = I('post.');
        $this->orderData($data);
        $this->success('排序成功',U('Back/Faq/showFaqList'));
    }

    private function orderData($data,$id='id',$order='order_number'){
        foreach ($data as $k => $v) {
            $v=empty($v) ? null : $v;
            M('FaqTitle')->where(array($id=>$k))->save(array($order=>$v));
        }
        return true;
    }
    /**
     * @ 1.选着相应的主标题添加二级标题。
     * @ 2.选着主标题时添加主标题
     */
    public function addFaqTitle(){
        if(IS_POST){
            $pid=I('pid');
//            $zh_cn_title = strip_tags(trim(I('zh-cn-title')));
//            $zh_tw_title = strip_tags(trim(I('zh-tw-title')));
            $en_us_title = strip_tags(trim(I('en-us-title')));
//            if(empty($zh_cn_title)){
//                  return $this->error('中文標題不能為空');
//            }
//            if(empty($zh_tw_title)){
//                  return $this->error('繁體標題不能為空');
//            }
            if(empty($en_us_title)){
                  return $this->error('英文標題不能為空');
            }
           
            $data = [
//                 'zh-cn-title' => $zh_cn_title,
//                 'zh-tw-title' => $zh_tw_title,
                 'en-us-title' => $en_us_title,
                 'add_time'    => $add_time = time(),
                 'pid'         => $pid,
            ];
            $res = M('FaqTitle')->add($data);
            if($res){
                return $this->success('添加成功',U('Faq/showFaqList'));
            }else{
                return $this->error('服務器繁忙，請稍後從試');
            }
       }
        $where['pid'] = array('eq',0);
        $list = M('FaqTitle')->where($where)->select();
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 修改FAQ标题
     * @author：fuwen
     * @date:2017-11-30 19:23:47
     */

    public function saveFaqTitle(){
        $id = I('id');
        $list = M('FaqTitle')->where(['id'=>$id])->find();
        if(IS_POST){
            $id = I('id');
//            $zh_cn_title = trim(I('zh-cn-title'));
//            $zh_tw_title = trim(I('zh-tw-title'));
            $en_us_title = trim(I('en-us-title'));
//            if(!empty($zh_cn_title)){
//                $data['zh-cn-title'] = $zh_cn_title;
//            }
//            if(!empty($zh_tw_title)){
//                $data['zh-tw-title']=$zh_tw_title;
//            }
            if(!empty($en_us_title)){
                $data['en-us-title'] = $en_us_title;
            }

            $res = M('FaqTitle')->where(['id'=>$id])->save($data);
            if($res){
                return $this->success('修改成功',U('Faq/showFaqList'));
            }else{
                return $this->error('服務器繁忙');
            }
        }
        $this->assign('list',$list);
        $this->display();
    }
    /**
     * 删除FAQ标题
     * @author：fuwen
     * @date:2017-11-30 19:23:47
     */
    public function delFaqTitle(){
        if(IS_AJAX){
            $id = I('id');
            $pid = M('FaqTitle')->where(['id' => $id])->find()['pid'];
            if ($pid > 0) {
                //删除二级标题之前先判断内容是否删掉title_id=id
                $content_list = M('FaqContent')->where(['title_id' => $id])->find();
                if (!$content_list) {
                    $res = M('FaqTitle')->where(['id' => $id])->delete();
                    if ($res) {
                        return $this->ajaxReturn(['status'=>200,'msg'=>'删除成功']);
                    } else {
                        return $this->ajaxReturn(['status'=>400,'msg'=>'刪除失敗']);
                    }
                } else {
                    return $this->ajaxReturn(['status'=>400,'msg'=>'該標題下存在內容，請先刪除內容']);
                }
            } elseif ($pid == 0) {
                //删除一级标题判断二级标题是否存在
                $ret = M('FaqTitle')->where(['pid' => $id])->field('id')->select();
                if (!$ret) {
                    $res = M('FaqTitle')->where(['id' => $id])->delete();
                    if ($res) {
                        return $this->ajaxReturn(['status'=>200,'msg'=>'刪除成功']);
                    } else {
                        return $this->ajaxReturn(['status'=>400,'msg'=>'刪除失敗']);
                    }
                } else {
                    return $this->ajaxReturn(['status'=>400,'msg'=>'該標題下有副標題，請先刪除對應的副標題']);
                }
            }
        }
    }

    /**
     * 查看修改FAQ内容
     * @author:fuwen
     * $list_title_info: 标题信息
     * $list_content_info： 该标题下内容信息
     * @date：2017-11-30 19:22:39
     */
    public function checkFaqTitleContent(){
        $id = I('id');
        $list_title_info = M('FaqTitle')->where(['id'=>$id])->find();
        $list_content_info = M('FaqContent')->where(['title_id'=>$id])->find();
        if(IS_POST){
            $id=I('id');
//            $zh_cn_content = strip_tags(trim(I('zh_cn_content')));
//            $zh_tw_content = strip_tags(trim(I('zh_tw_content')));
            $en_us_content = strip_tags(trim(I('en_us_content')));
//            if(!empty($zh_cn_content) ){
//                $data['zh-cn-content'] = $zh_cn_content;
//            }
//            if(!empty($zh_tw_content) ){
//                $data['zh-tw-content'] = $zh_tw_content;
//            }
            if(!empty($en_us_content)){
                $data['en-us-content'] = $en_us_content;
            }
            $res = M('FaqContent')->where(['title_id' => $id])->save($data);
            if($res){
                return $this->success('修改成功',U('Faq/showFaqList'));
            }else{
                return $this->error('修改失敗，請稍後重試');
            }
        }
        $this->assign('list_title',$list_title_info);
        $this->assign('list_content',$list_content_info);
        $this->display();
    }
}

