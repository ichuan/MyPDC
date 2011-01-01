<?php
/**
 * default ACL
 *
 * @author yc <iyanchuan@gmail.com>
 */

// role => array(parents)
$roles = array(
    'guest'             => null,
    'member'            => array('guest'),
    'admin'             => array('member'), // 系统管理员
);

//resources and priviledges map
$allow_rules = array(
    'guest' => array(
        'index'     => array('index', 'about', 'feedback', 'plan', 'markdown'),
        'auth'      => array('login', 'deny', 'register', 'resetpassword'),
    ),
    'member'    => array(
        'auth'      => 'logout',
        'member'    => array('setting', 'resetpasswd'),
        'code'      => array('index', 'new', 'edit', 'view', 'edit', 'delete', 'download', 'tags', 'languages'),
        'note'      => array('index', 'tags', 'tag', 'export'),
        'ajax'      => array('tags', 'note'),
    ),
    'admin'     => array(
        'member'    => null,
        'index'     => null,
        'admin'     => null,
        'ajax'      => null,
    ),
);

$deny_rules = array(
    'guest' => array(
//        ''  => null,  // deny all
    ),
    'admin' => array(
        'vuln' => array(
            'index',
        ),
    ),
);
return array($roles, $allow_rules, $deny_rules);
