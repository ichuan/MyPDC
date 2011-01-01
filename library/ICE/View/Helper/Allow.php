<?php
/**
 * ICE_Acl view helper
 *
 * @author yc <iyanchuan@gmail.com>
 */

class ICE_View_Helper_Allow extends Zend_View_Helper_Abstract 
{
	public function allow($action, $controller = null) 
	{
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			return false;
		}
		$user = Zend_Auth::getInstance()->getIdentity();
		$action = strtolower($action);
		if (null == $controller) {
			$controller = Zend_Controller_Front::getInstance()->getRequest()->getControllerName();
		}
        return ICE_Acl::allowed($user->role, $controller, $action);
	}
}
