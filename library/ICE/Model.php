<?php
/**
 * ICE_Model
 *
 * yc <iyanchuan@gmail.com>
 */

class ICE_Model extends Zend_Db_Table_Abstract
{
    /**
     * use $this->getByXX($x) to retrive a row where `XX` ==$x
     *  
     */
    public function __call($name, $args)
    {
        if (strpos($name, 'getBy') === 0){
            $field = strtolower(substr($name, 5));
            $value = array_shift($args);
            $where = $this->getAdapter()->quoteInto("$field = ?", $value);
            return $this->fetchRow($where);
        }
        trigger_error('method ' . $name . ' doesnot exists');
    }

    /**
     * a simple count helper
     *
     */
    public function count($where = array())
    {
        $select = $this->select()->from($this->_name, 'COUNT(1) AS counter');
        foreach ($where as $k => $v)
            if ($v === NULL) // RAW SQL
                $select->where($k);
            else
                $select->where($k, $v);
        $rows = $this->_fetch($select);
        if (count($rows))
            return $rows[0]['counter'];
        return 0;
    }

    public function resetTableName($name)
    {
        $this->setOptions(array('name' => $name));
    }
}
