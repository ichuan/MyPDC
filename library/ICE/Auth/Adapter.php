<?php
/**
* ICE_Auth_Adapter
*
* @author yc <iyanchuan@gmail.com>
*/

class ICE_Auth_Adapter extends Zend_Auth_Adapter_DbTable
{
    private $_db    = null;
    private $_mid   = null;

    public function __construct($username, $password)
    {
        $front = Zend_Controller_Front::getInstance();
        $this->_db = $front->getParam('bootstrap')->getResource('db');
        $this->setIdentity($username);
        $this->setCredential($password);
        parent::__construct($this->_db, 'member', 'username', 'password', 'md5(?)');
    }

    public function setMemberId($id)
    {
        $this->_mid = $id;
    }

    /**
     *
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        if (!is_null($this->_mid))
            return $this->getUserObjectResult();
        $result = parent::authenticate();
        if ($result->getCode() === Zend_Auth_Result::SUCCESS)
            $result = new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $this->getResultRowObject());
        return $result;
    }

    public function getUserObjectResult()
    {
        $select = $this->_db->select()->from('member')->where('id=?', $this->_mid)->limit(1);
        $user = $select->query()->fetchObject();
        return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $user);
    }
}
