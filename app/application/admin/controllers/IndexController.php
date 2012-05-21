<?php
class Admin_IndexController extends Zend_Controller_Action
{
	public function init()
	{
		$this->_tb = Class_Base::_('Post');
	}
	public function indexAction()
	{
		$orgCode = Class_Server::getOrgCode();
		$hashParam = $this->getRequest()->getParam('hashParam');
		$labels = array(
				'username' => '提问人',
				'title' => '标题',
				'content' => '内容',
				'isShow' => '是否显示',
				'state' => '状态',
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
				'url' => "/".$orgCode."/admin/index/get-form-json/",
				'actionId' => 'id',
				'click' => array(
						'action' => 'contextMenu',
						'menuItems' => array(
								array('查看', '/'.$orgCode.'/admin/index/create/id/'),
								array('删除', '/'.$orgCode.'/admin/index/delete/state/1/id/')
						)
				),
				'initSelectRun' => 'true',
				'hashParam' => $hashParam
		));
		$this->view->partialHTML = $partialHTML;
		$this->_helper->template->head('提问详情列表');
	}
	
	public function createAction()
	{
		$id = $this->getRequest()->getParam('id');
		$orgCode = Class_Server::getOrgCode();
		$selector = $this->_tb->select(false)->setIntegrityCheck(false)
							  ->from(array('p'=>'post'),array('p.id','p.parentId','p.username','p.title','p.content','p.isshow','p.orgCode','p.state'))
							  ->joinLeft(array('o'=>'post'),"p.parentId = o.parentId and p.orgCode = o.orgCode and o.sort != 1",array('o.id as oid','o.lastReplyUsername','o.lastReply','p.lastDatatime'))
							  ->where('p.id = ?',$id)
							  ->where('p.orgCode = ?',$orgCode);
		$row = $this->_tb->fetchAll($selector)->toArray();
		if(!empty($row)){
			$csa = Class_Session_User::getInstance();
			$lastReplyUsername = $csa->getUserData('loginName');
			if($this->getRequest()->isPost()){
				//$lastReplyUsername = $this->getRequest()->getParam('lastReplyUsername');
				$lastReply = $this->getRequest()->getParam('lastReply');
				$cousql = $this->_tb->select(false)
									->from($this->_tb,array('max(sort) as num'))
									->where('parentId = ?',$row[0]['parentId'])
									->where('orgCode = ?',$row[0]['orgCode']);
				$cou = $this->_tb->fetchRow($cousql)->toArray();
				$datatime = date('Y-m-d H:i:s',time());
				$arrin = array(
						'parentId' => $row[0]['parentId'],
						'sort' => $cou['num']+1,
						'lastReplyUsername' => $lastReplyUsername,
						'lastReply' => $lastReply,
						'orgCode' => $orgCode,
						'lastDatatime' => $datatime
				);
				$this->_tb->insert($arrin);
				$arrup = array(
						'lastReplyUsername' => $lastReplyUsername,
						'lastReply' => $lastReply,
						'lastDatatime' => $datatime
				);
				$where = 'id = '.$id;
				$this->_tb->update($arrup,$where);
				$this->_helper->redirector()->gotoSimple('create');
				//$this->_forward('create','index','admin',array('id'=>$id));
			}
			$this->view->lastReplyUsername = $lastReplyUsername;
			$this->view->row = $row;
			$this->view->id = $id;
		} else {
			$this->view->state = 1;
		}
	}
		
	public function editAction()
	{
		$id = $this->getRequest()->getParam('id');
		$title = $this->getRequest()->getParam('title');
		$content = $this->getRequest()->getParam('content');
		$isshow = $this->getRequest()->getParam('isshow');
		$state = $this->getRequest()->getParam('state');
		if(!empty($title)){
			$arrup = array(
					'title' => $title,
					'content' => $content,
					'isShow' => $isshow,
					'state' => $state
				);
			$where = 'id = '.$id;
			$this->_tb->update($arrup,$where);
		}
		$row = $this->_tb->find($id)->current()->toArray();
		$this->view->row= $row;
		$post = $this->view->render('edit/edit.phtml');
		echo $post;
		exit;
	}
	
	public function getFormJsonAction()
	{
		$pageSize = 20;
		$selector = $this->_tb->select(false)
							  ->where('sort = ?',1)
							  ->where('orgCode = ?',Class_Server::getOrgCode())
							  ->order('id desc')
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
		$result['pageSize'] = $pageSize;
	
		if(empty($result['currentPage'])) {
			$result['currentPage'] = 1;
		}
		return $this->_helper->json($result);
	}
	
	public function deleteAction()
	{
		$id = $this->getRequest()->getParam('id');
		$state = $this->getRequest()->getParam('state');
		if($state == 1){
			$row = $this->_tb->find($id)->current();
			if(!empty($row)){
				$where = 'parentId = '.$row['parentId'];
				$row = $this->_tb->delete($where);
			}
			$this->_redirect('/'.Class_Server::getOrgCode().'/admin/index/index/');
		} else {
			$row = $this->_tb->find($id)->current()->toArray();
			$numsql = $this->_tb->select(false)
								->from($this->_tb,array('max(sort) as num'))
								->where('parentId = ?',$row['parentId'])
								->where('orgCode = ?',$row['orgCode']);
			$cou = $this->_tb->fetchRow($numsql)->toArray();
			$where = 'id = '.$id;
			$this->_tb->delete($where);
			if($row['sort'] == $cou['num']) {
				$selector = $this->_tb->select(false)
									  ->from($this->_tb,array('id','lastReplyUsername','lastReply','lastDatatime'))
									  ->where('parentId = ?',$row['parentId'])
									  ->order('sort Desc')
									  ->limit(1);
				$arrone = $this->_tb->fetchRow($selector)->toArray();
				$arrup = array(
						'lastReplyUsername' => $arrone['lastReplyUsername'],
						'lastReply' => $arrone['lastReply'],
						'lastDatatime' => $arrone['lastDatatime']
						);
				$where = 'id = '.$row['parentId'];
				$this->_tb->update($arrup,$where);
			}
			exit;
		}	
	}
}
