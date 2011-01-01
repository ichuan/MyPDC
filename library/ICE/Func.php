<?php
/**
* global functions are defined here
*
* @author yc <iyanchuan@gmail.com>
*/

function isEmail($str)
{
    $zve = ICE_Global::get('Zend_Validate_EmailAddress', function(){ return new Zend_Validate_EmailAddress; });
    return $zve->isValid($str);
}

/**
 * computes the days between two datetimes
 *
 * @param $start,$end string like '2010-05-24 09:00:00'
 */
function dateSub($start, $end, $rawRet = false)
{
    if (empty($start) or empty($end))
        return false;
    list($date, $time) = explode(' ', $start);
    list($y, $m, $d) = explode('-', $date);
    list($h, $i, $s) = explode(':', $time);
    $tm1 = mktime($h, $i, $s, $m, $d, $y);
    list($date, $time) = explode(' ', $end);
    list($y, $m, $d) = explode('-', $date);
    list($h, $i, $s) = explode(':', $time);
    $tm2 = mktime($h, $i, $s, $m, $d, $y);
    $seconds = $tm2 - $tm1;
    if ($seconds <= 0)
        return false; 
    if ($rawRet)
        return $seconds;
    $days = (int)($seconds / 86400);
    return $days;
}

function psqlStr2Arr($str)
{
    if (empty($str) || $str == '{}')
        return array();
    return array_map('stripslashes', str_getcsv(trim($str, '{}'))); // use `stripslashes` cause the csv str queried out are slashed
}

function getTagsFromPsqlStr($str)
{
    return array_filter(psqlStr2Arr($str), function($i) { return $i != ',RESERVED,'; });
}

function getTagIdsFromPsqlStr($str)
{
    return array_filter(psqlStr2Arr($str), function($i) { return $i != '1'; });
}

function getTagCountersFromPsqlStr($str)
{
    return array_filter(psqlStr2Arr($str), function($i) { return $i != '0'; });
}

function ipCb($input, $func=null)
{
    $p = "(0|[1-9][0-9]{0,1}|1[0-9]{2}|2[0-4][0-9]|25[0-5])";
    $m = "([1-9]|[1-2][0-9]|3[0-2])";
    $n = "([1-9][0-9]{0,1}|1[0-9]{2}|2[0-4][0-9]|25[0-4])";
    $res = array(
    "/^$p\.$p\.$p\.$p$/",
    "/^$p\.$p\.$p\.$p\/$m$/",
    "/^$p\.$p\.$p\.$p-$n$/",
    "/^$p\.$p\.$p\.(\*)$/",
    "/^$p\.$p\.(\*)\.(\*)$/",
    "/^$p\.(\*)\.(\*)\.(\*)$/",
    "/^$p\.$p\.$p-$n\.(\*)$/",
    "/^(\*)\.(\*)\.(\*)\.(\*)$/",
    );

    for ($i=0; $i<count($res); $i++) {
        if (preg_match($res[$i], trim($input), $match)) {
            if($func) return $func($i, $match);
            else return true;
        }
    }
    return false;
}
function cbGetIpMinAndMax($seq, $m)
{
    if ($seq==0) {
        return array($m[0],$m[0]);
    } else if ($seq==1) {
        $pat1 = "";
        $pat2 = "";
        for ($i=0; $i<32; $i++) {
            if ($i< $m[5]) {
                $pat1 .= '1';
                $pat2 .= '0';
            } else {
                $pat1 .= '0';
                $pat2 .= '1';
            }
        }
        $min = bindec($pat1);
        $max = bindec($pat2);
        $ipnum = ip2long(implode(".", array($m[1], $m[2], $m[3], $m[4])));
        $minipnum = $min & $ipnum;
        $maxipnum = $max | $ipnum;
        return array(sprintf("%s", long2ip($minipnum)), sprintf("%s", long2ip($maxipnum)));
    } else if ($seq==2) {
        return array(sprintf("%s.%s.%s.%s", $m[1],$m[2],$m[3],$m[4]), sprintf("%s.%s.%s.%s", $m[1],$m[2],$m[3],$m[5]));
    } else if ($seq==3) {
        return array(sprintf("%s.%s.%s.0", $m[1],$m[2],$m[3]), sprintf("%s.%s.%s.255", $m[1],$m[2],$m[3]));
    } else if ($seq==4) {
        return array(sprintf("%s.%s.0.0", $m[1],$m[2]), sprintf("%s.%s.255.255", $m[1],$m[2]));
    } else if($seq == 5){
        return array(sprintf("%s.0.0.0", $m[1]), sprintf("%s.255.255.255", $m[1]));
    } else if($seq == 6){
        return array(sprintf("%s.%s.%s.0", $m[1], $m[2], $m[3]), sprintf("%s.%s.%s.255", $m[1], $m[2], $m[4]));
    }else{
        return array('0.0.0.0', '255.255.255.255');
    }
}

function isIpInRange($limit, $ip)
{
    $min = sprintf("%u", ip2long($limit[0]));
    $max = sprintf("%u", ip2long($limit[1]));
    $ipn = sprintf("%u", ip2long($ip));
    if ($ipn<=$max && $ipn>=$min)
        return true;
    return false;
}
function utf8Strlen($str)
{
    if (function_exists('mb_strlen'))
        return mb_strlen($str, 'UTF-8');
    $byteLen = strlen($str);
    $count = 0;
    for ($i = 0; $i < $byteLen; $i++){
        //see http://en.wikipedia.org/wiki/UTF-8#Description
        //192: 11000000, 128: 10000000
        if ((ord($str[$i]) & 192) == 128)
            continue;
        $count++;
    }
    return $count;
}

function utf8SubStr($str, $start = 0, $len = 99999999)
{
    if (function_exists('mb_substr'))
        return mb_substr($str, $start, $len, 'UTF-8');
    $byteLen = strlen($str);
    $count = 0; 
    $newStart = $newEnd = null; 
    for ($i = 0; $i < $byteLen; $i++){
        //see http://en.wikipedia.org/wiki/UTF-8#Description
        //192: 11000000, 128: 10000000
        if ((ord($str[$i]) & 192) == 128)
            continue; 
        if ($count == $start && is_null($newStart))
            $newStart = $i;
        elseif ($count == ($start + $len)){
            $newEnd = $i;
            break;
        }
        $count++;
    }
    if (is_null($newEnd))
        $newEnd = $i;
    return substr($str, $newStart, $newEnd - $newStart);
}

function isUTF8($str)
{
    $t = iconv('UTF-8', 'UTF-8', $str);
    if(empty($t))
        return false;
    return (md5($str)==md5($t));
}
/**
 * 用于下载附件时发送http头前的处理
 *
 */
function encodeFilename($name)
{
    $x = array("\\", '/', ":", "*", "?", '"', "<", ">", "|", "\n", "\r");
    $name = str_replace($x, "_", $name);
    // see http://tech.idv2.com/2009/03/05/use-utf8-in-download-filename/
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
        return rawurlencode($name);
    return $name;
}
/**
 * 去掉数组中的空值
 *
 * @param array
 */
function arrayTrim($x)
{
    foreach($x as $k => $v){
        is_string($v) && ($v=trim($v));
        if (empty($v)) 
            unset($x[$k]); 
    }
    return $x; 
}

/**
 * prepare a param array for insert() or update(), see Zend_Db for details
 *
 */
function makePgStrArrParam($filedName, $strArr)
{
    $ret = array();
    for ($i = 0, $j = count($strArr); $i < $j; $i++)
        $ret[$filedName . '[' . ($i + 1) . ']'] = $strArr[$i];
    return $ret;
}

/**
 * 从php数组生成psql整型数组的字符串表示, 会过滤掉特殊字符
 *
 * @param array
 */
function makePgIntArr($x)
{
    foreach($x as $k => $v)
        $x[$k] = (int)$v;
    return '{' . implode(',', $x) . '}';
}

/**
 * 从数组中选取第一个非空值
 *
 * @param array
 */
function tryThese($a)
{
    foreach ($a as $b)
        if ($b) return $b;
    return 0;
}

function _trans($x)
{
    return str_replace('\\', '\\\\\\\\', $x);
    //return str_replace(array('\\', '"', "'", '{', '}'), array('\\\\', '\\"', '\\\'', '\\{', '\\\}'), $x);
}

/**
 * 保存附件到附件目录，按年月分开，返回一个唯一的路径
 *
 * @param temp file path
 * @return the new path of the file
 */
function saveAttachment($path)
{
    if (!is_file($path))
        return false;
    $dir = ATTACH_PATH . strftime('/%Y/%m/', TIMESTAMP);
    !is_dir($dir) && mkdir($dir, 0777, true);
    $newPath = $dir . generateUniqueId();
    if (move_uploaded_file($path, $newPath))
        return $newPath;
    return false;
}

/**
 * 保存头像到头像目录，按年月分开，返回一个数组，包含大、中、小头像路径
 *
 * @param temp file path
 * @type 'png', 'jpg', or 'gif', the thunm image type
 * @return the new path of the file
 */
function saveAvatar($path, $type = 'png')
{
    if (!is_file($path))
        return false;
    $p = strftime('/%Y/%m/', TIMESTAMP);
    $dir = AVATAR_PATH . $p;
    !is_dir($dir) && mkdir($dir, 0777, true);
    $imgs = array(
        'large' => 73,
        'normal'=> 48,
        'mini'  => 24
    );
    foreach ($imgs as $k => $v){
        $id = generateUniqueId(16);
        $dst = $dir . $id . '.' . $type;
        if (!makeThumbImage($path, $dst, $type, $v, $v))
            return false;
        $imgs[$k] = $p . $id . '.' . $type;
    }
    return $imgs;
}

/**
 * @desc: generate a thumbnail for a given image
 * @param: source/dest image file path, expected type(png, jpg, gif), width, and height
 * @return: bool
 */
function makeThumbImage($src, $dst, $type, $width, $height)
{
    list ($o_width, $o_height, $o_type) = getimagesize($src);
    switch ($o_type){
        case 1:
            $createFunc = 'imagecreatefromgif';
            break;
        case 2:
            $createFunc = 'imagecreatefromjpeg';
            break;
        case 3:
            $createFunc = 'imagecreatefrompng';
            break;
        default:
            return false;
    }
    if ($o_width > $o_height)
        $height = $o_height / ($o_width / $width);
    else
        $width = $o_width / ($o_height / $height);
    $oldImg = $createFunc($src);
    $newImg = imagecreatetruecolor($width, $height);
    if (!imagecopyresampled($newImg, $oldImg, 0, 0, 0, 0, $width, $height, $o_width, $o_height))
        return false;
    switch ($type){
        case 'jpg':
            $outFunc = 'imagejpeg';
            break;
        case 'gif':
            $outFunc = 'imagegif';
            break;
        default:
            $outFunc = 'imagepng';
            break;
    }
    if (!$outFunc($newImg, $dst))
        return false;
    if (!imagedestroy($oldImg) || !imagedestroy($newImg))
        return false;
    return true;
}

/**
 * generate a uniqueid
 * from http://seld.be/notes/unpredictable-hashes-for-humans
 *
 * @param $maxLength length
 */
function generateUniqueId($maxLength = 32) 
{
    $entropy = '';

    // try ssl first
    if (function_exists('openssl_random_pseudo_bytes')) {
        $entropy = openssl_random_pseudo_bytes(64, $strong);
        // skip ssl since it wasn't using the strong algo
        if($strong !== true) {
            $entropy = '';
        }
    }

    // add some basic mt_rand/uniqid combo
    $entropy .= uniqid(mt_rand(), true);

    // try to read from the windows RNG
    if (class_exists('COM')) {
        try {
            $com = new COM('CAPICOM.Utilities.1');
            $entropy .= base64_decode($com->GetRandom(64, 0));
        } catch (Exception $ex) {
        }
    }

    // try to read from the unix RNG
    if (is_readable('/dev/urandom')) {
        $h = fopen('/dev/urandom', 'rb');
        $entropy .= fread($h, 64);
        fclose($h);
    }

    $hash = hash('whirlpool', $entropy);
    if ($maxLength) {
        return substr($hash, 0, $maxLength);
    }
    return $hash;
}    

function friendlySize($size)
{
    $formats = array(
        'TB' => 1099511627776,  // pow( 1024, 4)
        'GB' => 1073741824,     // pow( 1024, 3)
        'MB' => 1048576,        // pow( 1024, 2)
        'kB' => 1024,           // pow( 1024, 1)
        'B'  => 1,              // pow( 1024, 0)
    );
    foreach ($formats as $unit => $max)
        if ($size >= $max)
            return round($size / $max, 2) . $unit;
    return '0B';
}

/**
 *
 *@param $str '2010-8-18 1:43:49'
 *@return string '2 days ago'
 */
function friendlyDate($str = null, $suffix = '前')
{
    $ts = strtotime($str);
    if ($ts === false)
        return _t('几秒' . $suffix);
    $seconds = TIMESTAMP - $ts;
    $formats = array(
        _t('年' . $suffix)      => 31104000,
        _t('个月' . $suffix)    => 2592000,
        _t('天' . $suffix)      => 86400,
        _t('小时' . $suffix)    => 3600,
        _t('分钟' . $suffix)    => 60, 
        _t('秒' . $suffix)      => 1, 
    );
    foreach ($formats as $unit => $max)
        if ($seconds >= $max)
            return floor($seconds / $max) . ' ' . $unit;
    return _t('几秒' . $suffix);
}

function _t($str)
{
    if (!Zend_Registry::isRegistered('Zend_Translate'))
        throw new Exception('Zend_Translate is not registered yet!');
    return Zend_Registry::get('Zend_Translate')->translate($str);
}

function _e($str)
{
    echo _t($str);
}

function _h($str, $echo = true)
{
    if ($echo)
        echo htmlspecialchars($str);
    else
        return htmlspecialchars($str);
}

// input: "a,b ,c ,,"
// ouput: array('a','b','c')
function getArrayFromString($str, $delim = ',')
{
    $ret = array();
    foreach (explode($delim, $str) as $i){
        $t = trim($i);
        if (!empty($t)) $ret[] = $t;
    }
    return array_unique($ret);
}

function Highlight($string, $lang)
{
    static $geshi;
    if (!isset($geshi)){
        require_once 'geshi.php';
        $geshi = new GeSHi;
        $geshi->set_header_type(GESHI_HEADER_PRE_VALID);
        $geshi->enable_classes();
        $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
        $geshi->set_overall_style('font: normal normal 90% monospace; color: #000066; border: 1px solid #d0d0d0; background-color: #f0f0f0;', false);
        $geshi->set_line_style('color: #003030;', 'font-weight: bold; color: #006060;', true);
        $geshi->set_code_style('font-family:Monaco,"Courier New","DejaVu Sans Mono","Bitstream Vera Sans Mono",monospace;color: #000020;', true);
        $geshi->set_link_styles(GESHI_LINK, 'text-decoration:none!important;color: #000060;');
        $geshi->set_link_styles(GESHI_HOVER, 'background-color: #f0f000;');
        $geshi->set_link_target('_blank');
    }
    $geshi->set_source($string);
    $geshi->set_language($lang);
    return '<style type="text/css">' . $geshi->get_stylesheet(true) . "</style>\n" . $geshi->parse_code();
}

function buildUrlQuery(&$arr, $excludekeys = null)
{
    $ret = array();
    foreach ($arr as $k => $v)
        if (!empty($v) && ($excludekeys === null || !in_array($k, $excludekeys)))
            if (is_array($v))
                $ret[$k] = implode(',', $v);
            else
                $ret[$k] = $v;
    return http_build_query($ret);
}

// see http://code.google.com/p/cplan/source/browse/trunk/app/view/tag/index.tpl.php
function generateSizeArray(&$tags, $id = 'id', $counter = 'counter',  $smallest = 14, $largest = 44)
{
    if (empty($tags))
        return array();
    $counts = array();
    foreach ($tags as $tag)
        $counts[$tag[$id]] = (int)$tag[$counter];
    $minCount = min($counts);
    $spread = max($counts) - $minCount;
    if ($spread <= 0)
        $spread = 1;
    $fontSpread = $largest - $smallest;
    if ($fontSpread <= 0)
        $fontSpread = 1;
    $fontStep = $fontSpread / $spread;
    foreach ($counts as $id => $val)
        $counts[$id] = ceil($smallest + ($val - $minCount) * $fontStep);
    return $counts;
}


// send email via gmail
// todo use defined constants instead
function sendMail($addr, $name, $subject, $message)
{
    static $mailer;
    if (!isset($mailer)){
        require_once 'class.phpmailer.php';
        $mailer = new PHPMailer;
        $mailer->Mailer     = 'smtp';
        $mailer->Host       = 'ssl://smtp.gmail.com';
        $mailer->SMTPAuth   = true;
        $mailer->Port       = 465;
        $mailer->CharSet    = 'utf-8';
        $mailer->Username   = 'test@gmail.com'; # your gmail account
        $mailer->Password   = 'password'; # your gmail pasword
        $mailer->SetFrom('test@gmail.com', 'MyPDC');
    }
    $mailer->Subject    = $subject;
    $mailer->MsgHTML($message);
    $mailer->AddAddress($addr, $name);
    if (!$mailer->Send()){
        ICE_Log::crit($mailer->ErrorInfo);
        return false;
    }
    return true;
}

function hashes()
{
    return sha1(implode('|', func_get_args()));
}

function htmlSanitize($html)
{
    static $sanitizer;
    if (!isset($sanitizer)){
        require_once 'htmlpurifier/HTMLPurifier.standalone.php';
        $sanitizer = new HTMLPurifier;
    }
    return $sanitizer->purify($html);
}

// http://cn.php.net/manual/en/function.str-getcsv.php#91170
function str_putcsv($input, $delimiter = ',', $enclosure = '"')
{
    $fp = fopen('php://temp', 'r+');
    fputcsv($fp, $input, $delimiter, $enclosure);
    rewind($fp);
    $data = fread($fp, 1048576); // [changed]
    fclose($fp);
    return rtrim( $data, "\n" );
}

// input: 12, output: 12rd
// input: 14, output: 12th
function ordinalNumber($num)
{
    $num = "$num";
    switch($num[strlen($num) - 1]){
        case '1':
            return $num . 'st';
        case '2':
            return $num . 'nd';
        case '3':
            return $num . 'rd';
        default:
            return $num . 'th';
    }
}
