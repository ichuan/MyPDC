<?php

class MemberController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
        //echo $this->view->translate('测试');
    }

    public function indexAction()
    {
    }

    public function settingAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()){
            $this->view->message = array();
            $email = trim($request->email);
            if (empty($email))
                $this->view->message['email'] = _t('请输入你的电子邮件地址');
            else if (!isEmail($email))
                $this->view->message['email'] = _t('你输入的电子邮件地址不符合规则');
            else {
                $m  = ICE_Global::getModel('Member');
                $row= $m->getById($this->view->user->id);
                if ($row['id']){
                    $row['email']       = $email;
                    $row['email_hash']  = md5(strtolower($email));
                    if ($row->save()){
                        ICE_Audit::record(sprintf('更换电子邮件地址（%s => %s）', $this->view->user->email, $email), $this->view->user->id, ICE_Audit::SETTING);
                        $this->view->user->email = $row['email'];
                        $this->view->user->email_hash = $row['email_hash'];
                        $this->view->message['setting'] = array('success', _t('你的电子邮件地址已更新！'));
                        // update session
                        // $zass = Zend_Auth::getInstance()->getStorage();
                        // $zass->write($this->view->user);
                    } else
                        ICE_Log::crit('cannot update general settings.');
                }
            }
        }
    }

    public function resetpasswdAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()){
            $this->view->message = array();
            $password = trim($request->password);
            if (empty($password))
                $this->view->message['password'] = _t('请输入新密码');
            else {
                $m  = ICE_Global::getModel('Member');
                $row= $m->getById($this->view->user->id);
                if ($row['id']){
                    $row['password'] = md5($password);
                    if ($row->save()){
                        ICE_Audit::record('更改密码', $this->view->user->id, ICE_Audit::SETTING);
                        $this->view->user->password = $row['password'];
                        $this->view->message['password'] = array('success', _t('你的密码已变更！'));
                        // update session
                        // $zass = Zend_Auth::getInstance()->getStorage();
                        // $zass->write($this->view->user);
                    } else
                        ICE_Log::crit('cannot update general settings.');
                }
            }
        }
        $this->render('setting');
    }
}

