<?php
	include dirname(__FILE__) . "/../_header.php";
	@include $shopRootDir . "/conf/config.pay.php";
	@include $shopRootDir . "/lib/cart.class.php";
	setcookie('gd_isDirect','',time() - 3600,'/');

	if($_GET['cr']) {
		$cartReminder = Core::loader('CartReminder');
		$cartReminder->setCartReminderVisit($_GET['cr']);
	}

	### 변수할당
	$cartpage = true;
	$orderitem_mode = "cart";
	$mode = ($_POST[mode]) ? $_POST[mode] : $_GET[mode];

	$cart = new Cart;

	switch ($mode){
		case "modItem": $cart->modCart($_POST[ea]); break;
		case "delItem":
			arsort($_POST[idx]);
			foreach($_POST[idx] as $v) $cart->delCart($v);
		break;
		case "empty": $cart->emptyCart(); break;
	}
	if ($mode) header("location:cart.php");

	// 네이버 체크아웃
	$naverCheckout = &load_class('naverCheckoutMobile', 'naverCheckoutMobile');
	if($naverCheckout->isAvailable() && $naverCheckout->checkCart($cart)===true)
	{
		$tpl->assign('naverCheckout', $naverCheckout->getButtonTag('CART', true));
	}

	//페이코
	if(is_file($shopRootDir . '/lib/payco.class.php')){
		$Payco = Core::loader('payco')->getButtonHtmlCode('CHECKOUT', true, 'goodsCart');
		if($Payco) $tpl->assign('Payco', $Payco);
	}

	$cart->calcu();

	$tpl->assign('cart',$cart);
	$tpl->print_('tpl');
?>
