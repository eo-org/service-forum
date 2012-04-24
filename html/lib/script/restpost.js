$(document).ready(function() { 
	var obj = $('#service-forum-content').attr('org-code');
	//console.log('run');
	$.ajax({
		dataType: "jsonp",
		url : "http://forum.eo.test/rest/index/index/orgCode/"+obj, 
		success : function(json) 
		{
			var html = '<ul>'; 
			$.each(json, function(k, data){
				//console.log(data.username);
				html+= "<li><div id='title'>"+data.username+"问："+data.title+"</div>";
				html+= "<div id='content'>内容："+data.content+"</div>";
				html+= "<div id='lastreply'>最新回复：";
				if(data.lastReply){
					html+=data.lastReplyUsername+":"+data.lastReply;;
				}else{
					html+="暂无回复";
				}
			}); 
			html+= "</div></li></ul>"; 
			$('#service-forum-content').html(html);
		}
	});
}); 

