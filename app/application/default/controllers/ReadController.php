<?php
class ReadController extends Zend_Controller_Action
{
	private $_tb;
	public function init()
	{
		
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
		$orgCode = Class_Server::getOrgCode();
		$postCo = App_Factory::_m('Post');
		$row = $postCo->addFilter("md5httpurl",$http)->addFilter("isShow",'1')->sort('_id',-1)->fetchAll();
		foreach ($row as $num){
			$arrreturn[] = $num;
		}
		$val = Zend_Json::encode($arrreturn);
		$this->getResponse()->appendBody($callback.'('.$val.')');
		
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout()->disableLayout();
	}
	

	
}