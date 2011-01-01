<?php
/**
* AuthController
*
* @author yc <iyanchuan@gmail.com>
*/

class AuthController extends Zend_Controller_Action
{

    public function loginAction()
    {
        $request  = $this->getRequest();
		$auth 	  = Zend_Auth::getInstance();
        $clientIp = $request->getClientIp();
       
		// Redirect to index if user has logged in already
        $auth->hasIdentity() && $this->_redirect('/');

        $this->view->next = $this->_getParam('next');
        if ($request->isPost() && trim($request->getPost('username')) && trim($request->getPost('password'))) {
			$username = $request->getPost('username');
			$password = $request->getPost('password');
			$adapter  = new ICE_Auth_Adapter($username, $password);
			$result   = $auth->authenticate($adapter);
			switch ($result->getCode()){
				/**
				 * Logged in successfully
				 */
				case Zend_Auth_Result::SUCCESS:
                    $user = $auth->getIdentity();
                    if ($user->blocked){
                        ICE_Audit::record(sprintf('被屏蔽用户，登录失败！（IP：%s）', $clientIp), $user->id, ICE_Audit::AUTH);
                        // logout
                        Zend_Session::destroy(false, false);
                        $auth->clearIdentity();
                        $this->view->message = _t('你已被禁止登入');
                        break;
                    }
                    ICE_Audit::record(sprintf('登录成功！（IP：%s）', $clientIp), $user->id, ICE_Audit::AUTH);
                    $forward = isset($request->next) ? $request->next : '/';
                    $this->_redirect($forward);
					break;
				case Zend_Auth_Result::FAILURE:
				case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
				case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
				default:
                    $this->view->message = _t('你输入的用户名或密码不正确');
                    ICE_Audit::record(sprintf('登录失败！（IP：%s，用户：%s）', $clientIp, htmlspecialchars($username)), 1, ICE_Audit::AUTH);
					break;
			}
		}
    }

    public function denyAction()
    {
        //$response = $this->getResponse();
        //$response->setHeader('Content-Type', 'text/html; charset=utf-8', true);
        //$this->_helper->viewRenderer->setNoRender(true);
        //$this->view->layout()->disableLayout();
        //echo $this->view->translate('你没有进入当前页面的权限！');
        $this->_forward('error', 'error');
    }

    public function logoutAction()
    {
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$user = $auth->getIdentity();
            ICE_Audit::record('登出成功！', $user->id, ICE_Audit::AUTH);
//			// Clear Session
			Zend_Session::destroy(false, false);
			$auth->clearIdentity();
            $this->view->user = null;
		} else
            $this->_redirect('/');
    }

    public function registerAction()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity())
            $this->_redirect('/');
        $request  = $this->getRequest();
        $zci = new Zend_Captcha_Image(array(
            'imgDir'    => CAPTCHA_PATH,
            'imgUrl'    => CAPTCHA_URL,
            'wordlen'   => 6,
            'font'      => FONT_PATH,
        ));
        if ($request->isPost()){
            $this->view->message = array();
            if (empty($request->username))
                $this->view->message['username'] = _t('请输入你的用户名');
            else if (!preg_match('/^[\da-zA-Z\-_]+$/', $request->username))
                $this->view->message['username'] = _t('用户名只能由 a-Z 0-9 及 - 和 _ 组成');
            else if (strlen($request->username) >= 64)
                $this->view->message['username'] = _t('用户名不能超过 64 个字符');
            if (empty($request->password))
                $this->view->message['password'] = _t('请输入你的密码');
            if (empty($request->email))
                $this->view->message['email'] = _t('请输入你的电子邮件地址');
            else if (!isEmail($request->email))
                $this->view->message['email'] = _t('你输入的电子邮件地址不符合规则');
            if (empty($request->captcha))
                $this->view->message['captcha'] = _t('请输入验证码');
            else if (!$zci->isValid(array('id' => $request->captchaId, 'input' => $request->captcha)))
                $this->view->message['captcha'] = _t('请重新输入验证码');
            if (!empty($this->view->message)){
                $this->view->username = $request->username;
                $this->view->email = $request->email;
            } else {
                $m = ICE_Global::getModel('Member');
                $row = $m->getByUsername($request->username);
                if ($row['id'])
                    $this->view->message['username'] = _t('该用户名已被其他人使用');
                else {
                    $id = $m->insert(array(
                        'username'  => $request->username,
                        'password'  => md5($request->password),
                        'email'     => trim($request->email),
                        'email_hash'=> md5(strtolower(trim($request->email))),
                        'role'      => 'member',
                        'date_join' => DATETIME,
                    ));
                    $clientIp = $request->getClientIp();
                    if ($id){
                        $auth = Zend_Auth::getInstance();
                        $adapter = new ICE_Auth_Adapter('user', 'pass');
                        $adapter->setMemberId($id);
                        $result = $auth->authenticate($adapter);
                        ICE_Audit::record(sprintf('注册成功！（IP：%s）', $clientIp), $id, ICE_Audit::AUTH);
                        $this->_redirect('/');
                    } else
                        ICE_Audit::record(sprintf('注册失败！（IP：%s）', $clientIp), 1, ICE_Audit::AUTH);
                }
            }
        }
        Zend_Registry::set('Zend_Captcha_Image', $zci);
        $this->view->captchaId = $zci->generate();
    }

    public function resetpasswordAction()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity())
            $this->_redirect('/');
        $request  = $this->getRequest();

        // handle reset link from email
        if (isset($request->u) && isset($request->e) && isset($request->t) && isset($request->h)){
            if (hashes($request->u, $request->e, SERVER_PRK, $request->t) == $request->h && (TIMESTAMP - $request->t < 3600)){
                $m = ICE_Global::getModel('Member');
                $row = $m->getByUsernameAndEmail($request->u, $request->e);
                if ($row !== NULL || !$row->blocked){
                    $this->view->newpass = substr(hashes(TIMESTAMP, rand()), 0, 8);
                    $row->password = md5($this->view->newpass);
                    $row->save();
                    return;
                }
            }
        }

        $zci = new Zend_Captcha_Image(array(
            'imgDir'    => CAPTCHA_PATH,
            'imgUrl'    => CAPTCHA_URL,
            'wordlen'   => 6,
            'font'      => FONT_PATH,
        ));
        if ($request->isPost()){
            $this->view->message = array();
            if (empty($request->username))
                $this->view->message['username'] = _t('请输入你的用户名');
            else if (!preg_match('/^[\da-zA-Z\-_]+$/', $request->username))
                $this->view->message['username'] = _t('用户名只能由 a-Z 0-9 及 - 和 _ 组成');
            else if (strlen($request->username) >= 64)
                $this->view->message['username'] = _t('用户名不能超过 64 个字符');
            if (empty($request->email))
                $this->view->message['email'] = _t('请输入你的电子邮件地址');
            else if (!isEmail($request->email))
                $this->view->message['email'] = _t('你输入的电子邮件地址不符合规则');
            if (empty($request->captcha))
                $this->view->message['captcha'] = _t('请输入验证码');
            else if (!$zci->isValid(array('id' => $request->captchaId, 'input' => $request->captcha)))
                $this->view->message['captcha'] = _t('请重新输入验证码');
            if (!empty($this->view->message)){
                $this->view->email = $request->email;
            } else {
                $m = ICE_Global::getModel('Member');
                $row = $m->getByUsernameAndEmail($request->username, $request->email);
                if ($row === NULL || $row->blocked)
                    $this->view->message['email'] = _t('不存在此用户');
                else{
                    $this->view->sent = true;
                    $url = APPURL . '/reset_password?u=' . urlencode($row->username) . '&e=' . urlencode($row->email) .
                           '&t=' . TIMESTAMP . '&h=' . hashes($row->username, $row->email, SERVER_PRK, TIMESTAMP);
                    $url = '<a href="' . $url . '">' . $url . '</a>';
                    $body = sprintf(_t('%s这是一封来自 %sMyPDC%s 的重置密码的信件，你收到此信的原因是你（或其他人）对帐号 %s 使用了重置密码功能%s请访问此链接重置此帐号的密码：%s这个链接有效时间为 1 小时。'), '<p>', '<a href="'.APPURL.'">', '</a>', $row->username, '</p><p>', $url . '</p><br />');
                    sendMail($row->email, $row->username, _t('重置你在 MyPDC 的密码'), $body);
                }
            }
        }
        Zend_Registry::set('Zend_Captcha_Image', $zci);
        $this->view->captchaId = $zci->generate();
    }
}
