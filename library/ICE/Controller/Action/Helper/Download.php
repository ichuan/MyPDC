<?php
class ICE_Controller_Action_Helper_Download extends Zend_Controller_Action_Helper_Abstract
{

    /**
     * send download header and pass content to browser
     *
     * @paramm array $message, e.g. array('fail', _t('提交失败！'))
     */
    public function direct($data, $filename, $filesize = null, $plaintext = true, $isfile = false, $die = true)
    {
        $controller = $this->getActionController();
        $controller->view->layout()->disableLayout();
        $controller->getHelper('viewRenderer')->setNoRender(true);
        header('Content-type: ' . ($plaintext ? 'text/plain' : 'application/octet-stream'));
        if ($filesize !== null)
            header('Content-Length: ' . $filesize);
        header(sprintf('Content-Disposition: attachment; filename="%s"', encodeFilename($filename)));
        if (!$isfile)
            echo $data;
        elseif (is_file($data)){
            $fp = fopen($data, 'rb');
            fpassthru($fp);
        }
        $die && die();
    }
}
