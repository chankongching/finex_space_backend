/**
 * @desc 系統交易維護設置
 * @author 建强 2019年2月26日10:32:23
 */

$(function(){
	//修改交易模式的維護
	$(".flat_radio").click(function(){
	    editItem($(this));
	})
	//修改幣種 維護模式
	$(".flat_currency").click(function(){
	    editCurr($(this));
	})
	
 });
/**
 * @method 修改選項 交易模式
 * @param  _this
 * @return string 
 */
function editItem(_this){
	var val = _this.val();
	var text= (1==val)?'開啓':'關閉' ;
	var key = _this.attr('name');
	var type= $('#type_id').attr('type_id');
	
	var deal_order_text='';
	//發送請求判斷是否需要進行
	if((key=='deal_order' || key =='master_switch') && val==1){
		var deal_order_text = checkDealOrder(type);
	}
	layer.confirm(
   	"<span style='color:#fff'>"+deal_order_text+"確定"+text+"嗎？</span>", 
    {
        title:"*提示",
        btn: ["確定","取消"]
    }, 
    function()
    {  
	   $.ajax({
	        type: "POST",
	        url:  '/back/Maintain/editItem',
	        data: { 
	        	    'key': key,
	        	    'val': val,
	        	    'type':type
	              },
	        dataType: "json",
	        error: function(request){
	        	layer.msg('網絡繁忙,請稍後');
	        },
	        success: function(data) {
	        	layer.msg(data.msg);
	        	if(data.code==200) setTimeout(function(){location.reload();},1500);
	        }
	    });
     },
     //取消事件  重新reloading页面
     function(){ 
    	 location.reload();// 可以在这里刷新窗口
    	}
     );  
}
/***
 * 
 * @param   type
 * @returns text存在未处理订单描述
 */
function  checkDealOrder(type){
	var text_msg ='';
	$.ajax({
	        type: "POST",
	        url:  '/back/Maintain/checkDealOrder',
	        data: { 
	        	    'type':type
	              },
	        dataType: "json",
	        async:false, 
	        success: function(data) {
	        	if(data.code==200){
	        	 text_msg =  data.msg; //赋值全局变量
	        	}
	        }
	    });
	return text_msg;
}
/**
 * @method 幣種維護調整
 * @param _this
 * @returns string 
 */
function editCurr(_this){
	var val = _this.val();
	var text= (1==val)?'開啓':'關閉' ;
	var key = _this.attr('name');
	var id  = $('#curr_id').attr('curr_id');
	layer.confirm(
   	"<span style='color:#fff'>確定"+text+"嗎？</span>", 
    {
        title:"*提示",
        btn: ["確定","取消"]
    }, 
    function()
    {  
	   $.ajax({
	        type: "POST",
	        url:  '/back/Maintain/editCurr',
	        data: { 
	        	    'key': key,
	        	    'val': val,
	        	    'id' : id
	              },
	        dataType: "json",
	        error: function(request){
	        	layer.msg('網絡繁忙,請稍後');
	        },
	        success: function(data) {
	        	layer.msg(data.msg);
	        	if(data.code==200) setTimeout(function(){location.reload();},1500);
	        }
	    });
     },
     //取消事件  重新reloading页面
     function(){ 
    	 location.reload();// 可以在这里刷新窗口
    	}
     );  
}