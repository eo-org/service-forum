<?php
class ReadController extends Zend_Controller_Action
{
	private $_tb;
	public function init()
	{
		$this->_tb = Class_Base::_('Post');
		
	}
	
	public function replyAction()
	{	
		$callback = $this->getRequest()->getParam('callback');	
		if(isset($_SERVER["HTTP_REFERER"])) {
			$http =  $_SERVER["HTTP_REFERER"];
		} else {
			$http =  $_SERVER["HTTP_HOST"];
		}
		$http = parse_url($http,PHP_URL_PATH).parse_url($http,PHP_URL_QUERY).parse_url($http,PHP_URL_FRAGMENT);
		$http = md5($http);
		$page = 1;
		$pagesize = 10;
		$orgCode = Class_Server::getOrgCode();		
		$selector = $this->_tb->select(false)
							  ->from($this->_tb,'*')
							  ->where('sort = ?',1)
							  ->where('isShow =?',1)
							  ->where('orgCode = ?',$orgCode)
							  //->where('md5httpurl = ?',$http)
							  ->order('id desc')
							  ->limitPage($page, $pagesize);
		$row = $this->_tb->fetchAll($selector)->toArray();
		$val = Zend_Json::encode($row);
		$this->getResponse()->appendBody($callback.'('.$val.')');
		
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout()->disableLayout();
	}
	

	
}