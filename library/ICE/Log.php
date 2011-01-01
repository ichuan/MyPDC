<?php
/**
* ICE_Log
*
* usage: ICE_Log::method('some string')
*        available methods: err, crit, alert
*
* @author yc <iyanchuan@gmail.com>
*/

class ICE_Log
{
    protected static $_instance = null;
    protected static $_zflog= null;

    // no public accessable by 'new' operator
    protected function __construct()
    {
        $front = Zend_Controller_Front::getInstance();
        self::$_zflog = $front->getParam('bootstrap')->getResource('Log');
    }

    public static function err($message)
    {
        self::getInstance()->__call('err', array($message));
    }

    public static function crit($message)
    {
        self::getInstance()->__call('crit', array($message));
    }

    public static function alert($message)
    {
        self::getInstance()->__call('alert', array($message));
    }

    public static function getInstance()
    {
        if (null === self::$_instance) 
            self::$_instance = new self();
        return self::$_zflog;
    }
}
