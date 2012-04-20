function focus(obj) 
{
	//console.log('run');
	$.ajax({
		dataType: "jsonp",
		url : "http://forum.eo.test/rest/index/index/orgCode/"+obj, 
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
			$('#postdetail').html(html);
		}
	});/*
	var table = "<table width='95%' border='0' cellpadding='0' cellspacing='0'><tr>";
		table+= "<td height='35' colspan='2' align='center'>我要提问</td></tr>";
		table+= "<tr><td width='100px' height='35' align='right'>姓名：</td>";
		table+= "<td align='left'><input type='text' name='username' id='username' /><input type='hidden' id='orgcode' value='"+obj+"' /></td>";
		table+= "</tr><tr><td height='35' align='right' valign='top' >标题：</td>";
		table+= "<td align='left'><input type='text' name='title' id='title' size='35' /></td>";
		table+= "</tr><tr><td height='35' align='right' valign='top'>提问：</td><td align='left'>";
		table+= "<textarea name='content' id='content' cols='35' rows='3' style='width:300px;'></textarea></td></tr><tr>";
		table+= "<td height='35' colspan='2' align='center'><input type='button' name='button' id='submit' value='提交' /></td></tr></table>";
		$('#addpost').html(table);*/
} 

