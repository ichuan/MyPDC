<?php
/**
* The Audit class
*
* @author yc <iyanchuan@gmail.com>
*/

class Application_Model_Audit extends ICE_Model
{
    protected $_name    = 'audit';
    protected $_primary = 'id';
    protected $_referenceMap = array(
        'Member' => array(
            'columns'       => 'member_id',
            'refTableClass' => 'Application_Model_Member',
            'refColumns'    => 'id',
        ),
    );

    /*
    -- 日志 (see ICE_Audit)
    create table audit (
        id          serial not null primary key,
        member_id   integer references member(id) on delete cascade,
        content     varchar(255),
        date_audit  timestamp without time zone,
        audit_type  smallint
    );
    */
    public function getsByParams($params)
    {
        $orderby = isset($params['orderby']) ? $params['orderby'] : 'audit.id DESC';
        // can't use Zend_Db_Table_Select here, because we want to use join()
        // see http://stackoverflow.com/questions/520131/translating-a-query-to-use-zend-db-select
        $select = $this->getAdapter()->select()->from('audit')->order($orderby);
        if (isset($params['member_id']) && $params['member_id'] > 0)
            $select->where('audit.member_id=?', $params['member_id']);
        if (isset($params['audit_type']) && $params['audit_type'] > 0)
            $select->where('audit.audit_type=?', $params['audit_type']);
        $select->join('member', 'audit.member_id=member.id', array('username'));
        $ret = Zend_Paginator::factory($select);
        if (isset($params['page']) && $params['page'] > 0)
            $ret->setCurrentPageNumber((int)$params['page']);
        if (isset($params['perpage']))
            $ret->setItemCountPerPage((int)$params['perpage']);
        else
            $ret->setItemCountPerPage(20);
        return $ret;
    }
    
}
