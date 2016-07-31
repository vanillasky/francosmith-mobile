<?

@include dirname(__FILE__) . "/../lib/library.php";

$_SESSION = array();

session_destroy();
setCookie('Xtime','',0,'/');
setcookie('gd_cart','',time() - 3600,'/');
setcookie('gd_cart_direct','',time() - 3600,'/');
expireCookieMemberInfo();

msg('로그아웃 되었습니다');
if (!$referer) $referer = ($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "/".$mobileRootDir."/index.php";

if ( !stripos($referer, "/m2/index.php") &&
     !stripos($referer, "/m2/goods") ) {

	go("../index.php");
} else {
	go($referer);
}

?>