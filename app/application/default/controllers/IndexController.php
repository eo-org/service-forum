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
		
		if(isset($_SERVER["HTTP_REFERER"])) {
			$http =  $_SERVER["HTTP_REFERER"];		
		} else {
			$http =  $_SERVER["HTTP_HOST"];
		}
		$http = parse_url($http,PHP_URL_PATH).parse_url($http,PHP_URL_QUERY).parse_url($http,PHP_URL_FRAGMENT);
		$orgCode = Class_Server::getOrgCode();
		if($this->getRequest()->isPost()){
			$input = $this->getRequest()->getParams();
			foreach ($input as $num => $arrone){
				if($num != 'module' && $num != 'controller' && $num != 'action' && $num != 'submit'){
					$arrin[$num] = $arrone;
				}
			}
			if(!empty($arrin['captcha']) && $arrin['captcha'] == $_SESSION['code']['code']){
				if(!empty($arrin['username']) && !empty($arrin['title']) && !empty($arrin['content'])){
					$postCo = App_Factory::_m('Post');
					$row = $postCo->addFilter("username", $arrin['username'])->addFilter("title", $arrin['title'])->addFilter("content", $arrin['content'])->fetchOne();
					if(!empty($row)){
						$this->view->message = "对不起,不能重复提交。";
					}else{	
						$datatime = date('Y-m-d H:i:s',time());
						$tb = App_Factory::_m('Post');
						$postRow = $tb->create();
						$postRow->setFromArray($arrin);
						$http = $arrin['httpurl'];
						$postRow->md5httpurl = md5($arrin['httpurl']);
						$postRow->datatime = $datatime;
						$postRow->sort = 1;
						$postRow->isShow = 0;
						$postRow->status = '未处理';
						$postRow->save();
						$postRow->getId();
							
						$postRow->parentId = $postRow->getId();
						$postRow->save();
						$this->view->message = "提问成功！内容审核中···";
					}
				} else {
					$this->view->message = "名称、标题、内容不能为空！";
				}
			} else {
				$this->view->message = "验证码错误！";
			}
		}
		$this->view->http = $http;
		$this->view->captcha = $this->captchaAction();
// 		echo $this->view->captcha;
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
		//$this->_forward('index','index','default',array('state'=>$state));
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
	public function captchaAction()
	{
		$type = $this->getRequest()->getParam('type');
		$this->codeSession = new Zend_Session_Namespace('code'); //在默认构造函数里实例化	
		$captcha = new Zend_Captcha_Image(array(
				'font'=>'../html/images/simhei.ttf', //字体文件路径
				'fontsize'=>24, //字号
				'imgdir'=>'../html/images/', //验证码图片存放位置
				'session'=>$this->codeSession, //验证码session值
				'width'=>120, //图片宽
				'height'=>50,   //图片高
				'wordlen'=>5 )); //字母数
		 
		$captcha->setGcFreq(3); //设置删除生成的旧的验证码图片的随机几率
		$captcha->generate(); //生成图片
// 		$this->view->ImgDir = $captcha->getImgDir();
		$this->view->captchaId = $captcha->getId(); //获取文件名，md5编码
		$this->codeSession->code=$captcha->getWord(); //获取当前生成的验证字符串
		$this->view->ImgDir = '/images/';
		if($type == 1){
			echo $this->view->ImgDir.$this->view->captchaId.".png";
			exit;
		} else {
			return $this->view->ImgDir.$this->view->captchaId.".png";
		}
// 		echo $this->codeSession->code;
// 		echo $this->view->ImgDir,$this->view->captchaId;	
	}
}
