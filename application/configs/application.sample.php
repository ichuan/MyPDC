<?php
/**
*
* app config file
*
* @author yc <iyanchuan@gmail.com>
*/

assert_options(ASSERT_BAIL, 1); // terminate execution on failed assertions
error_reporting(E_ALL & ~E_STRICT); // dismiss STRICT notice

$router_config = array(
    'routes' => array(
        'signin' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => '/signin',
            'defaults' => array(
                'controller'    => 'auth',
                'action'        => 'login',
            ),
        ),
        'signout' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => '/signout',
            'defaults' => array(
                'controller'    => 'auth',
                'action'        => 'logout',
            ),
        ),
        'signup' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => '/signup',
            'defaults' => array(
                'controller'    => 'auth',
                'action'        => 'register',
            ),
        ),
        'reset_password' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => '/reset_password',
            'defaults' => array(
                'controller'    => 'auth',
                'action'        => 'resetpassword',
            ),
        ),
        'feedback' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => '/feedback',
            'defaults' => array(
                'controller'    => 'index',
                'action'        => 'feedback',
            ),
        ),
        'plan' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => '/plan',
            'defaults' => array(
                'controller'    => 'index',
                'action'        => 'plan',
            ),
        ),
        'changelog' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => '/changelog',
            'defaults' => array(
                'controller'    => 'index',
                'action'        => 'changelog',
            ),
        ),
        'setting' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => '/setting',
            'defaults' => array(
                'controller'    => 'member',
                'action'        => 'setting',
            ),
        ),
        'setting.password' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => '/setting/passwd',
            'defaults' => array(
                'controller'    => 'member',
                'action'        => 'resetpasswd',
            ),
        ),
        'code.language' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => 'code/language/([\da-zA-Z\-_]+)',
            'defaults' => array(
                'controller'    => 'code',
                'action'        => 'index',
                'filter'        => 'language',
            ),
        ),
        'code.view' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => 'code/(\d+)',
            'defaults' => array(
                'controller'    => 'code',
                'action'        => 'view',
            ),
        ),
        'code.delete' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => 'code/delete/(\d+)',
            'defaults' => array(
                'controller'    => 'code',
                'action'        => 'delete',
            ),
        ),
        'code.edit' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => 'code/edit/(\d+)', // with no '/' prefix!! see match() in Zend/Controller/Router/Route/Regex.php
            'defaults' => array(
                'controller'    => 'code',
                'action'        => 'edit',
            ),
        ),
        'code.download' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => 'code/download/(\d+)',
            'defaults' => array(
                'controller'    => 'code',
                'action'        => 'download',
            ),
        ),
        'code.tag' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => 'code/tag/(.+)',
            'defaults'  => array(
                'controller'    => 'code',
                'action'        => 'index',
                'filter'        => 'tag',
            ),
        ),
        'note.tag' => array(
            'type'  => 'Zend_Controller_Router_Route_Regex',
            'route' => 'note/tag/(.+)',
            'defaults'  => array(
                'controller'    => 'note',
                'action'        => 'index',
                'filter'        => 'tag',
            ),
        ),
        'about' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => '/about',
            'defaults' => array(
                'controller'    => 'index',
                'action'        => 'about',
            ),
        ),
        'markdown' => array(
            'type'  => 'Zend_Controller_Router_Route_Static',
            'route' => '/doc/markdown',
            'defaults' => array(
                'controller'    => 'index',
                'action'        => 'markdown',
            ),
        ),
    ),
);

$config = array(
    'phpSettings' => array(
        'display_startup_errors'    => 0,
        'display_errors'            => 0,
        'date.timezone'             => 'Asia/Shanghai',
        'session.name'              => 'ICETOKEN',
        'session.cookie_lifetime'   => 31536000, // 1 year
        'session.gc_maxlifetime'    => 31536000, // 1 year
        'session.use_cookies'       => 1,
        'session.use_only_cookies'  => 1,
        'session.cookie_httponly'   => 1,
        'session.save_path'         => APPLICATION_PATH . '/data/session/',
        'session.cookie_path'       => '/',
    ),
    'autoloadernamespaces' => array(
        'ICE_',
    ),
    'bootstrap' => array(
        'path'      => APPLICATION_PATH . '/Bootstrap.php',
        'class'     => 'Bootstrap',
    ),
    'appnamespace' => 'Application',
    'resources' => array(
        'frontController' => array(
            'controllerDirectory' => APPLICATION_PATH . '/controllers',
            'params' => array(
                'displayExceptions' => 0,
            ),
            'plugins' => array(
                'ICE_Controller_Plugin_Auth',
                'ICE_Controller_Plugin_Hook',
            ),
            'actionhelperpaths' => array(
                'ICE_Controller_Action_Helper'  => APPLICATION_PATH . '/../library/ICE/Controller/Action/Helper',
            ),
        ),
        'layout' => array(
            'layoutPath'    => APPLICATION_PATH . '/layouts/scripts',
            'layout'        => 'github',
        ),
        'db'     => array(
            'adapter'   => 'pdo_pgsql',
            'params'    => array(
                'host'      => '127.0.0.1',
                'username'  => 'postgres',
                'password'  => '',
                'dbname'    => 'pdc',
                'options'   => array(
                    'autoQuoteIdentifiers'  => false, // we want to use postgresql array columns such as: content[1]
                ),
            ),
            'default'   => 1,
        ),
        'router' => $router_config,
        'view' => array(
            'doctype'       => 'XHTML1_STRICT',
            'helperPath'    => array(
                'ICE_View_Helper'   => APPLICATION_PATH . '/../library/ICE/View/Helper',
                'Application_View_Helper'   => APPLICATION_PATH . '/views/helpers/'
            ),
        ),
        'log' => array(
            0       => array(
                        'writerName'        => 'Stream',
                        'filterName'        => 'Priority',
                        'writerParams'      => array(
                            'stream'            => APPLICATION_PATH . '/data/log/ui.log',
                            'mode'              => 'a',
                        ),
                        'filterParams'      => array(
                            'priority'          => 4, // >= WARN
                        ),
                    ),
        ),
        'cachemanager'  => array(
            'default'       => array(
                'frontend' => array(
                    'name'    => 'Core',
                    'options' => array(
                        'automatic_serialization'   => true,
                        'automatic_cleaning_factor' => 0,
                        'lifetime'                  => 31536000,  // 1 YEAR! yes, but we keep updating it
                        'write_control'             => false, // do NOT read cache just after saving it
                    ),
                ),
                'backend'       => array(
                    'name'    => 'File',
                    'options'       => array(
                        'read_control'  => false, // do NOT hash() the f*cking cache
                        'cache_dir'     => APPLICATION_PATH . '/data/cache',
                    ),
                ),
            ),
        ),
    ),
    'extras' => array(
        'translate' => array(
            'default'   => array(
                'data'      => APPLICATION_PATH . '/languages/default.en.php',
                'locale'    => 'en',
                'adapter'   => 'Array',
            ),
            'others'    => array(
                'zh'        => APPLICATION_PATH . '/languages/default.zh.php',
            ),
        ),
        'acl'               => APPLICATION_PATH . '/configs/acl.php',
        'redis' => array(
            'host'  => '127.0.0.1',
            'port'  => 6543,
            'database' => 0,
        ),
    ),
    'config' => array(
        APPLICATION_PATH . '/configs/defines.php',
    ),
);

if (APPLICATION_ENV === 'development'){
    $config['phpSettings']['display_startup_errors'] = 1;
    $config['phpSettings']['display_errors'] = 1;
    $config['resources']['frontController']['params']['displayExceptions'] = 1;
}
return $config;
