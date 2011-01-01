<?php
/**
* AdminController
*
* @author yc <iyanchuan@gmail.com>
*/

class AdminController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $this->view->headTitle()->append(_t('系统管理'));
    }

    public function auditAction()
    {
        $request = $this->getRequest();
        $a = ICE_Global::getModel('Audit');
        $params = array(
            'audit_type'=> (int)$request->getParam('audit_type', 0),
            'member_id' => (int)$request->getParam('member_id', 0),
            'page'      => (int)$request->getParam('page', 1),
            'perpage'   => ITEMS_PER_PAGE,
        );

        $paginator = $a->getsByParams($params); // big query, Ouch! db hited!
        $this->view->audits = $paginator->getCurrentItems(); // Ouch! db hited again!
        $this->view->pages = $paginator->getPages(); // Ouch! COUNT() query performed!

        unset($params['perpage']);
        $this->view->params = $params;
        $this->render('audit');
    }

    public function memberAction()
    {
        $request = $this->getRequest();
        $m = ICE_Global::getModel('Member');
        $params = array(
            'page'      => (int)$request->getParam('page', 1),
            'perpage'   => ITEMS_PER_PAGE,
        );

        $paginator = $m->getsByParams($params); // big query, Ouch! db hited!
        $this->view->members = $paginator->getCurrentItems(); // Ouch! db hited again!
        $this->view->pages = $paginator->getPages(); // Ouch! COUNT() query performed!

        unset($params['perpage']);
        $this->view->params = $params;
        $this->render('member');
    }

    public function cleancacheAction()
    {
        ICE_Cache::cleanAll();
        // clear stat caches
        $redis = ICE_Global::get('redis');
        $redis->flushdb();
        $this->render('index');
    }
}

