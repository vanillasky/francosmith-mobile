<?
/*********************************************************
* 파일명     :  goods/cart.php
* 프로그램명 :	장바구니 리스트 페이지
* 작성자     :  dn
* 생성일     :  2012.07.10
**********************************************************/
include dirname(__FILE__) . "/../_header.php";
@include $shopRootDir . "/conf/config.pay.php";
@include $shopRootDir . "/lib/cart.class.php";

# 일단 장바구니로 왔으므로, 바로가기 쿠키값이 있으면 지운다.  khs
setcookie('gd_isDirect','',time() - 3600,'/');

if($_GET['cr']) {
	$cartReminder = Core::loader('CartReminder');
	$cartReminder->setCartReminderVisit($_GET['cr']);
}

$mode = ($_POST[mode]) ? $_POST[mode] : $_GET[mode];
$orderitem_mode = 'cart';
$cart = new Cart;

switch ($mode){
		case "modItem": $cart->modCart($_POST[ea]); break;
		case "delItem":
			arsort($_POST[idxs]);
			foreach($_POST[idxs] as $v) $cart->delCart($v);
		break;
		case "empty": $cart->emptyCart(); break;
		case "editOption": $cart->editOption($_POST); break;
}
if ($mode) header("location:cart.php");

// 네이버 체크아웃
$naverCheckout = &load_class('naverCheckoutMobile', 'naverCheckoutMobile');
if($naverCheckout->isAvailable() && $naverCheckout->checkCart($cart))
{
	$tpl->assign('naverCheckout', $naverCheckout->getButtonTag('CART', true));
}

//페이코
if(is_file($shopRootDir . '/lib/payco.class.php')){
	$Payco = Core::loader('payco')->getButtonHtmlCode('CHECKOUT', true, 'goodsCart');
	if($Payco) $tpl->assign('Payco', $Payco);
}

$cart->calcu();

// 네이버 마일리지
$naverNcash = Core::loader('naverNcash');
if ($naverNcash->canUseMobile() === false) $naverNcash->useyn = "N";
if ($naverNcash->useyn == 'Y' && $naverNcash->baseAccumRate) {
	$naverMileageAccumrateList = array();
	foreach ($cart->item as $key => $item) {
		$exceptionYN = $naverNcash->exception_goods(array(array('goodsno' => $item['goodsno'])));
		if ($exceptionYN == 'N') {
			$cart->item[$key]['NaverMileageAccum'] = true;
			$naverMileageAccumrateList[$item['goodsno']]['NaverMileageAccum'] = true;
		}
	}
	$tpl->assign('NaverMileageAccum', include dirname(__FILE__).'/../../shop/proc/naver_mileage/goods_accum_rate_type_3.php');
}

$tpl->assign('cart', $cart);
$tpl->print_('tpl');
?>