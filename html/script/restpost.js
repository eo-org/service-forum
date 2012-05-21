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
				html+= "<div id='forum'><div id='title' >"+data.username+"[发表于："+data.datatime+"]"+data.title;
				html+= "<div id='state' >状态:["+data.status+"]</div></div>";
				html+= "<div id='content' style='BACKGROUND:transprant;overflow:auto;border:1px solid #000;font-size:16px;float:left;margin-left:5px;margin-top:5px;'>"+data.content+"</div></div>";
			}); 
			$('#service-forum-content').html(html);
		}
	});
});
