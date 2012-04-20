<?php
require CONTAINER_PATH.'/app/application/default/forms/Page.php';
class IndexController extends Zend_Controller_Action
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
		$orgCode = $this->getRequest()->getParam('orgCode');
		$page = $this->getRequest()->getParam('page');
		$selector = $this->_tb->select(false)
							  ->from($this->_tb,'*')
							  ->where('sort = ?',1)
							  ->where('isShow =?',1)
							  ->where('orgCode = ?',$orgCode)
							  ->order('id desc')
							  ->limitPage($page, $pagesize);
		$row = $this->_tb->fetchAll($selector)->toArray();
		$selset = $this->_tb->select(false)
							  ->from($this->_tb,array('count(*) as num'))
							  ->where('sort = ?',1)
							  ->where('isShow =?',1)
							  ->where('orgCode = ?',$orgCode);
		$rowset = $this->_tb->fetchRow($selset)->toArray();
		$this->view->row = $row;
		$num = $rowset['num'];
		$this->view->orgCode = $orgCode;
		//Zend_Debug::dump($row);
		$this->view->pageshow = $this->_pagelist->getPage($page,$num,"/default/index/index/orgCode/".$orgCode,$pagesize);
	}
	
	public function addAction()
	{
		$orgCode = $this->getRequest()->getParam('orgCode');
		$username = $this->getRequest()->getParam('username');
		$title = $this->getRequest()->getParam('title');
		$content = $this->getRequest()->getParam('content');
		$selector = $this->_tb->select(false)
							  ->from($this->_tb,array('max(parentId) as num'))
							  ->where('sort = ?',1)
							  ->where('orgCode = ?',$orgCode);
		$row = $this->_tb->fetchRow($selector)->toArray();
		$arrin = array(
				'parentId' => $row['num']+1,
				'sort' => 1,
				'username' => $username,
				'title' => $title,
				'content' => $content,
				'orgCode' => $orgCode
				);
		$row = $this->_tb->insert($arrin);
		echo $row;
		exit;
	}
	
	public function detailAction()
	{
		$pagesize = 10;
		$id = $this->getRequest()->getParam('id');
		$page = $this->getRequest()->getParam('page');
		$row = $this->_tb->find($id)->current()->toArray();
		$numsql = $this->_tb->select(false)
							->from($this->_tb,'count(id) as num')
							->where('parentId = ?',$row['parentId']);
		$rownum = $this->_tb->fetchRow($numsql)->toArray();
		$selector = $this->_tb->select(false)->setIntegrityCheck(false)
							  ->from(array('a'=>'post'),array('content'))
							  ->joinLeft(array('b'=>'post'),"a.parentId = b.parentId and a.orgCode = b.orgCode ",array('sort','lastReplyUsername','lastReply'))
							  ->where('a.id = ?',$id)
							  ->order('b.id desc');
		$rowset = $this->_tb->fetchAll($selector)->toArray();
		$num = $rownum['num'];
		$this->view->row = $row;
		$this->view->rowset = $rowset;
		$this->view->pageshow = $this->_pagelist->getPage($page,$num,"/default/index/index/orgCode/".$row['orgCode'],$pagesize);
	}
	
	public function createAction()
	{
		//$callback = $this->getRequest()->getParam('callback');
		$orgCode = $this->getRequest()->getParam('orgCode');
		if($this->getRequest()->isPost()){
			$username = $this->getRequest()->getParam('username');
			$title = $this->getRequest()->getParam('title');
			$content = $this->getRequest()->getParam('content');
			$selector = $this->_tb->select(false)
								  ->from($this->_tb,array('max(parentId) as num'))
								  ->where('sort = ?',1)
								  ->where('orgCode = ?',$orgCode);
			$row = $this->_tb->fetchRow($selector)->toArray();
			$arrin = array(
					'parentId' => $row['num']+1,
					'sort' => 1,
					'username' => $username,
					'title' => $title,
					'content' => $content,
					'orgCode' => $orgCode
			);
			$row = $this->_tb->insert($arrin);
		}
		$this->view->orgCode = $orgCode;
// 		$row = Zend_Json::encode($row);
		
// 		$this->getResponse()->appendBody($callback.'('.$row.')');
// 		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout()->disableLayout();
	}
}