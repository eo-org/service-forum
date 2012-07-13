<?php
class Admin_IndexController extends Zend_Controller_Action
{
	public function init()
	{
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
				'status' => '状态',
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
								array('查看', '/'.$orgCode.'/admin/index/create/id/')
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
		$this->_helper->template->head('留言信息修改及回复');
		$id = $this->getRequest()->getParam('id');
		$orgCode = Class_Server::getOrgCode();
		$forumCo = App_Factory::_m('Forum');
		$forumDoc = $forumCo->addFilter("forumid", $orgCode)->fetchOne();
		$setrow = empty($forumDoc)?$forumDoc:$forumDoc->toArray();
		$postCo = App_Factory::_m('Post');
		$row = $postCo->addFilter("parentId", $id)->sort('_id',1)->fetchAll();
		foreach ($row as $num ){
			$viewrow[] = $num;
		}
		if(!empty($viewrow)){
			$csa = Class_Session_User::getInstance();
			$lastReplyUsername = $csa->getUserData('loginName');
			if($this->getRequest()->isPost()){
				//$lastReplyUsername = $this->getRequest()->getParam('lastReplyUsername');
				$lastReply = $this->getRequest()->getParam('lastReply');
				$datatime = date('Y-m-d H:i:s',time());
				$arrdata = array(
						'lastReplyUsername' => $lastReplyUsername,
						'lastReply' => $lastReply,
						'lastDatatime' => $datatime,
						'parentId' => $id
						);
				$postDoc = $postCo->find($id);
				$postDoc->setFromArray($arrdata);
				$postDoc->save();
				$postInDoc = $postCo->create();
				$i = end($viewrow);
				$arrdata['sort'] = $i['sort']+1;
				$postInDoc->setFromArray($arrdata);
				$postInDoc->save();
				$this->_helper->redirector()->gotoSimple('create');
			}
			$this->view->lastReplyUsername = $lastReplyUsername;
			$this->view->row = $viewrow;
			$this->view->id = $id;
		} else {
			$this->view->state = 1;
		}
		$this->_helper->template->actionMenu(array('delete'));
		$this->view->setrow = $setrow;
	}
		
	public function editAction()
	{
		
		$orgCode = Class_Server::getOrgCode();
		$forumCo = App_Factory::_m('Forum');
		$forumDoc = $forumCo->addFilter("forumid", $orgCode)->fetchOne();
		$this->view->setrow = empty($forumDoc)?$forumDoc:$forumDoc->toArray();
		$id = $this->getRequest()->getParam('id');
		$title = $this->getRequest()->getParam('title');
		$content = $this->getRequest()->getParam('content');
		$isshow = $this->getRequest()->getParam('isshow');
		$status = $this->getRequest()->getParam('status');
		$postCo = App_Factory::_m('Post');
		$postDoc = $postCo->find($id);
		if(!empty($title)){
			$arrup = array(
					'title' => $title,
					'content' => $content,
					'isShow' => $isshow,
					'status' => $status
				);
			$postDoc->setFromArray($arrup);
			$postDoc->save();
		}
		$postDoc = $postCo->find($id);
		$this->view->row= $postDoc->toArray();
		$post = $this->view->render('edit/editpost.phtml');
		echo $post;
		exit;
	}
	
	public function deleteAction()
	{
		$id = $this->getRequest()->getParam('id');
		$state = $this->getRequest()->getParam('state');
		$postCo = App_Factory::_m('Post');
		if($state == 2){
			$postDoc = $postCo->find($id);
			$postid = $postDoc->toArray();
			$postrow = $postCo->find($postid['parentId']);
			$row = $postrow->toArray();
			$postDoc->delete();
			if($postid['lastReply'] == $row['lastReply']){
				$forumDoc = $postCo->addFilter("parentId", $postid['parentId'])->sort('sort',-1)->fetchAll();
				foreach ($forumDoc as $num => $arrone){
					if($arrone['sort']==1){
						$arrup = array(
								'lastReplyUsername' => '',
								'lastReply' => '',
								'lastDatatime' => ''
						);
					}else{
						$arrup = array(
								'lastReplyUsername' => $arrone['lastReplyUsername'],
								'lastReply' => $arrone['lastReply'],
								'lastDatatime' => $arrone['lastDatatime']
								);
					}
					break;		
				}
				$postrow->setFromArray($arrup);
				$postrow->save();
				exit;
			}
			
		} else {
			$postDoc = $postCo->addFilter("parentId", $id)->fetchAll();
			foreach ($postDoc as $num){
				$optionDoc = $postCo->find($num['_id']);
				$optionDoc->delete();
			}
			$this->_redirect('/'.Class_Server::getOrgCode().'/admin/index/index/');
		}
		exit;
	}
	
	public function getFormJsonAction()
	{
		$pageSize = 5;
		$postCo = App_Factory::_m('Post');
		$postCo->addFilter("orgCode", Class_Server::getOrgCode());
		$postCo->sort('_id', -1);
		$result = array();
		foreach($this->getRequest()->getParams() as $key => $value) {
			if(substr($key, 0 , 7) == 'filter_') {
				$field = substr($key, 7);
				switch($field) {
					case 'type':
						$postCo->addFilter('formName', new MongoRegex("/^".$value."/"));
						break;
					case 'page':
						if(intval($value) != 0) {
    						$currentPage = $value;
    					}
    					$result['currentPage'] = intval($value);
						break;
				}
			}
		}
		$postCo->setPage($currentPage)->setPageSize($pageSize);
		$data = $postCo->fetchAll(true);
		$dataSize = $postCo->count();
		$result['data'] = $data;
		$result['dataSize'] = $dataSize;
		$result['pageSize'] = $pageSize;
		$result['currentPage'] = $currentPage;
		
		return $this->_helper->json($result);
	}
}
