var httpurl = "http://forum.eo.test/" ;
//var httpurl = "http://forum.enorange.cn/" ;
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
				html+= "<div id='forum'><div id='username'>"+data.username+"[发表于："+data.datatime+"]</div><div id='title' >"+data.title+"</div>";
				html+= "<div id='state' >状态:["+data.status+"]</div>";
				html+= "<div id='content'>"+data.content+"</div>";
				html+= "<div id='division'>-------------------------------------------------------------------------------</div><div id='reply'>";
				if(data.lastReplyUsername){
					html+= "<div id='replyname'>"+data.lastReplyUsername+"回复：</div><div id='replycontent'>"+data.lastReply+"</div></div></div>";
				} else {
					html+= "<div id='replycontent'>暂无回复！</div></div></div>";
				}
			}); 
			$('#service-forum-content').html(html);
		}
	});
});
