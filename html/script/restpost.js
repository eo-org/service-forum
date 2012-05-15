//var httpurl = "http://forum.eo.test/" ;
var httpurl = "http://forum.enorange.cn/" ;
$(document).ready(function() { 
	var obj = $('#service-forum-content').attr('org-code');
	//console.log('run');
	$.ajax({
		dataType: "jsonp",
		url : httpurl+obj+"/rest/index/index/", 
		success : function(json) 
		{
			var html = '<ul>'; 
			$.each(json, function(k, data){
				//console.log(data.username);
				html+= "<li>"+data.username+"问："+data.title+"<br>";
				html+= "&nbsp;&nbsp;&nbsp;&nbsp;内容："+data.content;
				html+= "<br>&nbsp;&nbsp;&nbsp;&nbsp;最新回复：";
				if(data.lastReply){
					html+=data.lastReplyUsername+":"+data.lastReply;;
				}else{
					html+="暂无回复";
				}
			}); 
			html+= "</li></ul>"; 
			$('#service-forum-content').html(html);
		}
	});
}); 

