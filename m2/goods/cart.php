<?
/*********************************************************
* ���ϸ�     :  goods/cart.php
* ���α׷��� :	��ٱ��� ����Ʈ ������
* �ۼ���     :  dn
* ������     :  2012.07.10
**********************************************************/
include dirname(__FILE__) . "/../_header.php";
@include $shopRootDir . "/conf/config.pay.php";
@include $shopRootDir . "/lib/cart.class.php";

# �ϴ� ��ٱ��Ϸ� �����Ƿ�, �ٷΰ��� ��Ű���� ������ �����.  khs
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

// ���̹� üũ�ƿ�
$naverCheckout = &load_class('naverCheckoutMobile', 'naverCheckoutMobile');
if($naverCheckout->isAvailable() && $naverCheckout->checkCart($cart))
{
	$tpl->assign('naverCheckout', $naverCheckout->getButtonTag('CART', true));
}

//������
if(is_file($shopRootDir . '/lib/payco.class.php')){
	$Payco = Core::loader('payco')->getButtonHtmlCode('CHECKOUT', true, 'goodsCart');
	if($Payco) $tpl->assign('Payco', $Payco);
}

$cart->calcu();

// ���̹� ���ϸ���
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