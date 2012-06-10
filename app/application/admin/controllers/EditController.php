<?php
class Admin_EditController extends Zend_Controller_Action
{
	public function init()
	{
	}
	public function indexAction()
	{
		
	}
	
	public function editAction()
	{
		$orgCode = Class_Server::getOrgCode();
		$forumCo = App_Factory::_m('Forum');
		$forumDoc = $forumCo->addFilter("forumid", $orgCode)->fetchOne();
// 		var_export($forumDoc->toArray());exit;
		if($this->getRequest()->isPost()){
			$input = $this->getRequest()->getParams();
			foreach ($input as $num => $arrone){
				if($num != 'module' && $num != 'controller' && $num != 'action' && $num != 'button' && $num != 'id'){
					$arrin[$num] = $arrone;
				}
			}
			$arrin['forumid'] = $orgCode;
			$arrin['logincheck'] = !isset($arrin['logincheck'])?"off":"on";
			$arrin['avatarcheck'] = !isset($arrin['avatarcheck'])?"off":"on";
			$arrin['captchacheck'] = !isset($arrin['captchacheck'])?"off":"on";
			if(empty($forumDoc)){
				$forumDoc = $forumCo->create(); 
			}
			$forumDoc->setFromArray($arrin);
			$forumDoc->save();
			$this->_redirect('/'.Class_Server::getOrgCode().'/admin/index/index/');
		}
		$this->view->row = $forumDoc;	
	}
}
