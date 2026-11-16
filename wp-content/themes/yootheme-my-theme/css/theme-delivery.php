<?php
/** Public, losslessly compressed CSS delivery for the local PHP development server. */
$file = isset($_GET['file'])&&is_string($_GET['file'])?basename($_GET['file']):'';
if (!preg_match('/^theme(?:\.[a-zA-Z0-9_-]+)*\.css$/D', $file)) {
    http_response_code(404);exit;
}
$path = __DIR__ . '/' . $file;
if (!is_file($path)) {http_response_code(404);exit;}
$encoding=$_SERVER['HTTP_ACCEPT_ENCODING']??'';
$gzip=function_exists('gzencode')&&preg_match('/\bgzip\b/i',$encoding)&&!preg_match('/gzip\s*;\s*q=0(?:\.0*)?(?:\s*,|$)/i',$encoding);
$etag = '"'.sha1(hash_file('sha256',$path).':'.($gzip?'gzip':'identity')).'"';
header('Content-Type: text/css; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=31536000, immutable');
header('Vary: Accept-Encoding');
header('ETag: '.$etag);
if (($_SERVER['HTTP_IF_NONE_MATCH']??'') === $etag) {http_response_code(304);exit;}
$css = file_get_contents($path);
if ($gzip) {
    $css = gzencode($css, 6);
    header('Content-Encoding: gzip');
}
header('Content-Length: '.strlen($css));
echo $css;
