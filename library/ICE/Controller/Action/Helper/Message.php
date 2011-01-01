<?php
class ICE_Controller_Action_Helper_Message extends Zend_Controller_Action_Helper_Abstract
{

    /**
     * render a message page
     *
     * @paramm array $message, e.g. array('fail', _t('提交失败！'))
     */
    public function direct($message)
    {
        $controller = $this->getActionController();
        $controller->view->layout()->setLayout('message');
        $controller->getHelper('viewRenderer')->setNoRender(true);
        $controller->view->message = $message;
    }
}
