<?php
/**
* hook plugin
*
* @author yc <iyanchuan@gmail.com>
*/

require_once 'Zend/Controller/Plugin/Abstract.php';


class ICE_Controller_Plugin_Hook extends Zend_Controller_Plugin_Abstract
{
    /**
     * before dispatch loop start, setup our app enviorment
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        ICE_Acl::getInstance();

        // clean cache if needed
        // cannot be placed in dispatchLoopShutdown() cause  _redirect() in controller can terminate us before we get executed
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity())
            return;
        if ($request->getControllerName() == 'code'){
            $action = $request->getActionName();
            if (($request->isPost() && ($action == 'new' || $action == 'edit')) || $action == 'delete'){
                $member = $auth->getIdentity();
                ICE_Cache::cleanByTags(array('code', 'pages', 'member' . $member->id));
                ICE_Cache::del(ICE_Cache::id('code.tags.member' . $member->id));
                ICE_Cache::del(ICE_Cache::id('code.languages.member' . $member->id));
            }
        }
    }

    /**
     * after dispatch loop ends, update user staff
     *
     */
    public function dispatchLoopShutdown()
    {
        // update user's last_active time
        // $m = ICE_Global::getModel('Member');
        // $m->updateLastActive();

    }
}
