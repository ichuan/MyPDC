<?php
/**
 * ICE_Acl view helper
 *
 * @author yc <iyanchuan@gmail.com>
 */

class Application_View_Helper_Score extends Zend_View_Helper_Abstract 
{
    /**
     *
     *@param $a the A 
     *@param $b the B
     *@return float score in [0, 10), exactly: y = log(x+1)
     */
	public function score($a = 0, $b = 0)
    {
        if ($a == 0 && $b == 0)
            return 0.00;
        return round(log10($a*$a*1.0/($a+$b) + 1)*10,2);
	}
}
