<?php
/**
 * ICE_Acl view helper
 *
 * @author yc <iyanchuan@gmail.com>
 */

class Application_View_Helper_Avatar extends Zend_View_Helper_Abstract 
{
    /**
     *
     *@param $user object or array
     *@param $size
     *@return image url
     */
	public function avatar($user, $size = 24, $echo = true)
    {
        if (is_object($user))
            $hash = $user->email_hash;
        else if (is_array($user))
            $hash = $user['email_hash'];
        if (empty($hash))
            $hash = 'default';
        $image = 'http://www.gravatar.com/avatar/' . $hash . '.jpg?s=' . $size;
        if ($echo)
            echo $image;
        else
            return $image;
	}
}
