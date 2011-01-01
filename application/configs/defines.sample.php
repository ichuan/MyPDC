<?php

/**
* define constants here
*
* @author yc <iyanchuan@gmail.com>
*/

define('TIMESTAMP', time());
define('DATETIME', date('Y-m-d H:i:s', TIMESTAMP));
define('TIME', date('H:i', TIMESTAMP));
define('YEAR', date('Y', TIMESTAMP));
define('MONTH', date('m', TIMESTAMP));
define('VERSION', '0.1');
define('APPNAME', 'MyPDC');
define('APPNAME_HTML', '<span class="brown">My</span>PDC');
define('APPURL', 'http://mypdc.info');
define('APPDESC', '这里是一个关于文档分享、阅读的地方');
define('ITEMS_PER_PAGE', 10);//每页条目数
define('NOTE_TITLE_LEN', 30);//记事本标题长度
define('ENABLE_CACHE', true);//启用缓存

define('ATTACH_PATH', realpath(dirname(__FILE__) . '/../data/upload/'));  //附件保存路径
define('CAPTCHA_PATH', realpath(dirname(__FILE__) . '/../data/static/image/captcha/'));  //验证码缓存路径
define('CAPTCHA_URL', '/static/image/captcha/');
define('FONT_PATH', realpath(dirname(__FILE__) . '/../data/static/misc/Neuton.ttf'));  //字体路径

define('SERVER_PRK',
'-----BEGIN RSA PRIVATE KEY-----
MIICXgIBAAKBgQC7e+gnjSw/xTWBwe9ThOJIyRTSZKZadOx2ApE0/8+HsQZYSHV5
51l9gSLEgLG53FwChLwzpt9XZOUUApbxDNTEAHV1rqjBVNWMy9MoiPypI+GoNczl
9wG+7czKLPxhvw1t/KBJDGyjxbXbWJzsTaU8Tn8jN0R2d9tscj3saeVaZQIDAQAB
AoGBAJqTwsYjEFOCcO6RnKhWVDlLwaUDJYBBHeLvmQmIz36JyL6y+lXOKClsfdxo
eKfaBfJ5va4mtdWfOZ43yHv+ZdHWWbbQ0wR4p9crDD3rv4TCRohtapoJFqPB5300
YXDyxePcbRTcZICuIGBvg7x5Tphv+12ipiBypfl0Edp1qzBBAkEA7Kvnm67kqrcA
XGRA9zL9kz7XoVHPyB+KxM/gv0TTayPfvbPfb5BSrMRiFvQeeww2ifSDzZQHoGvx
amr+byn7EQJBAMrLo2zPYsAf/7XeDbcFiT4/YG1cB7RIYIqRx35lhM5JxDJFk5tU
JqFaaPBRgp3Dw4o2q2pyvZKmhsEAsHNjohUCQQC7UegzyX5FPCi198ePoDUheOi0
Twt06rorwhiwrHTVZQRuolJje8hj4997KWaCn4z/LZ+wc8yIhU4Dm4GcPI7xAkAF
9nzkqyhlK8uyBkhy9De329cy//y+AU7NZEHwZn3ELwkUzVTswUmtfHINBuhz/yiV
vMvHgn4ufLUDociObASNAkEAsKwDw/CezcOq6lSElbzaYoAHt9OWgXBN22teebb/
+cSJZA+GUwnL2KjhdjOe5CPmfqTODQf0jcBtDvFFOqK5oQ==
-----END RSA PRIVATE KEY-----'
); // used in google login

return array();
