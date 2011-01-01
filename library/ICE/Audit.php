<?php
/**
* ICE_Audit
*
* usage: ICE_Audit::method('some string')
*        available methods: debug, info, notice, warn, err, crit, alert, emerg
*
* @author yc <iyanchuan@gmail.com>
*/

class ICE_Audit
{
    protected static $_instance = null;
    protected $_model = null;
    protected $_consts = null;

    // see getTypes()
    const AUTH      = 1;  // 登录、注册、登出等日志
    const CODE      = 2;  // 代码app
    const SETTING   = 3;  // 设置
    const NOTE      = 4;  // 设置
    const AUDIT     = 98;  // 日志操作
    const OTHER     = 99;  // 其他类型
    

    // no public accessable by 'new' operator
    protected function __construct()
    {
        $this->_model = ICE_Global::getModel('Audit');
        $r = new ReflectionClass($this);
        $this->_consts = $r->getConstants();
    }

    public static function getTypes()
    {
        return self::getInstance()->_consts;
    }

    /**
     * record a audit into database
     *
     * @param $methods string like '12' or '127.0.0.1'
     * @param $type ICE_Audit::LOGIN, etc
     * @param $admin_id
     */
    public static function record($message, $member_id = 1, $type = ICE_Audit::OTHER)
    {
        // handle error like: SQLSTATE[23503]: Foreign key violation: 7 错误:  插入或更新表 "audit" 违反外键约束
        try{
            return self::getInstance()->_model->insert(array(
                'content'       => $message,
                'member_id'     => (int)$member_id,
                'date_audit'    => DATETIME,
                'audit_type'    => $type,
            ));
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getInstance()
    {
        if (null === self::$_instance) 
            self::$_instance = new self();
        return self::$_instance;
    }
}
