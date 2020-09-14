//触发键盘回车事件
$(document).keyup(function(event) {
	if(event.keyCode == 13)
	{
		// 提交登录表单方法
		$('#loginBtn').click(function(){
		    LogPwdForm();
		});
	}
});

//点击事件
$('#loginBtn').click(function(){
    LogPwdForm();
});


// 点击发送验证码开始倒计时
$('#loginphone').click(function(){
//	sendTong();
});

/* ---------------定义公用方法 ----------------- */
//通用发送短信ajax方法
function sendTong(){

	var name=$("#username").val();
	var pass=$("#password").val();
	if(name=='')
	{
		 layer.msg('用戶名不能為空');
		 return false;
	}
	if(pass=='')
	{
		layer.msg('密碼不能為空');
		 return false;
	}	
    //ajax部分
    $.ajax({
        type: "POST",
        url:  '/back/Login/ApiSendSms',
        data: { 'username': name,
                'password': pass
             },     //传入data变量
        dataType: "json",
        error: function(request) {
        },
        success: function(data) {
        	 layer.msg(data.info);
             if(data.status==200)
             {
            	 time($('#loginphone'));
             }
        }
    });
}
// 通用手机验证码倒计时方法
var wait = 120;
function time(o) {
    if(wait == 0) {
        o.removeAttr("disabled");
        o.text('重新發送');
        //设置倒计时秒数
        wait = 120;
    } else {
        o.attr("disabled", true);
        o.text("(" + wait + ")")
        wait--;
        setTimeout(function() {
            time(o)
        },
        1000);
    }
}

//切换验证码
function changeverifyError() 
{
    $("#verifyImg").attr("src", '/Back/Login/getVerify'+ "?" + Math.random());
}
// 提交登录表单方法
function LogPwdForm(){
	
	   var username =$("#username").val();    //用户名
       var password =$("#password").val();    //密码
       var code    =$("#code").val();         //图片验证码
       var phoneCode=$("#phone_code").val();  //短信验证码
       
	   if(username=='')
	   {
		   layer.msg('用戶名不能為空');
		   return false;
	   }
	   if(password=='')
	   {
		   layer.msg('密碼不能為空');
		   return false;
	   }
	   if(code=='')
	   {
		   layer.msg('圖片驗證碼不能為空');
		   return false;
	   }	   
	   if(phoneCode=='')
	   {
		   layer.msg('短信驗證碼不能為空');
		   return false;
	   }	   
	
    $.ajax({
        type: "POST",
        url: "/Back/Login/subLogin",
        data: {'username':username,'password':password,'code':code,'phoneCode':phoneCode},
        dataType: "json",
        success: function(data) {
            if(data.status == 200) 
            {
                 layer.msg(data.info);
				 if(data.status == 200) 
				 {   
					setTimeout(function(){window.location.href=data.result.url;},1500);
				 } 
            }
            else
            {
                layer.msg(data.info);
                //验证出错重置验证码
                changeverifyError() 
            }
        }
    });
}