<?php
$noDemoMsg = 1;
include dirname(__FILE__) . "/../_header.php";
@include $shopRootDir . "/lib/cart.class.php";

## 쿠폰 설정 정보
@include $shopRootDir . "/conf/config.pay.php";
@include $shopRootDir . "/conf/coupon.php";

if(!$cfgCoupon['use_yn']) $cfgCoupon['use_yn'] = 0;	// 쿠폰 사용여부(0:사용하지않음 1:사용)
if(!$cfgCoupon['range']) $cfgCoupon['range'] = 0;	// 중복할인여부(0:쿠폰할인, 회원할인 동시사용  1:회원할인만 사용 2: 쿠폰할인만 사용)
if(!$cfgCoupon['double']) $cfgCoupon['double'] = 0;	// 쿠폰 사용제한(0:한 주문에 여러 쿠폰 사용가능   1:한 주문에 한개 쿠폰만 사용)

## 회원할인 적용여부
if(!$cfgCoupon['use_yn'] || ($cfgCoupon['range'] != '2' && $cfgCoupon['use_yn']))$ableDc = true;
else $ableDc = false;

## 쿠폰 적용여부
if($cfgCoupon['range'] != '1' && $cfgCoupon['use_yn'])$ableCoupon = true;
else $ableCoupon = false;

$Cart = & load_class('Cart','Cart',$_COOKIE[gd_isDirect]);
$Goods = & load_class('Goods','Goods');
$coupon_price = & load_class('coupon_price','coupon_price');
$coupon_price->set_config($cfgCoupon);

if($Cart -> item)foreach($Cart -> item as $v) {
	if($abledc) $dc = getDcPrice($v[price],$Cart->dc);
	else $dc = 0;
	$arCategory = $Goods->get_goods_category($v['goodsno']);
	$coupon_price->set_item($v['goodsno'],$v['price'],$v['ea'],$arCategory,$v['opt'][0],$v['opt'][1],$v['addopt'],$v['goodsnm']);
	$goodsPrice += ($v['price'] + $v['addprice']) * $v['ea'];
}

$coupon_price->get_goods_coupon('order');
if($coupon_price->arCoupon)foreach($coupon_price->arCoupon as $data){
	if($data['excPrice'] && $data['excPrice'] > $goodsPrice) continue;
	if($data['pay_limit'] == "limited" && $data['limit_amount'] && $goodsPrice < $data['limit_amount']) continue;
	$data['apr']=0;
	if($data['sale']) $data['apr'] = @array_sum($data['sale']);
	if($data['reserve']) $data['apr'] = @array_sum($data['reserve']);
	$loop[] = $data;
}
$cart->dc = $member[dc]."%";
if(!$sess['m_no'])	echo("<script>alert('회원만 쿠폰사용이 가능합니다.!');self.close();</script>");
if(!$ableCoupon)	echo("<script>alert('쿠폰사용이 불가 합니다.!');self.close();</script>");

$tpl->assign(array('cart' => $cart));
$tpl->print_('tpl');
?>