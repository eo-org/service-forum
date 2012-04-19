<?php
require CONTAINER_PATH.'/app/application/default/forms/Page.php';
class PostController extends Zend_Controller_Action
{
	private $_tb;
	private $_pagelist;
	public function init()
	{
		$this->_tb = Class_Base::_('Post');
		$this->_pagelist = new Form_Page();
	}
	public function indexAction()
	{
		$pagesize = 10;
		$orgcode = $this->getRequest()->getParam('orgcode');
		$page = $this->getRequest()->getParam('page');
		$selector = $this->_tb->select(false)
							  ->from($this->_tb,'*')
							  ->where('sort = ?',1)
							  ->where('isshow =?',1)
							  ->where('orgCode = ?',$orgcode)
							  ->order('id desc')
							  ->limitPage($page, $pagesize);
		$row = $this->_tb->fetchAll($selector)->toArray();
		$selset = $this->_tb->select(false)
							  ->from($this->_tb,array('count(*) as num'))
							  ->where('sort = ?',1)
							  ->where('isshow =?',1)
							  ->where('orgCode = ?',$orgcode);
		$rowset = $this->_tb->fetchRow($selset)->toArray();
		$this->view->row = $row;
		$num = $rowset['num'];
		$this->view->orgcode = $orgcode;
		$this->view->pageshow = $this->_pagelist->getPage($page,$num,"/default/post/index/orgcode/".$orgcode,$pagesize);
	}

	public function createAction()
	{
		$id = $this->getRequest()->getParam('id');
		if($this->getRequest()->isPost()){
			$username = $this->getRequest()->getParam('username');
			$lestreily = $this->getRequest()->getParam('lestreily');
			$selector = $this->_tb->select(false)
								  ->from($this->_tb,'*')
								  ->where('id = ?',$id);
			$row = $this->_tb->fetchRow($selector)->toArray();
			if(!empty($row['repContent'])){
				$cousql = $this->_tb->select(false)
									  ->from($this->_tb,array('count(*) as num'))
									  ->where('parentId = ?',$row['parentId'])
									  ->where('orgCode = ?',$row['orgCode'])
									  ->group('parentId')
									  ->group('orgCode');
				$cou = $this->_tb->fetchRow($cousql)->toArray();
				$arrin = array(
						'parentid' => $row['parentId'],
						'sortid' => $cou['num']+1,
						'lestreily' => $row['repContent'],
						'username' => $row['username'],
						'orgcode' => $row['orgCode']
						);
				$this->_tb->insert($arrin);
			}
			$arrup = array(
					'repContent' => $lestreily,
					'username' => $username
					);
			$where = 'id = '.$id;
			$this->_tb->update($arrup,$where);
			$this->_redirect('/default/post/admin/orgcode/'.$row['orgCode']);
			
		}
		$this->view->id = $id;
	}

	public function adminAction()
	{
		$orgcode = $this->getRequest()->getParam('orgcode');
		$pathurl = "/default/post/get-form-json/orgcode/".$orgcode.'/';
		$this->_helper->template->head('提问详情列表');
		$hashParam = $this->getRequest()->getParam('hashParam');
		$labels = array(
				'question' => '提问人',
				'content' => '问题内容',
				'num' => '回复记录',
				'isshow' => '是否显示',
				'~contextMenu' => '操作'
		);
		$partialHTML = $this->view->partial('select-search-header-front.phtml', array(
				'labels' => $labels,
				'selectFields' => array(
						'id' => null,
						'desc' => null,
						'isshow' => array(
								'1' => '显示',
								'0' => '隐藏'
								)
				),
				'url' => $pathurl,
				'actionId' => 'id',
				'click' => array(
						'action' => 'contextMenu',
						'menuItems' => array(
								array('回复','/default/post/create/id/'),
								array('编辑','/default/post/edit/id/')
						)
				),
				'initSelectRun' => 'true',
				'hashParam' => $hashParam
		));
		$this->view->partialHTML = $partialHTML;
		$this->_helper->template->actionMenu(array(
				array('label' => '前台页面', 'href' => '/pm/detail/create/', 'method' => 'CreateDetail')));
		
	}
	
	public function getFormJsonAction()
	{
		$pageSize = 20;
		$orgcode = $this->getRequest()->getParam('orgcode');
		$selector = $this->_tb->select(false)->setIntegrityCheck(false)
							  ->from(array('a'=>'post'),array('id','question','content','isshow'))
							  ->joinLeft(array('b'=>'post'),"a.parentId = b.parentId and a.orgCode = b.orgCode and b.repContent != ''",array('count(b.parentId) as num'))
							  ->where('a.sort = ?',1)
							  ->where('a.orgCode = ?',$orgcode)
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
		$result['pageSize'] = $pageSize;
	
		if(empty($result['currentPage'])) {
			$result['currentPage'] = 1;
		}
		return $this->_helper->json($result);
	}
	
	public function selpostAction()
	{
		$id = $this->getRequest()->getParam('id');
		$selector = $this->_tb->select(false)->setIntegrityCheck(false)
							  ->from(array('a'=>'post'),array('content'))
							  ->joinLeft(array('b'=>'post'),"a.parentId = b.parentId and a.orgCode = b.orgCode and b.sort > 1",array('id','repContent','username'))
							  ->where('a.id = ?',$id)
							  ->order('b.id desc');
		$row = $this->_tb->fetchAll($selector)->toArray();
		$html = "<div id='html".$id."'>";
		if(!empty($row[0]['repContent'])){
			foreach($row as $num => $arrone){
				$html.= "<div style='border:1px solid #000;width:480px;padding:5px;'>".$arrone['username']."答：".$arrone['repContent']."</div>";
			}
		}
		$html.= "</div>";
		echo $html;exit;
	}
	
	public function addAction()
	{
		$orgcode = $this->getRequest()->getParam('orgcode');
		$question = $this->getRequest()->getParam('question');
		$issubject = $this->getRequest()->getParam('issubject');
		$selector = $this->_tb->select(false)
							  ->from($this->_tb,array('max(parentId) as num'))
							  ->where('sort = ?',1)
							  ->where('orgCode = ?',$orgcode);
		$row = $this->_tb->fetchRow($selector)->toArray();
		$arrin = array(
				'parentId' => $row['num']+1,
				'sort' => 1,
				'question' => $question,
				'content' => $issubject,
				'orgCode' => $orgcode
				);
		
		$this->_tb->insert($arrin);
		exit;
	}
	
	public function editAction()
	{
		$id = $this->getRequest()->getParam('id');
		$row = $this->_tb->find($id)->current()->toArray();
		if($this->getRequest()->isPost()){
			$show = $this->getRequest()->getParam('showid');
			$issubject = $this->getRequest()->getParam('issubject');
			$showid = 0;
			if($show == '显示'){
				$showid = 1;
			}
			$arrup = array(
					'content' => $issubject,
					'isshow' => $showid
					);
			$where = 'id = '.$id;
			$this->_tb->update($arrup,$where);
			$this->_redirect('/default/post/admin/orgcode/'.$row['orgCode']);
		}
		$this->view->row = $row;
	}
}