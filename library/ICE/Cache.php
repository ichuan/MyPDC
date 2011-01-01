<?php
/**
* ICE_Cache
*
* @author yc <iyanchuan@gmail.com>
*/

class ICE_Cache
{
    protected static $_instance = null;
    protected $_cache = null;

    // no public accessable by 'new' operator
    protected function __construct()
    {
        $front = Zend_Controller_Front::getInstance();
        $manager = $front->getParam('bootstrap')->getResource('cachemanager');
        $this->_cache = $manager->getCache('default');
    }


    public static function set($id, $data, $tags = array(), $life = false)
    {
        if (!ENABLE_CACHE)
            return null;
        return self::getInstance()->_cache->save($data, $id, $tags, $life);
    }

    public static function get($id)
    {
        if (!ENABLE_CACHE)
            return null;
        return self::getInstance()->_cache->load($id);
    }

    /**
     * first try to get the cache, return it if hit,
     * or else try to execute the result, set it, and then return it
     *
     */
    public static function load($id, $callback = null, $tags = array(), $life = false)
    {
        if (!ENABLE_CACHE)
            return is_callable($callback) ? $callback() : self::get($id);
        $ret = self::get($id);
        if ($ret !== false)
            return $ret;
        if (is_callable($callback)){
            $data = $callback();
            if (self::set($id, $data, $tags, $life) === false)
                throw new Exception('cannot save cache !');
            return $data;
        }
        return false;
    }

    public static function del($id)
    {
        return self::getInstance()->_cache->remove($id);
    }

    // any thing to a string id
    public static function id()
    {
        $ret = '';
        foreach (func_get_args() as $arg)
            $ret .= var_export($arg, 1);
        return md5($ret);
    }

    public static function cleanAll()
    {
        return self::getInstance()->_cache->clean();
    }

    public static function cleanByTags($tags)
    {
        return self::getInstance()->_cache->clean('matchingTag', $tags);
    }

    // get the raw Zend_Cache_* object
    public static function rawObj()
    {
        return self::getInstance()->_cache;
    }

    public static function getInstance()
    {
        if (null === self::$_instance) 
            self::$_instance = new self();
        return self::$_instance;
    }
}
