<?php
/**
 * ICE_Acl view helper
 *
 * @author yc <iyanchuan@gmail.com>
 */

class Application_View_Helper_Audit extends Zend_View_Helper_Abstract 
{
	public function audit($audit) 
    {
        return ICE_Audit::content($audit);
	}
}
