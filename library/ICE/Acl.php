<?php
/**
* ICE_Acl
*
* @author yc <iyanchuan@gmail.com>
*/

class ICE_Acl extends Zend_Acl
{
    protected static $_instance = null;
    protected $_cacheFile = null;

    // no public accessable by 'new' operator
    protected function __construct()
    {
        $front = Zend_Controller_Front::getInstance();
        $options = $front->getParam('bootstrap')->getOption('extras');
        if (!isset($options['acl']) || !is_file($options['acl'])){
            ICE_Log::crit('default acl config not found!');
            return $this;
        }
        list($roles, $allow_rules, $deny_rules) = include $options['acl'];
        foreach ($roles as $r => $p){
            try{
                $this->addRole($r, $p);
            } catch (Exception $e){
                ICE_Log::alert($e->getMessage());
            }
        }
        $this->_applyRules($allow_rules, 'allow');
        $this->_applyRules($deny_rules, 'deny');
    }

    private function _applyRules($rules, $action = 'allow')
    {
        if ($action != 'allow' && $action != 'deny')
            return;
        foreach ($rules as $role => $rule){
            $role = ($role == '') ? null : $role;
            foreach ($rule as $res => $priv){
                if ($res == '')
                    $res = null;
                else if (!$this->has($res))
                    $this->addResource($res);
                if (!is_array($priv))
                    $priv = array($priv);
                try{
                    foreach ($priv as $p){
                        $this->$action($role, $res, $p);
                    }
                } catch (Exception $e){
                    ICE_Log::alert($e->getMessage());
                }
            }
        }
    }

    public static function getInstance()
    {
        if (null === self::$_instance) 
            self::$_instance = new self();
        return self::$_instance;
    }

    public static function allowed($role = null, $resource = null, $privilege = null)
    {
        $x = self::getInstance();
        if (!$x->has($resource))
            return false;
        if (!$x->hasRole($role))
            return false;
        return $x->isAllowed($role, $resource, $privilege);
    }
}
