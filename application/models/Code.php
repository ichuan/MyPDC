<?php
/**
* The code class
*
* @author yc <iyanchuan@gmail.com>
*/

class Application_Model_Code extends ICE_Model
{
    protected $_name    = 'code';
    protected $_primary = 'id';
    protected $_referenceMap = array(
        'Member' => array(
            'columns'       => 'member_id',
            'refTableClass' => 'Application_Model_Member',
            'refColumns'    => 'id',
        ),
    );

    /*
    -- 代码
    create table code (
        id          serial not null primary key,
        title       varchar(255),
        member_id   integer references member(id) on delete cascade,
        description text,
        code        text,
        highlighted text,
        tag_ids     integer[],
        language_id integer default 0, -- 0 means 'uncategoriesed'
        created     timestamp without time zone
        codebytes   integer,
    );
    create index code_index on code(member_id, tag_ids, language_id);
    */

    public function getsByParams($params)
    {
        $cols = isset($params['cols']) ? $params['cols'] : '*';
        $orderby = isset($params['orderby']) ? $params['orderby'] : 'code.id DESC';
        // can't use Zend_Db_Table_Select here, because we want to use join()
        // see http://stackoverflow.com/questions/520131/translating-a-query-to-use-zend-db-select
        $select = $this->getAdapter()->select()->from('code', $cols)->order($orderby);
        if (isset($params['member_id']))
            $select->where('code.member_id=?', $params['member_id']);
        if (isset($params['id']))
            $select->where('code.id=?', $params['id']);
        if (isset($params['tag_id']))
            $select->where('?=ANY(code.tag_ids)', $params['tag_id']);
        else if (isset($params['tag_ids']))
            $select->where(sprintf('ARRAY[%s] && code.tag_ids', implode(',', $params['tag_ids'])));
        if (!empty($params['title']))
            $select->where($this->getAdapter()->quoteInto('code.title ILIKE ?', '%'.str_replace('%','\\%',$params['title']).'%'));
        if (!empty($params['language_ids']))
            $select->where(sprintf('language_id=ANY(ARRAY[%s])', implode(',', $params['language_ids'])));
        if ($cols == '*' || in_array('tag_ids', $cols) || !empty($params['tag'])){
            // we have to deal with situations that a code does not have any tag, by using CASE..WHEN..THEN..ELSE..END
            $cond = 'tag.id=ANY(code.tag_ids)';
            // filter by tag
            if (!empty($params['tag']))
                $cond .= sprintf(' AND tag.tag_name=ANY(ARRAY[%s])', $this->getAdapter()->quote($params['tag']));
            $select->join('tag', $cond, array(
                        'array_agg(tag.tag_name) AS tags',
                        'array_agg(tag.counter) AS tag_counter'));
            foreach (($cols === '*' ? $this->info(self::COLS) : $cols) as $field)
                $select->group("code.{$field}");
            $select->group('code.id')->group('code.tag_ids');
        }
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

    public function getLanguages($params)
    {
        $select = $this->select()->from('code', array('language_id', 'count(1) as counter'))->order('counter DESC')->group('language_id');
        if (isset($params['member_id']))
            $select->where('member_id=?', $params['member_id']);
        if (isset($params['limit']))
            $select->limit($params['limit']);
        return $this->fetchAll($select);
    }
}
