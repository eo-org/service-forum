<?php

class Rest_IndexController extends Zend_Controller_Action
{
	private $_tb;
	public function init()
	{
		$this->_tb = Class_Base::_('Post');
	}
	public function indexAction()
	{
		$callback = $this->getRequest()->getParam('callback');	
		$http =  md5($_SERVER["HTTP_REFERER"]);
		$pagesize = 20;
		$orgCode = Class_Server::getOrgCode();
		$selector = $this->_tb->select(false)
							  ->from($this->_tb,'*')
							  ->where('sort = ?',1)
							  ->where('isShow =?',1)
							  ->where('orgCode = ?',$orgCode)
							  ->where('md5httpurl = ?',$http)
							  ->order('id desc')
							  ->limitPage(0, $pagesize);
		$row = $this->_tb->fetchAll($selector)->toArray();
		$val = Zend_Json::encode($row);
		$this->getResponse()->appendBody($callback.'('.$val.')');
		
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout()->disableLayout();
	}
	
}