var httpurl = 'http://forum.eo.test/';
$(document).ready(function() { 
	var obj = $('#service-forum-content').attr('org-code');
	var parameter = eval($('#service-forum-content').attr('data'));
	var val = obj+'/pagesize/'+parameter[0]['pagesize'];
	$.ajax({
		dataType: "jsonp",
		url : httpurl+"rest/index/index/orgCode/"+val, 
		success : function(json) 
		{
			var html = '<ul>'; 
			$.each(json, function(k, data){
				html+= "<li><div id='title'>"+data.username+"问："+data.title+"("+data.datatime+")</div>";
				if(parameter[0]['showcontent'] ==1){
					html+= "<div id='content'>内容："+data.content+"</div>";
					if(parameter[0]['lastshow'] == 1){
						html+= "<div id='lastreply'>最新回复：";
						if(data.lastReply){
							html+=data.lastReplyUsername+":"+data.lastReply+"("+data.lastDatatime+")";
						}else{
							html+="暂无回复";
						}
					}
					if(parameter[0]['expand'] == 1){
							html+= "<div id='detail' class='"+data.parentId+"' state='1'><a href='#'>展开</a></div>";
					} 
					html+= "<div id='detailreply"+data.parentId+"'></div></div></li><br>";
				}
			}); 
			html+= "</ul>"; 
			$('#service-forum-content').html(html);
		}
	});
}); 

$('#detail').live("click",function(){
	var obj = $(this).attr('class');
	var state = $(this).attr('state');
	var aa = "";
	var bb = "<a href='#'>展开</a>";
	if(state == 1){
		$.ajax({
			dataType: "jsonp",
			url : httpurl+"rest/index/detail/id/"+obj, 
			success : function(json) 
			{
				$.each(json, function(k, data){
					if(data.lastReply){
						aa+= "<div id='reply'>"+data.lastReplyUsername+":"+data.lastReply+"("+data.lastDatatime+")</div>"; 
					}else{
						aa+="暂无回复";
					}
					
				});
				$('#detailreply'+obj).html(aa);
			}
		});
		bb = "<a href='#'>关闭</a>"; 
		state = 0;
	}else{
		state = 1;
		$('#detailreply'+obj).html(aa);
	}
	$('.'+obj).attr("state",state);
	$('.'+obj).html(bb);
	return false;
	
});
