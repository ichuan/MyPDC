<?php
/**
* auth plugin
*
* @author yc <iyanchuan@gmail.com>
*/

require_once 'Zend/Controller/Plugin/Abstract.php';


class ICE_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
    /**
     * before dispatch loop start, authenticate the user
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        if (!$request instanceof Zend_Controller_Request_Http) {
            return;
        }
        $controller = $request->getControllerName();
        $action 	= $request->getActionName();
        $isMember = Zend_Auth::getInstance()->hasIdentity();
        $role = $isMember ? Zend_Auth::getInstance()->getIdentity()->role : 'guest';
        // 最基础的角色权限判断（基于 Controller 和 Action ），更细化的需要在各个页面判断
        $isAllowed = ICE_Acl::allowed($role, $controller, $action);
		if (!$isAllowed) {
			$forwardAction = $isMember ? 'deny' : 'login';
            $next = $request->getRequestUri();
			$request->setControllerName('Auth')
					->setActionName($forwardAction)
					->setQuery('next', $next)
					->setDispatched(true);
		}
    }
}
