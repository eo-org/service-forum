<?php
class Admin_IndexController extends Zend_Controller_Action
{
	public function init()
	{
		$this->_tb = Class_Base::_('Post');
	}
	public function indexAction()
	{
		if(empty($_SESSION['orgCode'])){
			$orgCode = $this->getRequest()->getParam('orgCode');
			$_SESSION['orgCode'] = $orgCode;
		}
		$pathurl = "/admin/index/get-form-json/";
		$this->_helper->template->head('提问详情列表');
		$hashParam = $this->getRequest()->getParam('hashParam');
		$labels = array(
				'username' => '提问人',
				'title' => '标题',
				'num' => '回复记录',
				'isShow' => '是否显示',
				'~contextMenu' => '操作'
		);
		$partialHTML = $this->view->partial('select-search-header-front.phtml', array(
				'labels' => $labels,
				'selectFields' => array(
						'id' => null,
						'desc' => null,
						'isShow' => array(
								'1' => '显示',
								'0' => '隐藏'
								)
				),
				'url' => $pathurl,
				'actionId' => 'id',
				'click' => array(
						'action' => 'contextMenu',
						'menuItems' => array(
								array('回复','/admin/index/create/id/'),
								array('删除','/admin/index/del/id/')
						)
				),
				'initSelectRun' => 'true',
				'hashParam' => $hashParam
		));
		$this->view->partialHTML = $partialHTML;
		
	}
	
	public function getFormJsonAction()
	{
		$pageSize = 20;
		$selector = $this->_tb->select(false)->setIntegrityCheck(false)
							  ->from(array('a'=>'post'),array('id','username','title','isShow'))
							  ->joinLeft(array('b'=>'post'),"a.parentId = b.parentId and a.orgCode = b.orgCode and b.lastReply != ''",array('count(b.parentId) as num'))
							  ->where('a.sort = ?',1)
							  ->where('a.orgCode = ?',$_SESSION['orgCode'])
							  ->group('a.parentId')
							  ->order('a.id desc')
							  ->limitPage(1, $pageSize);
		$result = array();
		foreach($this->getRequest()->getParams() as $key => $value) {
			if(substr($key, 0 , 7) == 'filter_') {
				$field = substr($key, 7);
				switch($field) {
					case 'type':
						$selector->where('type like ?', '%'.$value.'%');
						break;
					case 'state':
						$selector->where('state like ?', '%'.$value.'%');
						break;
					case 'label':
						$selector->where('label like ?', '%'.$value.'%');
						break;
					case 'page':
						if(intval($value) == 0) {
							$value = 1;
						}
						$selector = $selector->limitPage(intval($value), $pageSize);
						$result['currentPage'] = intval($value);
						break;
				}
			}
		}
		$rowset = $this->_tb->fetchAll($selector)->toArray();
		$result['data'] = $rowset;
		//$result['dataSize'] = App_Func::count($selector);
		$result['pageSize'] = $pageSize;
	
		if(empty($result['currentPage'])) {
			$result['currentPage'] = 1;
		}
		return $this->_helper->json($result);
	}
	public function createAction()
	{
		$id = $this->getRequest()->getParam('id');
		$selector = $this->_tb->select(false)->setIntegrityCheck(false)
							  ->from(array('p'=>'post'),array('p.id','p.parentId','p.username','p.title','p.content','p.isshow','p.orgCode'))
							  ->joinLeft(array('o'=>'post'),"p.parentId = o.parentId and p.orgCode = o.orgCode",array('o.id as oid','o.lastReplyUsername','o.lastReply','p.lastDatatime'))
							  ->where('p.id = ?',$id);
		$row = $this->_tb->fetchAll($selector)->toArray();
		if($this->getRequest()->isPost()){
			$lastReplyUsername = $this->getRequest()->getParam('lastReplyUsername');
			$lastReply = $this->getRequest()->getParam('lastReply');
			if(!empty($row[0]['lastReply'])){
				$cousql = $this->_tb->select(false)
									->from($this->_tb,array('count(*) as num'))
									->where('parentId = ?',$row[0]['parentId'])
									->where('orgCode = ?',$row[0]['orgCode'])
									->group('parentId')
									->group('orgCode');
				$cou = $this->_tb->fetchRow($cousql)->toArray();
				$arrin = array(
						'parentid' => $row[0]['parentId'],
						'sort' => $cou['num']+1,
						'lastReplyUsername' => $row[0]['lastReplyUsername'],
						'lastReply' => $row[0]['lastReply'],
						'orgCode' => $row[0]['orgCode'],
						'lastDatatime' => $row[0]['lastDatatime']
				);
				$this->_tb->insert($arrin);
			}
			$datatime = date('Y-m-d H:i:s',time());
			$arrup = array(
					'lastReplyUsername' => $lastReplyUsername,
					'lastReply' => $lastReply,
					'lastDatatime' => $datatime
			);
			$where = 'id = '.$id;
			$this->_tb->update($arrup,$where);
			$this->_redirect('/admin/index/index/');
// 			var_export($arrup);
		}
		$this->view->row = $row;
		$this->view->id = $id;
	}
		
	public function editAction()
	{
		$id = $this->getRequest()->getParam('id');
		$title = $this->getRequest()->getParam('title');
		$content = $this->getRequest()->getParam('content');
		$isshow = $this->getRequest()->getParam('isshow');
		if(!empty($title)){
			$arrup = array(
					'title' => $title,
					'content' => $content,
					'isShow' => $isshow
					);
			$where = 'id = '.$id;
			$this->_tb->update($arrup,$where);
		}
		$row = $this->_tb->find($id)->current()->toArray();
		$post = "<div id='username' class='username' style='border:1px solid #000;width:480px;padding:5px;'>".$row['username']."问：".$row['title']."</div>";
		$post.= "<div id='messagecontent' class='messagecontent' style='border-left:1px solid #000; border-right:1px solid #000;border-bottom:1px solid #000;width:480px; padding:5px;'>";
		$post.= $row['content']."<div id='editdetail' style='width:50px;text-align:center;float:right;'><a href='#'>修改</a></div></div>";
		echo $post;
		exit;
	}
	
	public function delAction()
	{
		$id = $this->getRequest()->getParam('id');
		$row = $this->_tb->find($id)->current()->toArray();
		$where = 'id = '.$id;
		if(!empty($row)){
			$this->_tb->delete($where);
		}
		$this->_redirect('/admin/index/index/');
	}
	
	public function dellastAction()
	{
		$id = $this->getRequest()->getParam('id');
		$row = $this->_tb->find($id)->current()->toArray();
		$where = 'id = '.$id;
		if(!empty($row)){
			$row = $this->_tb->delete($where);
		}
		echo $row;
		exit;
	}
}
