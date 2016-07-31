<?php
include dirname(__FILE__) . "/_header.php";

if (get_magic_quotes_gpc()) {
	stripslashes_all($_POST);
	stripslashes_all($_GET);
}

$forward = preg_split("/[?]+/",$_GET['refer']);
if(isset($forward[1]))
{
	$forward[1] = explode('=', $forward[1], 2);
	while($forward[1][1]!=urldecode($forward[1][1])) $forward[1][1] = urldecode($forward[1][1]);
	$forward[1][1] = urlencode($forward[1][1]);
	$forward[1] = implode('=', $forward[1]);
}

### 모바일에서 제공하는 페이지리스트
$url_check = array(
	'/' => 'index.php',
	'/index.php' => 'index.php',
	'/shop' => 'index.php',
	'/shop/index.php' => 'index.php',
	'/shop/main' => 'index.php',
	'/shop/main/html.php' => 'html.php',
	'/shop/mypage/mypage_orderview.php' => 'myp/orderview.php',	
	'/shop/goods/goods_view.php' => 'goods/view.php',
	'/shop/main/index.php' => 'index.php',
	'/shop/goods/goods_cart.php' => 'goods/cart.php',
	'/shop/goods/goods_list.php' => 'goods/list.php',
	'/shop/goods/goods_brand.php' => 'goods/brand.php',
	'/shop/member/login.php' => 'mem/login.php',
	'/shop/mypage/mypage_coupon.php' => 'myp/couponlist.php',
	'/shop/mypage/mypage_emoney.php' => 'myp/emoneylist.php',
	'/shop/mypage/mypage_wishlist.php' => 'myp/wishlist.php',
	'/shop/mypage/mypage_orderlist.php' => 'myp/orderlist.php',
	'/shop/member/myinfo.php' => 'myp/index.php',
	'/shop/service/agreement.php' => 'service/agrmt.php',
);

// brand 페이지 유무 확인
if ($forward[0] === '/shop/goods/goods_brand.php') {
	if (!file_exists('../'.$cfg['rootDir'].'/data/skin_mobileV2/'.$cfgMobileShop['tplSkinMobile'].'/goods/brand.htm')) {
		unset($url_check['/shop/goods/goods_brand.php']);
	}
}

### 모바일용 페이지인지 체크하여 모바일용 페이지로 링크 변환
foreach($url_check as $k => $v){
	if( $k == $forward[0] ){
		$param = "";
		foreach($_GET as $k2=>$v2){
			while($v2!=urldecode($v2)) $v2 = urldecode($v2);
			if($k2 != 'refer') $param .= "&".$k2."=".urlencode($v2);
		}
		header("location:http://".$_SERVER['HTTP_HOST'].$cfgMobileShop['mobileShopRootDir']."/".$v."?".$forward[1].$param);exit;
	}
}

### 상단에서 매칭되는 정보가 없다면 모두 메인페이지로 이동.
$queryList = array();
foreach ($_GET as $name => $value) {
	if ($name != 'refer') $queryList[] = $name.'='.urlencode($value);
}
$queryString = count($queryList) ? ('?'.implode('&', $queryList)) : '';
header("location:http://".$_SERVER['HTTP_HOST'].$cfgMobileShop['mobileShopRootDir'].$queryString);exit;

?>
