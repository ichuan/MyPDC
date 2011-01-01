<?php
/**
* The tag class
*
* @author yc <iyanchuan@gmail.com>
*/

class Application_Model_Tag extends ICE_Model
{
    protected $_name    = 'tag';
    protected $_primary = 'id';

    /*
    -- 标签
    create table tag (
        id          serial not null primary key,
        tag_name    varchar(255),
        counter     integer default 1,
        member_id   integer references member(id) on delete cascade
    );
    */

    public function getsByMemberAndSearch($memberId = 0, $search = null, $limit = 10, $offset = 0, $orderby = null)
    {
        $order = ($orderby === null) ? 'tag_name' : $orderby;
        $select = $this->select()->limit($limit, $offset)->order($order);
        if ($memberId !== 0)
            $select->where('member_id = ?', $memberId);
        if (!empty($search))
            $select->where('tag_name ilike ?', "{$search}%");

        return $this->fetchAll($select);
    }

    public function getByMemberAndName($memberId = 0, $name = null)
    {
        if ($memberId === 0 || $name === null)
            return array();
        $select = $this->select()->where('member_id = ?', $memberId)->where('tag_name = ?', $name);
        return $this->fetchRow($select);
    }

    public function increaseCounter($ids)
    {
        //return $this->update(array('counter' => 'counter+1'), sprintf('id IN (%s)', implode(',', $ids)));
        return $this->_db->query(sprintf('UPDATE %s SET counter=counter+1 WHERE id IN (%s)', $this->_name, implode(',', $ids)))
                         ->rowCount();
    }

    public function decreaseCounter($ids)
    {
        if ($this->_db->query(sprintf('UPDATE %s SET counter=counter-1 WHERE id IN (%s)', $this->_name, implode(',', $ids)))->rowCount())
            $this->unlinkTags($ids);
    }

    // delete those referense is zero (counter == 0)
    public function unlinkTags($ids)
    {
        $this->delete(array('counter=0', sprintf('id IN (%s)', implode(',', $ids))));
    }
}
