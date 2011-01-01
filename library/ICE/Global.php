<?php
/**
* ICE_Acl
*
* global varible container
*
* @author yc <iyanchuan@gmail.com>
*/

class ICE_Global
{
    protected static $_instance = null;
    protected $_storage = array();

    // no public accessable by 'new' operator
    protected function __construct(){}

    public static function getInstance()
    {
        if (null === self::$_instance) 
            self::$_instance = new self();
        return self::$_instance;
    }

    public static function set($name, &$value)
    {
        assert(is_string($name));
        $me = self::getInstance();
        $me->_storage[$name] = $value;
        return $value;
    }

    public static function get($name, $defalut = null)
    {
        assert(is_string($name));
        $me = self::getInstance();
        if (isset($me->_storage[$name]))
            return $me->_storage[$name];
        if (is_callable($defalut))
            $defalut = $defalut();
        return self::set($name, $defalut);;
    }

    // this is equvilent to ICE_Global::get('SomeModel', function(){ return new Application_Model_SomeModel; });
    public static function getModel($name)
    {
        assert(is_string($name));
        $className = 'Application_Model_' . $name;
        $name = 'Model::' . $name;
        $me = self::getInstance();
        if (isset($me->_storage[$name]))
            return $me->_storage[$name];
        return self::set($name, new $className);
    }

    public static function has($name)
    {
        assert(is_string($name));
        $me = self::getInstance();
        return isset($me->_storage[$name]);
    }
}
