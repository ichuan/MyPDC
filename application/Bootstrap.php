<?php
/**
* The bootstrap class
*
* @author yc <iyanchuan@gmail.com>
*
*/
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    public function _initTranslation()
    {
        $options    = $this->getOption('extras');
        assert(isset($options['translate']['default']));
        $translate = new Zend_Translate($options['translate']['default']['adapter'],
                                        $options['translate']['default']['data'],
                                        $options['translate']['default']['locale']
                                        );
        foreach ($options['translate']['others'] as $locale => $data)
            $translate->addTranslation($data, $locale);

        // setup local
        $locale = (isset($_COOKIE['lang']) && $_COOKIE['lang'] == 'en') ? 'en' : 'zh';
        $translate->setLocale($locale);

        // allow View/Helper/Translate to access
        Zend_Registry::set('Zend_Translate', $translate);
        return $translate;
    }

    public function _initCache()
    {
        $manager = $this->bootstrap('cachemanager')->getResource('cachemanager');
        //Zend_Paginator::setCache($manager->getCache('default')); // we will use our cache
        Zend_Db_Table_Abstract::setDefaultMetadataCache($manager->getCache('default'));

        $options    = $this->getOption('extras');
        assert(isset($options['redis']));
        require_once 'Predis.php';
        ICE_Global::set('redis', new Predis\Client($options['redis']));
    }

    public function _initPage()
    {
        #$front  = Zend_Controller_Front::getInstance();
        #$request= $front->getRequest();
        $this->bootstrap('view');
        $view = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view;
        $view->headMeta('text/html; charset=utf-8', 'Content-Type');
        $view->headTitle(APPNAME)->setSeparator(' › ');

        $view->user = Zend_Auth::getInstance()->getIdentity();

        //$view->onlineCount = ICE_Cache::load('OnlineUserCount', // todo caches
        //                Application_Model_User::getInstance(), 300);// 5分钟内在线用户
        //$view->onlineCount = Application_Model_User::getInstance()->getOnlineUserCount();

        // init placeholders
        $view->placeholder('main')
             ->setPrefix('<div id="main">')
             ->setPostfix('</div>');
        $view->placeholder('sidebar')
              ->setPrefix('<div id="sidebar">')
              ->setPostfix('</div>');
    }

    public function _initOther()
    {
        include 'ICE/Func.php'; // load functions
    }
}
