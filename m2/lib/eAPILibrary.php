<?
/*********************************************************
* 파일명     :  lib/eAPILibrary.php
* 프로그램명 :  라이브러리 파일
* 작성자     :  dn
* 생성일     :  2012.03.29
**********************************************************/
### 헤더 설정
header("Content-Type: text/html; charset=EUC-KR");
header('P3P: CP="ALL CURa ADMa DEVa TAIa OUR BUS IND PHY ONL UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE LOC OTC"');
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

### 코어 라이브러리
include("lib.core.php");

### 상수 설정
if (get_magic_quotes_gpc()) {
	stripslashesArr($_POST);
	stripslashesArr($_GET);
}

define('DOCUMENT_ROOT',$_SERVER['DOCUMENT_ROOT']);
define('MOBILE_ROOT',DOCUMENT_ROOT.$mobileRootDir);
define('SHOP_ROOT',DOCUMENT_ROOT.'/shop');
/*
loadClass('db');

$db = new DB(SHOP_ROOT.'/conf/db.conf.php');
*/
### 브라우저 정보.
$browser_info = getBrowser();
define('BROWSER', $browser_info['browser']);
define('BROWSER_VERSION', $browser_info['version']);
if (BROWSER == 'msie') define('BROWSER_IE'.substr(BROWSER_VERSION, 0, 1), true);
if (!defined('BROWSER_IE6')) define('BROWSER_IE6', false);
if (!defined('BROWSER_IE7')) define('BROWSER_IE7', false);
if (!defined('BROWSER_IE8')) define('BROWSER_IE8', false);
if (!defined('BROWSER_IE9')) define('BROWSER_IE9', false);
unset($browser_info);
/*
session_start();

### 암호화 함수
function encode($arr,$mode=0) {

	$ip = (!$mode) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['SERVER_ADDR'];
	$mod = str_replace(".","",$ip);
	if ($mode){
		$file = file(dirname(__FILE__)."/../conf/serial.cfg.php");
		$md5 = trim($file[1]);
	} else {
		$Xmd5 = $md5 = md5(crypt(''));
		$_SESSION['Xmd5'] = $Xmd5;
	}
	for ($i=0;$i<strlen($md5);$i++) $key[] = base_convert($md5[$i],32,9);
	$key = implode("",$key)/$mod;
	$key = substr(log($key),3);
	$ret = base64_encode(serialize($arr));
	for ($i=0;$i<strlen($ret);$i++) $x[] = chr(ord($ret[$i])-$key[$i%strlen($key)]);
	return base64_encode(implode("",$x));
}

### 복호화 함수
function decode($keys,$mode=0) {

	if (!$keys) return;
	$ip = (!$mode) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['SERVER_ADDR'];
	$mod = str_replace(".","",$ip);
	$keys = base64_decode($keys);
	if ($mode) $file = file(dirname(__FILE__)."/../conf/serial.cfg.php");
	$md5 = (!$mode) ? $_SESSION['Xmd5'] : trim($file[1]);
	for ($i=0;$i<strlen($md5);$i++) $key[] = base_convert($md5[$i],32,9);
	$key = @implode("",$key)/$mod;
	$key = substr(log($key),3);
	for ($i=0;$i<strlen($keys);$i++) $x[] = chr(ord($keys[$i])+$key[$i%strlen($key)]);
	$data = unserialize(base64_decode(@implode("",$x)));
	if (!$data) $data = unserialize(base64_decode(@implode("",$x))."}");
	return $data;
}

### 시리얼 함수
function serial($key='',$dir='') {

	if($dir){
		return md5(base64_encode($_SERVER['SERVER_ADDR'].$dir."_".$key."_".$_SERVER['DOCUMENT_ROOT']));
	}else{
		return md5(base64_encode($_SERVER['SERVER_ADDR']."_".$key."_".$_SERVER['DOCUMENT_ROOT']));
	}

}

*/



?>