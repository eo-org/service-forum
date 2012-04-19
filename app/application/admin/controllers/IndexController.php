<?php
require CONTAINER_PATH.'/app/application/forms/Page.php';
class Admin_IndexController extends Zend_Controller_Action
{
	public function init()
	{
		$this->_tb = Class_Base::_('Post');
		$this->_pagelist = new Form_Page();
	}
	public function indexAction()
	{
		$orgCode = $this->getRequest()->getParam('orgCode');
		$pathurl = "/admin/index/get-form-json/orgCode/".$orgCode.'/';
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
								array('编辑','/admin/index/edit/id/')
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
		$orgCode = $this->getRequest()->getParam('orgCode');
		$selector = $this->_tb->select(false)->setIntegrityCheck(false)
							  ->from(array('a'=>'post'),array('id','username','title','isShow'))
							  ->joinLeft(array('b'=>'post'),"a.parentId = b.parentId and a.orgCode = b.orgCode and b.lastReply != ''",array('count(b.parentId) as num'))
							  ->where('a.sort = ?',1)
							  ->where('a.orgCode = ?',$orgCode)
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
		if($this->getRequest()->isPost()){
			$lastReplyUsername = $this->getRequest()->getParam('lastReplyUsername');
			$lastReply = $this->getRequest()->getParam('lastReply');
			$selector = $this->_tb->select(false)
								  ->from($this->_tb,'*')
								  ->where('id = ?',$id);
			$row = $this->_tb->fetchRow($selector)->toArray();
			if(!empty($row['lastReply'])){
				$cousql = $this->_tb->select(false)
									->from($this->_tb,array('count(*) as num'))
									->where('parentId = ?',$row['parentId'])
									->where('orgCode = ?',$row['orgCode'])
									->group('parentId')
									->group('orgCode');
				$cou = $this->_tb->fetchRow($cousql)->toArray();
				$arrin = array(
						'parentid' => $row['parentId'],
						'sort' => $cou['num']+1,
						'lastReplyUsername' => $row['lastReplyUsername'],
						'lastReply' => $row['lastReply'],
						'orgCode' => $row['orgCode']
				);
				$this->_tb->insert($arrin);
			}
			$arrup = array(
					'lastReplyUsername' => $lastReplyUsername,
					'lastReply' => $lastReply
			);
			$where = 'id = '.$id;
			$this->_tb->update($arrup,$where);
			$this->_redirect('/admin/index/index/orgCode/'.$row['orgCode']);
				
		}
		$this->view->id = $id;
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
					'isShow' => $showid
					);
			$where = 'id = '.$id;
			$this->_tb->update($arrup,$where);
			$this->_redirect('/admin/index/index/orgCode/'.$row['orgCode']);
		}
		$this->view->row = $row;
	}
}
