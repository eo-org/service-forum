//var httpurl = "http://forum.eo.test/" ;
var httpurl = "http://forum.enorange.cn/" ;
$(document).ready(function() { 
	orgcode = $('#service-forum-content').attr('org-code');
	pagesize = $('#service-forum-content').attr('page-size');
	//alert(page);
	$.ajax({
		dataType: "jsonp",
		url : httpurl+orgcode+"/default/read/reply", 
		success : function(json) 
		{
			var html = ""; 
			$.each(json, function(k, data){
				html+= "<div id='forum'><div id='username'><strong>";
				//html+= "<img src='"+httpurl+"/images/avatar/"+data.avatar+".gif' />"+data.username+"</strong>&nbsp;&nbsp;&nbsp;&nbsp;[&nbsp;发表于："+data.datatime+"&nbsp;]</div>";
				html+= data.username+"</strong>&nbsp;&nbsp;&nbsp;&nbsp;[&nbsp;发表于："+data.datatime+"&nbsp;]</div>";
				html+= "<div id='state' >状态：[&nbsp;"+data.status+"&nbsp;]</div>";
				html+= "<div id='title' >标题：&nbsp;&nbsp;&nbsp;&nbsp;"+data.title+"</div>";
				html+= "<div id='content'>"+data.content+"</div>";
				if(data.lastReplyUsername){
					html+= "<div id='replyname'><strong>"+data.lastReplyUsername+"回复：</strong></div><div id='replycontent'>"+data.lastReply+"</div></div>";
				} else {
					html+= "<div id='replycontent'>暂无回复！</div></div>";
				}
			}); 
			$('#service-forum-content').html(html);
		}
	});
});
