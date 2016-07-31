<?php

include dirname(__FILE__) . "/../_header.php";
@include $shopRootDir . "/lib/cart.class.php";
@include $shopRootDir . "/conf/config.pay.php";
@include $shopRootDir . "/conf/coupon.php";
@include $shopRootDir . "/conf/pg_mobile.$cfg[settlePg].php";
@include $shopRootDir . "/conf/pg_mobile.escrow.php";


if(class_exists('validation') && method_exists('validation','xssCleanArray')){
	$_POST = validation::xssCleanArray($_POST, array(
		validation::DEFAULT_KEY => 'text',
	));
}

// getordno 함수는 /shop/lib/lib.func.php 파일로 이동
if(!function_exists('getordno')) {
	function getordno(){
		global $db;
		$old = (time() - 86400) .'999';
		$db->query("delete from ".GD_ORDER_TEMP . " where ordno < '$old'");
		$ordno = time().sprintf("%03d",rand(0,999));
		$query = "insert into ".GD_ORDER_TEMP." (ordno)  values('$ordno')";
		$res = $db->query($query);
		if($res) return $ordno;
		else getordno();
	}
}

### 외부 유입의 경우(야후패션소호) 에스크로 자동실행
if ($_COOKIE[cc_inflow] == 'yahoo_fss'){
		$escrow['use'] = 'Y';
		$escrow['min'] = '0';
}

### 회원정보 가져오기
if ($sess){
	$query = "
	select * from
		".GD_MEMBER." a
		left join ".GD_MEMBER_GRP." b on a.level=b.level
	where
		m_no='$sess[m_no]'
	";
	$member = $db->fetch($query,1);
	$style_member = "readonly";
}

$mobilians = Core::loader('Mobilians');
$danal = Core::loader('Danal');

### 장바구니 쿠키 설정
if ($_POST[mode]=="addItem" && !$_COOKIE[gd_isDirect]) setcookie('gd_isDirect',1,0,'/');
$isDirect = ($_POST[mode]=="addItem" || $_COOKIE[gd_isDirect]) ? 1 : 0;
$cart = new Cart($isDirect);

if ($_POST[mode]=="addItem"){
	chkOpenYn($_POST[goodsno],"D",-1);	//진열여부 체크
	if ($_POST[multi_ea]) {
		$_keys = array_keys($_POST[multi_ea]);
		for ($i=0, $m=sizeof($_keys);$i<$m;$i++) {
			$_opt = $_POST[multi_opt][ $_keys[$i] ];
			$_ea = $_POST[multi_ea][ $_keys[$i] ];
			$_addopt = $_POST[multi_addopt][ $_keys[$i] ];
			$_addopt_inputable = $_POST[multi_addopt_inputable][ $_keys[$i] ];
			$cart->addCart($_POST[goodsno], $_opt, $_addopt, $_addopt_inputable, $_ea, $_POST[goodsCoupon]);
		}
	}
	else {
		$cart->addCart($_POST[goodsno], $_POST[opt], $_POST[addopt], $_POST[_addopt_inputable], $_POST[ea], $_POST[goodsCoupon]);
	}
}

if (!isset($_POST[idxs])) $_POST[idxs] = $_COOKIE['_posted_idxs'] ? unserialize( get_magic_quotes_gpc() ? stripslashes($_COOKIE['_posted_idxs']) : $_COOKIE['_posted_idxs']) : 'all';
//성인인증 상품 청소년보호법 적용 (성인인증을 안한 상태에서 성인인증 상품 주문시 성인인증 진행)
if($_POST[idxs] == 'all'){
	if ($cart->item) {
		foreach ($cart->item as $v){
			if ($v['use_only_adult'] == '1' && !Clib_Application::session()->canAccessAdult()){
				msg("성인인증이 필요한 상품/컨텐츠 가 포함되어 있습니다.\\n\\n성인(본인) 인증 후 주문하기를 진행해 주세요.","../intro/intro_adult.php?returnUrl=../goods/goods_cart.php");
				exit;
			}
		}
	}
} else {
	if (is_array($_POST[idxs]) && !empty($_POST[idxs])) foreach($_POST[idxs] as $idx) {
		$item = $cart->item[$idx];
		if ($item['use_only_adult'] == '1' && !Clib_Application::session()->canAccessAdult()){
			msg("성인인증이 필요한 상품/컨텐츠 가 포함되어 있습니다.\\n\\n성인(본인) 인증 후 주문하기를 진행해 주세요.","../intro/intro_adult.php?returnUrl=../goods/goods_cart.php");
			exit;
		}
	}
}
$cart->setOrder($_POST[idxs]);	// $_POST[idxs] 는 , 로 구분된 0 이상의 정수 또는 'all'

if($_POST['mode'] != 'addItem'){	//장바구니 주문하기에서 진열여부,판매종료 체크
	chkOpenYn($cart,"D",-1);
}

if(!$cart->item) echo "<script>history.back();</script>";

if($member){
	$cart->excep = $member['excep'];
	$cart->excate = $member['excate'];
	$cart->dc = $member[dc]."%";
}
$cart -> coupon = $_POST['coupon'];
$cart -> coupon_emoney = $_POST['coupon_emoney'];
$cart->calcu();


### s1스킨들을 위해 기본 배송비 가져오기
$param = array(
	'mode' => '0',
	'zipcode' => $member[zipcode],
	'emoney' => 0,
	'deliPoli' => 0,
	'coupon' => 0,
	'road_address' => $member['road_address'],
	'address' => $member['address'],
	'address_sub' => $member['address_sub'],
);

$delivery = getDeliveryMode($param);
$cart -> delivery = $delivery['price'];
$cart -> totalprice += $delivery['price'];

### 잔여 재고 체크........2007-07-18 modify
if ($cart->item) {
	foreach ($cart->item as $v){
		$cart->chkStock($v[goodsno],$v[opt][0],$v[opt][1],$v[ea]);
	}
}
//debug($sess);
//debug($_GET);


### 비회원일 경우 로그인창으로 이동
if ($_GET[guest]) setCookie('guest',1,0,'/');
if (!$sess && !$_GET[guest] && !$_COOKIE[guest]){
	setCookie('_posted_idxs', serialize($_POST[idxs]) ,0,'/');
	go("../mem/login.php?guest=1&returnUrl=$_SERVER[PHP_SELF]");
}
else {
	# 비회원에 따라 로그인페이지를 경유했을 수 있으므로, _posted_idxs 가 쿠키에 설정되었을 수 있다
	# 따라서, _posted_idxs 를 지원준다.
	setCookie('_posted_idxs', false , time() - 86400 ,'/');
}

### 주문번호 생성
$ordno = getordno();

$set['emoney']['base'] = pow(10,$set['emoney']['cut']);

### 적립금 사용범위
if(!$set['emoney']['emoney_use_range'])$tmp = $cart->goodsprice;
else $tmp = $cart->totalprice;
$tmp = $tmp - getDcPrice($cart->goodsprice,$cart->dc);
$emoney_max = getDcprice($tmp,$set[emoney][max])+0;

$r_deli = explode('|',$set['r_delivery']['title']);

if ($member){
	$member[zipcode] = explode("-",$member[zipcode]);
	$member[phone] = explode("-",$member[phone]);
	$member[mobile] = explode("-",$member[mobile]);
	$member[memo] = '';
	$tpl->assign($member);
}

$naverNcash = Core::loader('naverNcash');
$load_config_ncash = $config->load('ncash');

if ($naverNcash->canUseMobile() === false) {
	$load_config_ncash['useyn'] = 'N';
	$naverNcash->useyn = 'N';
}

// 네이버 마일리지 적립 예외상품 체크
$exceptionYN = $naverNcash->exception_goods($cart->item);
$load_config_ncash['exception_price'] = $naverNcash->exception_price($cart->item);

if ($naverNcash->useyn == 'Y' && $exceptionYN == 'N') {
	$tpl->assign('NaverMileageScript', $naverNcash->getMobileScript());
	$tpl->assign('NaverMileageConfig', $load_config_ncash);
	$tpl->assign('NaverMileageForm', include dirname(__FILE__).'/../../shop/proc/naver_mileage/accum_order_form_type_2.php');
	$tpl->assign('NaverMileageForm2', include dirname(__FILE__).'/../../shop/proc/naver_mileage/accum_order_form_type_3.php');
	$tpl->assign('NaverMileageCalc', include dirname(__FILE__).'/../../shop/proc/naver_mileage/accum_order_calc_type_1.php');
}

//페이코
if(is_file($shopRootDir . '/lib/payco.class.php')){
	$Payco = Core::loader('payco')->getButtonHtmlCode('EASYPAY', true, '');
	if($Payco) $tpl->assign('Payco', $Payco);
}

// 모빌리언스 서비스가 활성화 되어있을 시 모바일 결제 추가 셋팅
if ($mobilians->isEnabled() && $cfg['settleCellPg'] === 'mobilians') {
	$set['use_mobile']['h'] = 'on';
}

// 다날 서비스가 활성화 되어있을 시 모바일 결제 추가 셋팅
if ($danal->isEnabled() && $cfg['settleCellPg'] === 'danal') {
	$set['use_mobile']['h'] = 'on';
}

$termsPolicyCollection3 = getTermsGuideContents('terms', 'termsPolicyCollection3');
$tpl->assign('termsPolicyCollection3', $termsPolicyCollection3);

$tpl->assign('cart',$cart);
$tpl->assign('ordno',$ordno);
$tpl->print_('tpl');

?>
