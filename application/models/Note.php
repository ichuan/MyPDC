<?php
/**
* The Note class
*
* @author yc <iyanchuan@gmail.com>
*/

class Application_Model_Note extends ICE_Model
{
    protected $_name    = 'note';
    protected $_primary = 'id';
    protected $_referenceMap = array(
        'Member' => array(
            'columns'       => 'member_id',
            'refTableClass' => 'Application_Model_Member',
            'refColumns'    => 'id',
        ),
    );

    /*
    -- 记事本
    create table note (
        id          serial not null primary key,
        member_id   integer references member(id) on delete cascade,
        title       varchar(255),                                   
        content     text,
        markdowned  text,                                           
        checked     boolean default false,
        tag_ids     integer[],                                      
        created     timestamp without time zone
    );                                                              
    create index note_index on note(member_id, checked, title);
    */

    public function getsByParams($params)
    {
        $orderby = isset($params['orderby']) ? $params['orderby'] : array('note.top DESC', 'note.id DESC');
        $cols = isset($params['cols']) ? $params['cols'] : '*';
        // can't use Zend_Db_Table_Select here, because we want to use join()
        // see http://stackoverflow.com/questions/520131/translating-a-query-to-use-zend-db-select
        $select = $this->getAdapter()->select()->from('note', $cols)->order($orderby);
        if (isset($params['id']) && $params['id'] > 0)
            $select->where('note.id=?', $params['id']);
        if (isset($params['member_id']) && $params['member_id'] > 0)
            $select->where('note.member_id=?', $params['member_id']);
        if (isset($params['checked']))
            $select->where('note.checked=?', $params['checked'] ? 'true' : 'false');
        if (!empty($params['title']))
            $select->where($this->getAdapter()->quoteInto('note.title ILIKE ?', '%'.$params['title'].'%'));
        if (!empty($params['search']))
            $select->where($this->getAdapter()->quoteInto('note.content ILIKE ?', '%'.$params['search'].'%'));
        if ($cols == '*' || in_array('tag_ids', $cols) || !empty($params['tag'])){
            $cond = 'note_tag.id=ANY(note.tag_ids)';
            // filter by tag
            if (!empty($params['tag']))
                $cond .= sprintf(' AND note_tag.tag_name=ANY(ARRAY[%s])', $this->getAdapter()->quote($params['tag']));
            $select->join('note_tag', $cond, array(
                        'array_agg(note_tag.tag_name) AS tags',
                        'array_agg(note_tag.counter) AS tag_counter'));
            foreach (($cols === '*' ? $this->info(self::COLS) : $cols) as $field)
                $select->group("note.{$field}");
            $select->group('note.id')->group('note.tag_ids')->group('note.top');
        }
        if (isset($params['fetchAll']) && isset($params['fetchAll']))
            return $select->query()->fetchAll();
        $ret = Zend_Paginator::factory($select);
        if (isset($params['page']) && $params['page'] > 0)
            $ret->setCurrentPageNumber((int)$params['page']);
        if (isset($params['perpage']))
            $ret->setItemCountPerPage((int)$params['perpage']);
        else
            $ret->setItemCountPerPage(20);
        return $ret;
    }

    /**
     *
     *
     * @return array | NULL
     */
    public function getByParams($params)
    {
        $ret = $this->getsByParams($params);
        // dont use count($ret) here, cause it will issue a COUNT() query to the database
        try {
            return $ret->getItem(1);
        } catch (Exception $e) {
            return NULL;
        }
    }
}
