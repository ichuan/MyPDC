<?php
/**
* The member class
*
* @author yc <iyanchuan@gmail.com>
*/

class Application_Model_Member extends ICE_Model
{
    protected $_name    = 'member';
    protected $_primary = 'id';

    /*
    --  用户
    create table member (
        id          serial not null primary key,
        username    varchar(64),
        password    varchar(32),
        email       varchar(64),
        email_hash  varchar(32), -- md5(strtolower(trim($email))), cache string
        role        varchar(32),
    --  bio         varchar(140),
        date_join   timestamp without time zone,
    --  last_active timestamp without time zone, -- todo to be cached 
        blocked     boolean default false
    );  
    create index member_index on member(username, password);
    */

    public function getsByParams($params)
    {
        $select = $this->select()->order('id DESC');
        $ret = Zend_Paginator::factory($select);
        if (isset($params['page']) && $params['page'] > 0)
            $ret->setCurrentPageNumber((int)$params['page']);
        if (isset($params['perpage']))
            $ret->setItemCountPerPage((int)$params['perpage']);
        else
            $ret->setItemCountPerPage(20);
        return $ret;
    }

    public function getByUsernameAndEmail($username, $email)
    {
        $select = $this->select()->where('username=?', $username)->where('email=?', $email);
        return $this->fetchRow($select);
    }

    /*
     * 更新最后活动时间
     *
     *
     */
    public function updateLastActive($datetime = null)
    {
        return;//todo 
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity())
            return;
        is_null($datetime) && ($datetime =  DATETIME);
        $member = $auth->getIdentity();
        $this->update(array('last_active' => $datetime), $this->getAdapter()->quoteInto('id=?', $member->id));
    }

    public function getStatById($id)
    {
        assert(is_numeric($id));
        return array(
                ICE_Global::getModel('Code')->count(array('member_id=?' => $id)), // totalCode
                ICE_Global::getModel('Note')->count(array('member_id=?' => $id)), // totalMemo
        );
    }
}
