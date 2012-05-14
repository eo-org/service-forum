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
		
		if(isset($_SERVER["HTTP_REFERER"])) {
			$http =  $_SERVER["HTTP_REFERER"];		
		} else {
			$http =  $_SERVER["HTTP_HOST"];
		}
		$state = $this->getRequest()->getParam('state');
		if($state == 1){
			$this->view->message = "提问成功！内容审核中···";
		} else if($state == 2) {
			$this->view->message = "名称、标题、内容不能为空！";
		}
		$orgCode = Class_Server::getOrgCode();
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
		$this->view->http = $http;
		$this->view->pageshow = $this->_pagelist->getPage($page,$num,"/default/index/index/orgCode/".$orgCode,$pagesize);
	}
	
	public function createThreadAction()
	{
		$orgCode = Class_Server::getOrgCode();
		$input = $this->getRequest()->getParams();
		if(!empty($input['username']) && !empty($input['title']) && !empty($input['content'])){
			$datatime = date('Y-m-d H:i:s',time());
			$tb = App_Factory::_('Post');
			$postRow = $tb->createRow();
			$postRow->setFromArray($input);
			
			$postRow->md5httpurl = md5($input['httpurl']);
			$postRow->datatime = $datatime;
			$postRow->sort = 1;
			$postRow->save();
			
			$postRow->parentId = $postRow->id;
			$postRow->save();
			$state = 1;
		} else {
			$state = 2;
		}
		$this->_forward('index','index','default',array('state'=>$state));
	}
	
	public function addAction()
	{
		$orgCode = $this->getRequest()->getParam('orgCode');
		$username = $this->getRequest()->getParam('username');
		$title = $this->getRequest()->getParam('title');
		$content = $this->getRequest()->getParam('content');
		$httpurl = $this->getRequest()->getParam('httpurl');
		$datatime = date('Y-m-d H:i:s',time());
		$selector = $this->_tb->select(false)
							  ->from($this->_tb,array('max(parentId) as num'))
							  ->where('sort = ?',1)
							  ->where('orgCode = ?',$orgCode);
		$row = $this->_tb->fetchRow($selector)->toArray();
		
		
		Zend_Debug::dump($row);
		die();
		
		
		$arrin = array(
				'parentId' => $row['num']+1,
				'sort' => 1,
				'username' => $username,
				'title' => $title,
				'content' => $content,
				'orgCode' => $orgCode,
				'httpurl' => $httpurl,
				'md5httpurl' => md5($httpurl),
				'datatime' => $datatime
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
	
	public function selAction()
	{
		$callback = $this->getRequest()->getParam('callback');
		$http =  $_SERVER["HTTP_REFERER"];
		$pagesize = 20;
		$orgCode = $this->getRequest()->getParam('orgCode');
		$page = $this->getRequest()->getParam('page');
		$selector = $this->_tb->select(false)
							  ->from($this->_tb,'*')
							  ->where('sort = ?',1)
							  ->where('isShow =?',1)
							  ->where('orgCode = ?',$orgCode)
							  ->where('httpurl = ?',$http)
							  ->order('id desc')
							  ->limitPage($page, $pagesize);
		$row = $this->_tb->fetchAll($selector)->toArray();
		$val = Zend_Json::encode($row);
		$this->getResponse()->appendBody($callback.'('.$val.')');
		
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout()->disableLayout();
	}
}