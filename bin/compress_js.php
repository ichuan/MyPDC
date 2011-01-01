<?php

$js_dir = realpath(dirname(__FILE__) . '/../application/data/static/js/');
$js_file = $js_dir . '/pdc.orig.js';
$out_file = $js_dir . '/pdc.js';

$res = curl_init('http://closure-compiler.appspot.com/compile');
curl_setopt($res, CURLOPT_RETURNTRANSFER, true);
curl_setopt($res, CURLOPT_POST, true);
curl_setopt($res, CURLOPT_POSTFIELDS, http_build_query(array(
    'js_code'           => file_get_contents($js_file),
    'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
    'output_format'     => 'text',
    'output_info'       => 'compiled_code',
)));

if (($ret = curl_exec($res)) !== false)
    file_put_contents($out_file, $ret);
else
    echo curl_error($res), "\n";
