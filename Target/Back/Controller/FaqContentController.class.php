<?php
namespace Back\Controller;
use Back\Tools\Page;
/* *****************
 * FAQ内容列表控制器
 * @author fuwen
 * 2017年11月24日10:41:37
 *********************/
class FaqContentController extends BackBaseController{
    /*****************************
     * FAQ内容显示页面
     * @author fuwen
     * @Date   2017年11月24日10:44:26
     ******************************/
    public function showFaqContentList()
    {
        $where = $this->params('t');
        
        if(count($where)>0)
        {  
            $count=M('FaqTitle')
                   ->alias('t')
                   ->join('right join __FAQ_CONTENT__ as c  on c.title_id=t.id')
                   ->where($where)
                   ->count();
        	$Page = new Page($count,15);
        	$list =M('FaqTitle')
        	       ->alias('t')
        	       ->join('right join __FAQ_CONTENT__ as c  on c.title_id=t.id')
        	       ->where($where)
        	       ->field('t.`en-us-title`,t.id,t.pid,c.add_time,c.`en-us-content`as content,c.id as content_id')
        	       ->limit($Page->firstRow,15)
        	       ->order('c.add_time desc')
        	       ->select();
        }
        else 
        {
            $count=M('FaqTitle')
                    ->alias('t')
                    ->join('right join __FAQ_CONTENT__ as c  on c.title_id=t.id')
                    ->count();
            $Page  =new Page($count,15);
            $list = M('FaqTitle')
                    ->alias('t')
                    ->join('right join __FAQ_CONTENT__ as c  on c.title_id=t.id')
                    ->field('t.`en-us-title`,t.id,t.pid,c.add_time,c.`en-us-content`as content,c.id as content_id')
                    ->limit($Page->firstRow,15)
                    ->order('c.add_time desc')
                    ->select();
        }
        //获取所有一级标题
        $fist=M('FaqTitle')->field('`en-us-title` as  fist,id')->where('pid=0')->select();
        $fistTitle= array_column($fist, 'fist','id');
        //组装数据 获取顶级标题
        if(count($list)>0)
        {
           $list=$this->getFirstTitle($list);
        }

        foreach ($list as $k => &$v) {
            $list[$k]['zh_us_title'] = html_entity_decode($v['zh_us_title']);
            $list[$k]['content'] = strip_tags(html_entity_decode($v['content']));
        }

        $show = $Page->show();
        $this->assign('list',$list);
        $this->assign('faqTitleList',$fistTitle);
        $this->assign('Page',$show);
        $this->display();
    }
    
    /**
     * @method 获取顶级标题
     * @param  array $list
     * @return array 
     */
    protected function getFirstTitle($list)
    {
          if(count($list)<0) return $list;
          $where=[
              'id'=>['IN',  array_unique(array_filter(array_column($list, 'pid')))]
          ];
          $firstTitle = M('FaqTitle')->where($where)->field('id,pid,`en-us-title`')->select();
          $firstTitle = array_column($firstTitle, 'en-us-title','id');
          foreach ($list as $k=>$v) 
          {
              $list[$k]['fistTitle']=$firstTitle[$v['pid']];
          } 
         return $list;
    }

    /**
     * @return string 查询条件
     */
    private function params($alias)
    {
    	$where=[];
        $title = trim(I('title'));
        $id = intval(trim(I('type')));
        if($id>0)
        {
            $where[$alias.'.id']=['in',$this->getSecond($id)];
        }
        if(strlen($title)>0)
        {
        	$where[$alias.'.`en-us-title`']=array('like','%'.$title.'%');
        }
        return $where;
    }
    
    //获取二级标题id值
    protected function getSecond($id)
    {
    	$ids= M('FaqTitle')->where(['pid'=>$id])->field('id')->select();
    	return array_column($ids, 'id');
    }
    /**
     * 添加FAQ内容
     * @author fuwen
     * @Date   2017年11月24日10:44:26
     */
    public function addFaqContentList(){
       $id = I('id');
       $faq_title_info = M('FaqTitle')->where(['id'=>$id])->find();
       $faq_content_info = M('FaqContent')->where(['title_id'=>$id])->find();
       if(IS_POST){
//            $zh_cn_content = trim(I('zh_cn_content'));
//            $zh_tw_content = trim(I('zh_tw_content'));
            $en_us_content = trim(I('en_us_content'));
//            if(empty($zh_cn_content)){
//                 return $this->error('中文內容不能為空');
//            }
//            if(empty($zh_tw_content)){
//                 return $this->error('繁體標題不能為空');
//            }
            if(empty($en_us_content)){
                 return $this->error('英文標題不能為空');
            }
            $data = [
                'title_id'=>$id,
//                'zh-cn-content'=>$zh_cn_content,
//                'zh-tw-content'=>$zh_tw_content,
                'en-us-content'=>$en_us_content,
                'add_time'=>$add_time=time(),
            ];
            $res = M('FaqContent')->add($data);
            if($res){
                return $this->success('內容添加成功',U('Faq/showFaqList'));
            }else{
                return $this->error('服務器繁忙請稍後重試');
            }
       }
       $this->assign('list_title',$faq_title_info);
       $this->assign('list_content',$faq_content_info);
       $this->display();
    }

    /**修改FAQ内容
     * @author fuwen
     * @Date   2017年11月24日10:44:26
     */
    public function saveFaqContent(){
        $id = I('id');
        $list = M('FaqContent')->alias('c')
              ->join('__FAQ_TITLE__ as t on c.title_id=t.id')
              ->field('c.*,t.`zh-cn-title`,t.`zh-tw-title`,t.`en-us-title`')
              ->where(['c.id' => $id])
              ->find();
        if(IS_POST){
            $id=I('id');
//            $zh_cn_content = strip_tags(trim(I('zh_cn_content')));
//            $zh_tw_content = strip_tags(trim(I('zh_tw_content')));
            $en_us_content = strip_tags(trim(I('en_us_content')));
//            if(!empty($zh_cn_content)){
//                $data['zh-cn-content'] = $zh_cn_content;
//            }
//            if(!empty($zh_tw_content)){
//                $data['zh-tw-content'] = $zh_tw_content;
//            }
            if(!empty($en_us_content)){
                $data['en-us-content'] = $en_us_content;
            }
            $res = M('FaqContent')->where(['id' => $id])->save($data);
            if($res){
                return $this->success('修改成功',U('FaqContent/showFaqContentList'));
            }else{
                return $this->error('服務器繁忙');
            }
        }
        $this->assign('list',$list);
        $this->display(); 
    }

    /**删除FAQ内容
     * @author fuwen
     * @Date   2017年11月24日10:44:26
     */
    public function delFaqContent(){
        if(IS_AJAX){
            $id = I('id');
            if(empty($id) || !isset($id)){
                return $this->ajaxReturn(['status'=>400,'msg'=>'刪除失敗']);
            }else{
                $res = M('FaqContent')->where(['id'=>$id])->delete();
                if($res){
                return $this->ajaxReturn(['status'=>200,'msg'=>'删除成功']);
                }else{
                return $this->ajaxReturn(['status'=>400,'msg'=>'刪除失敗']);
                }
            }
        }
    }
}

