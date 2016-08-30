<?php

header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");

@include dirname(__FILE__) . "/../lib/library.php";
@include $shopRootDir . "/conf/config.php";
@include $shopRootDir . "/conf/config.pay.php";
@include $shopRootDir . "/lib/cart.class.php";
@include $shopRootDir . "/conf/coupon.php";

$mobilians = Core::loader('Mobilians');
$danal = Core::loader('Danal');

if(class_exists('validation') && method_exists('validation','xssCleanArray')){
	$_POST = validation::xssCleanArray($_POST, array(
		validation::DEFAULT_KEY => 'text',
	));
}

$ordno = $_POST[ordno];
if(!$ordno)	msg('주문번호가 없습니다.',-1); //주문번호 널체크

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
}

// 네이버 마일리지
$naverNcash = &load_class('naverNcash','naverNcash');
if($_POST['save_mode'] != ''){
	$load_config_ncash = $config->load('ncash');
}else{
	$load_config_ncash = array();
}

$cart = & load_class('cart','Cart',$_COOKIE[gd_isDirect]);
$Goods = & load_class('Goods','Goods');
$coupon_price = & load_class('coupon_price','coupon_price');

### 주문서 정보 체크
if($_POST['paycoType']!='CHECKOUT'){ //payco 간편구매형
	$cart -> chkOrder();
}
chkCartMobile(&$cart, $cfgMobileShop['vtype_goods']);

$cart -> reset(); //주문시 상품가격 정보를 정확히 하기위해

if($member){
	$cart->excep = $member['excep'];
	$cart->excate = $member['excate'];
	$cart->dc = $member[dc]."%";
}
$cart -> coupon = $_POST['coupon'];
$cart->calcu();

$param = array(
	'mode' => '0',
	'zipcode' => @implode("",$_POST['zipcode']),
	'emoney' => $_POST['emoney'],
	'deliPoli' => $_POST['deliPoli'],
	'coupon' => $_POST['coupon'],
	'road_address' => $_POST['road_address'],
	'address' => $_POST['address'],
	'address_sub' => $_POST['address_sub'],
);
$delivery = getDeliveryMode($param);

if($_POST['settlekind'] == 't') {
	//주문상품별 배송비 등록
	$orderDeliveryItem = &load_class('orderDeliveryItem','orderDeliveryItem');
	$orderDeliveryItem->ordno = $ordno;
	$delivery_idxs = $orderDeliveryItem->set_delivery_data($delivery);
	if(isset($delivery_idxs['area_idx'])) {
		$area_idx = $delivery_idxs['area_idx'];
	}
}

$cart -> delivery = $delivery[price];
$cart -> totalprice += $delivery[price];

### 잔여 재고 체크
foreach ($cart->item as $v){
	$cart->chkStock($v[goodsno],$v[opt][0],$v[opt][1],$v[ea]);
	$arItemSno[] = $v[goodsno];
}

### 적립금 재계산
$_POST['coupon'] = $cart -> coupon;
$discount = $_POST[coupon] + $_POST[emoney] + $cart->dcprice + $_POST["totalUseAmount".$load_config_ncash['api_id']] + $cart->special_discount_amount;
if ($cart->totalprice - $discount < 0){
	$_POST[emoney] = $cart->totalprice - $_POST[coupon]-$cart->dcprice - $_POST["totalUseAmount".$load_config_ncash['api_id']]-$cart->special_discount_amount;
}

### 적립금 유효성 체크
chkEmoney($set[emoney],$_POST[emoney]);

### 쿠폰 사용 체크
checkCoupon($cart->item, $_POST['coupon'],$_POST['coupon_emoney'],$_POST['apply_coupon'],$_POST['settlekind']);

### 주문정보/쿠폰 유효성 체크
$coupon_price->set_config($cfgCoupon);
foreach($cart->item as $v){
	$arCategory = $Goods->get_goods_category($v['goodsno']);
	$coupon_price->set_item($v['goodsno'],$v['price'],$v['ea'],$arCategory,$v['opt'][0],$v['opt'][1],$v['addopt'],$v['goodsnm']);
}
$coupon_item = $coupon_price->get_goods_coupon('order');

$result = $coupon_price->check_coupon($_POST['coupon'],$_POST['coupon_emoney'],$_POST['settlekind'],$_POST['apply_coupon']);
if($result == "cash") $errmsg = "무통장 결제로만 사용가능한 쿠폰입니다.";
if($result == "sale"||$result == "reserve") $errmsg = "쿠폰 사용정보가 올바르지 않습니다.";
if($result !== true) msg($errmsg,-1);

## 쿠폰 사용정보
if($coupon_price->arCoupon && $sess['m_no']){

		if($coupon_price->arCoupon)foreach($coupon_price->arCoupon as $arCoupon){
			if(!in_array($arCoupon[sno],$_POST['apply_coupon'])) continue;

			if($arCoupon['applysno']){
				$setQuery = ",applysno = '$arCoupon[applysno]'";
			}else if($arCoupon['downsno']){
				$setQuery = ",downloadsno	= '$arCoupon[downsno]'";
				$ArrCouponSql[] = "update ".GD_COUPON_APPLY." set status = '1'  where sno = '$arCoupon[applysno]'";	// 다운로드 쿠폰만 상태를 변경.
			}

			if($arCoupon['sale'])	$couponDc = array_sum($arCoupon['sale']);
			if($arCoupon['reserve']) $couponEmoney = array_sum($arCoupon['reserve']);
			$ArrCouponSql[] = "insert into gd_coupon_order set
							ordno		= '$ordno',
							coupon		= '".mysql_real_escape_string($arCoupon[coupon])."',
							dc			= '$couponDc',
							emoney		= '$couponEmoney',
							regdt		= now(),
							m_no		=	'$sess[m_no]'".$setQuery;
		}
}

//도로명주소
if($_POST['road_address']) {
	$road_address = $_POST['road_address'].' '.$_POST['address_sub'];
} else {
	$road_address = "";
}

### 주문데이타 가공
$_POST[phoneOrder]		= @implode("-",$_POST[phoneOrder]);
$_POST[mobileOrder]		= @implode("-",$_POST[mobileOrder]);
$_POST[phoneReceiver]	= @implode("-",$_POST[phoneReceiver]);
$_POST[mobileReceiver]	= @implode("-",$_POST[mobileReceiver]);
$_POST[zipcode]			= @implode("-",$_POST[zipcode]);

$discount = $_POST[coupon] + $_POST["totalUseAmount".$load_config_ncash['api_id']] + $_POST[emoney] + $cart->dcprice + $cart->special_discount_amount;

// 결제금액 갱신
$settleprice = $cart->totalprice - $discount;
$_POST['settleprice']		= $settleprice;
$_REQUEST['settleprice']	= $settleprice;

### PG모듈 이용시 처리 (무통장,적립금결제 제외)
if (in_array($_POST[settlekind],array("c","o","v","h","p","t"))){
	$qrTmp = "step2=50,";
	$qrTmp2 = "istep=50,";
}

if(!$set['delivery']['deliverynm']) $a_tmp[] = $set['delivery']['deliverynm'];
else $a_tmp[] = '기본 배송';
$b_tmp = @explode('|',$set['r_delivery']['title']);
$r_deli = @array_merge($a_tmp,$b_tmp);

## 쿠폰 사용정보 체크
if($_POST['apply_coupon'] && $sess['m_no']){
	foreach($_POST['apply_coupon'] as $v){

		// offline 쿠폰
		if (preg_match("/^off_/i", $v)) {
			$query = "
			SELECT offdown.*
			FROM ".GD_OFFLINE_DOWNLOAD." AS offdown
			INNER JOIN ".GD_OFFLINE_COUPON." AS offcoupon
			ON offdown.coupon_sno = offcoupon.sno
			INNER JOIN ".GD_COUPON_ORDER." AS coupon_order
			ON coupon_order.downloadsno = offdown.sno AND coupon_order.m_no = '".$sess['m_no']."'
			WHERE offcoupon.sno = '".intval(preg_replace('/[^0-9]/','',$v))."'
			GROUP BY coupon_order.m_no
			";
		}
		// online 쿠폰 (온라인 다운로드 쿠폰 포함)
		else {
			$query = "
			SELECT
				CP.*,
				COUNT(O.m_no) AS usecnt
			FROM ".GD_COUPON_APPLY." AS CA
			INNER JOIN ".GD_COUPON." AS CP
			ON CA.couponcd = CP.couponcd
			INNER JOIN ".GD_COUPON_ORDER." AS O
			ON O.applysno = CA.sno AND O.m_no = '".$sess['m_no']."'
			WHERE CA.couponcd = '$v'
			GROUP BY O.m_no
			";
		}

		if (($cp = $db->fetch($query,1)) != false) {	// false or null
			if ((int)$cp['coupontype'] === 1) {	// 재사용이 가능할 수 있는 다운로드 쿠폰
				if ((int)$cp['dncnt'] > 0 && $cp['dncnt'] <= $cp['usecnt']) {
					msg('이미 사용된 쿠폰입니다.');
					exit;
				}
			}
			else {
				msg('이미 사용된 쿠폰입니다.');
				exit;
			}
		}
		else {
			// valid coupon
		}

	}
}

if ($_POST['settlekind'] == 'h' && $cfg['settleCellPg'] === 'mobilians' && $mobilians->isEnabled()) {
	$qrTmp .= "`pg`='mobilians',";
	$settlePg = 'mobilians';
}
else if ($_POST['settlekind'] == 'h' && $cfg['settleCellPg'] === 'danal' && $danal->isEnabled()) {
	$qrTmp .= "`pg`='danal',";
	$settlePg = 'danal';
}
else if ($_POST['settlekind'] == 't') {
	$payco = Core::loader('payco');
	if($payco->checkNcash($load_config_ncash['useyn'], $_POST['totalUseAmount'.$load_config_ncash['api_id']]) == true){
		$payco->msgLocate("PAYCO 결제수단은 네이버 마일리지 및 캐쉬 사용이 불가합니다.", $_POST['paycoType'], 'Y');
	}
	$qrTmp .= $payco->getqrTmp($_POST['paycoType']);
	$settlePg = 'payco';
}
else {
	$qrTmp .= "`pg`='".$cfg['settlePg']."',";
	$settlePg = $cfg['settlePg'];
}

### 주문번호 중복여부 체크
list ($chk,$pre_settlekind) = $db->fetch("select ordno,settlekind from ".GD_ORDER." where ordno='$ordno'");

if ($chk){

	// 복합과세 적용 - 15.04.28 - su
	$order = new order();
	$order->load($ordno);
	$tax = $order->getTaxAmount();
	$vat = $order->getVatAmount();
	$taxfree = $order->getTaxFreeAmount();

	if (in_array($_POST[settlekind],array("c","o","v","h"))){
		### 결제방법이 변경된 경우 업데이트 처리
		$paycoOrderInfoResetQuery = '';
		if($pre_settlekind == 't'){
			$paycoOrderInfoResetQuery = ", settleInflow = NULL, payco_settle_type = NULL, pg='".$cfg['settlePg']."' ";
		}
		if ($_POST[settlekind]!=$pre_settlekind) $db->query("update ".GD_ORDER." set settlekind='$_POST[settlekind]' $paycoOrderInfoResetQuery where ordno='$ordno'");
		switch ($settlePg)
		{
		  case "kcp":
        echo "<script>
          if(parent.document.getElementsByName('good_mny')[0].value == '".$settleprice."'){
            parent.kcp_AJAX();
          }else{
            alert('결제금액이 올바르지 않습니다.');
            parent.location.replace('order.php');
          }
          </script>";
        exit;
			case "allat":
				echo "<script>
					if(parent.document.getElementsByName('allat_amt')[0].value == '".$settleprice."'){
						parent.approval();
					}else{
						alert('결제금액이 올바르지 않습니다.');
						parent.location.replace('order.php');
					}
					</script>";
				exit;
			case "allatbasic":
				echo "<script>
				if(parent.document.getElementsByName('allat_amt')[0].value == '".$settleprice."'){
					parent.approval();
				}else{
					alert('결제금액이 올바르지 않습니다.');
				parent.location.replace('order.php');
				}
			</script>";
			exit;
			case "inicis":
				// 이니시스 4.1 은 복합과세 시 부가세와 면세를 전달  **주의!! 필드명 tax가 부과세임
				echo "<script>
					if(parent.document.getElementsByName('P_AMT')[0].value == '".$settleprice."'){
						parent.document.getElementsByName('P_TAX')[0].value = '".$vat."';
						parent.document.getElementsByName('P_TAXFREE')[0].value = '".$taxfree."';
						parent.on_card();
					}else{
						alert('결제금액이 올바르지 않습니다.');
						parent.location.replace('order.php');
					}
					</script>";
				exit;
			case "inipay":
				// 이니시스 5.0 은 복합과세 시 부가세와 면세를 전달  **주의!! 필드명 tax가 부과세임
				echo "<script>
					if(parent.document.getElementsByName('P_AMT')[0].value == '".$settleprice."'){
						parent.document.getElementsByName('P_TAX')[0].value = '".$vat."';
						parent.document.getElementsByName('P_TAXFREE')[0].value = '".$taxfree."';
						parent.on_card();
					}else{
						alert('결제금액이 올바르지 않습니다.');
						parent.location.replace('order.php');
					}
					</script>";
				exit;
			case "lgdacom":
				// 엘지데이콤 은 복합과세 시 면세를 전달
				echo "<script>
					if(parent.document.getElementsByName('LGD_AMOUNT')[0].value == '".$settleprice."'){
						parent.document.getElementsByName('LGD_TAXFREEAMOUNT')[0].value = '".$taxfree."';
						parent.launchCrossPlatform();

					}else{
						alert('결제금액이 올바르지 않습니다.');
						parent.location.replace('order.php');
					}
					</script>";
				exit;
			case "agspay":
				echo "<script>
					if(parent.document.getElementsByName('Amt')[0].value == '".$settleprice."'){
						parent.Pay();
					}else{
						alert('결제금액이 올바르지 않습니다.');
						parent.location.replace('order.php');
					}
					</script>";
				exit;
			case "easypay":
				echo "<script>
					if(parent.document.getElementsByName('sp_pay_mny')[0].value == '".$settleprice."'){
						parent.f_submit();
					}else{
						alert('결제금액이 올바르지 않습니다.');
						parent.location.replace('order.php');
					}
					</script>";
			exit;
			case "settlebank":
				echo "<script>
					if(parent.document.getElementsByName('PAmt')[0].value == '".$settleprice."'){
						parent.submitForm();
					}else{
						alert('결제금액이 올바르지 않습니다.');
						parent.location.replace('order.php');
					}
					</script>";
			exit;
			case 'mobilians' :
				exit('
				<script type="text/javascript">
				var f = parent.document.frmSettle;
				f.action = "'.$cfg['rootDir'].'/order/card/mobilians/card_gate.php?pc=true&isMobile=true";
				f.target = "ifrmHidden";
				f.submit();
				</script>
				');
			case 'danal' :
				exit('
				<script type="text/javascript">
				var f = parent.document.frmSettle;
				f.action = "'.$cfg['rootDir'].'/order/card/danal/card_gate.php?isMobile=true";
				f.target = "ifrmHidden";
				f.submit();
				</script>
				');
		}
	} else {
		if($_POST[settlekind] == 't') {
			$payco->msgLocate("동일한 주문번호가 존재합니다", $_POST['paycoType'], 'Y');
		}
		else {
			msg("동일한 주문번호가 존재합니다","order.php","parent");
		}
	}
}

### 적립이 결제가 기준일때 적립금 재설정
if($set['emoney']['chk_goods_emoney'] == '0' && $set['emoney']['emoney_standard'] == '1') {
	$cart->resetReserveAmount($settleprice);
}

### 회원 추가 적립금 설정
switch($member['add_emoney_type']) {
	case 'goods':
		$tmp_price = $cart->goodsprice;
		break;
	case 'settle_amt':
		$tmp_price = $settleprice;
		break;
	default:
		$tmp_price = 0;
		break;
}
$cart->bonus += getExtraReserve($member['add_emoney'], $member['add_emoney_type'], $member['add_emoney_std_amt'], $tmp_price, $cart);

if ($delivery[type]=="후불" && $delivery[freeDelivery] =="1") $delivery['msg'] = "0원";

if(isset($_POST['reqTxId'.$load_config_ncash['api_id']]) && strlen(trim($_POST['reqTxId'.$load_config_ncash['api_id']]))>0)
{
	$qrTmp .= "settlelog='----------------------------------------".PHP_EOL.
	"네이버 마일리지 ".(int)$_POST['mileageUseAmount'.$load_config_ncash['api_id']]."원 결제시도".PHP_EOL.
	"네이버 캐쉬 ".(int)$_POST['cashUseAmount'.$load_config_ncash['api_id']]."원 결제시도".PHP_EOL.
	"----------------------------------------',".PHP_EOL;

	$naverMileageTx = Core::loader('NaverMileageTransaction', $ordno);
	$naverMileageTx->setTransactionId($_POST['reqTxId'.$load_config_ncash['api_id']]);
	$naverMileageTx->setOrderAmount($cart->totalprice);
	$naverMileageTx->setCalcAmount($cart->goodsprice);
	$naverMileageTx->setLegacySaveMode($_POST['save_mode']);
	$naverMileageTx->setMileageUseAmount($_POST['mileageUseAmount'.$load_config_ncash['api_id']]);
	$naverMileageTx->setCashUseAmount($_POST['cashUseAmount'.$load_config_ncash['api_id']]);
	$naverMileageTx->setDiscountAmount($_POST['emoney']+$cart->coupon+$cart->dcprice+$cart->special_discount_amount);
	foreach ($cart->item as $item) {
		$naverMileageTx->addOrderItem($item['goodsno'], $item['goodsnm'], $item['ea'], $item['price']);
	}
	$naverMileageTx->createTransaction();
}
else
{
	if($_POST['save_mode']=='unused') $qrTmp .= "`ncash_save_yn`='u',";
}

// 구매내용확인
if ($cfg['orderDoubleCheck'] == 'y' && isset($_POST['doubleCheck'])) {
	$qrTmp .= sprintf("`doubleCheck`='%s',", ($_POST['doubleCheck'] == 'y' ? 'y' : 'n'));
}

### 주문정보 저장
$query = "
insert into ".GD_ORDER." set $qrTmp
	ordno			= '$ordno',
	nameOrder		= '".trim($_POST[nameOrder])."',
	email			= '$_POST[email]',
	phoneOrder		= '$_POST[phoneOrder]',
	mobileOrder		= '$_POST[mobileOrder]',
	nameReceiver	= '$_POST[nameReceiver]',
	phoneReceiver	= '$_POST[phoneReceiver]',
	mobileReceiver	= '$_POST[mobileReceiver]',
	zipcode			= '$_POST[zipcode]',
	zonecode		= '$_POST[zonecode]',
	address			= '$_POST[address] $_POST[address_sub]',
	road_address	= '$road_address',
	settlekind		= '$_POST[settlekind]',
	settleprice		= '$settleprice',
	prn_settleprice	= '$settleprice',
	goodsprice		= '{$cart->goodsprice}',
	deli_title		= '".$r_deli[$_POST['deliPoli']]."',
	delivery		= '{$cart->delivery}',
	deli_type		= '".$delivery['type']."',
	deli_msg		= '".$delivery['msg']."',
	coupon			= '$_POST[coupon]',
	emoney			= '$_POST[emoney]',
	memberdc		= '".$cart->dcprice ."',
	o_special_discount_amount		= '".$cart->special_discount_amount ."',
	reserve			= '{$cart->bonus}',
	bankAccount		= '$_POST[bankAccount]',
	bankSender		= '$_POST[bankSender]',
	m_no			= '$sess[m_no]',
	ip				= '$_SERVER[REMOTE_ADDR]',
	referer			= '$referer',
	memo			= '$_POST[memo]',
	inflow	=	'$_COOKIE[cc_inflow]',
	orddt			= now(),
	coupon_emoney	=	'".$_POST[coupon_emoney]."',
	cashbagcard		= '".$cashbagcard."',
	cbyn			= 'N',
	mobilepay		= 'y'
";
if(!$db->query($query))msg('정상적으로 주문 접수가 되지 않았습니다.\n다시 한번 시도하세요!!',0);

### 주문상품 저장
foreach ($cart->item as $k=>$item){

	unset($addopt);

	### 상품 테이블에서 공급가 가져오기
	list ($item[supply]) = $db->fetch("select supply from ".GD_GOODS_OPTION." where goodsno='$item[goodsno]' and opt1='{$item[opt][0]}' and opt2='{$item[opt][1]}' and go_is_deleted <> '1' and go_is_display = '1'");

	### 추가옵션
	if (is_array($item[addopt])){
		foreach ($item[addopt] as $v) $addopt[] = $v[optnm].":".$v[opt];
		$addopt = @implode("^",$addopt);
	}
	$memberdc = $item['memberdc'];

	$item[goodsnm] = addslashes(strip_tags($item[goodsnm]));

	### 제조사, 브랜드
	list($maker, $brandnm, $tax, $delivery_type, $goods_delivery, $usestock) = $db -> fetch("select maker, brandnm, tax, delivery_type, goods_delivery, usestock from ".GD_GOODS." left join ".GD_GOODS_BRAND." on brandno=sno where goodsno='{$item[goodsno]}'");
	$maker = addslashes($maker);
	$brandnm = addslashes($brandnm);
	$item_deli_msg = "";
	if($delivery_type == 3){
		$item_deli_msg = "착불";
		if($goods_delivery) $item_deli_msg .= " ".number_format($goods_delivery)." 원";
	}
	if($usestock == 'o') $stockable = "y";
	else $stockable = "n";

	// 상품별 쿠폰 금액 (할인, 적립금)
	$coupon = 0;
	$coupon_emoney = 0;

	foreach($coupon_price->arCoupon as $arCoupon) {

		if (!in_array($arCoupon['sno'],$_POST['apply_coupon']) || isset($arCoupon['sale']['order']) || isset($arCoupon['reserve']['order'])) {
			continue;
		}

		$_same_goods_count_cart = 0;
		foreach ($cart->item as $_item) {
			if ($_item['goodsno'] == $item['goodsno']) {
				$_same_goods_count_cart = $_same_goods_count_cart + $_item['ea'];
			}
		}

		$_coupon = ($_coupon = (int)$arCoupon['sale'][$item['goodsno']]) ? $_coupon / $_same_goods_count_cart : 0;
		$_coupon_emoney = ($_coupon_emoney = (int)$arCoupon['reserve'][$item['goodsno']]) ? $_coupon_emoney / $_same_goods_count_cart : 0;

		$coupon += $_coupon;
		$coupon_emoney += $_coupon_emoney;
	}

	$oi_delivery_idx = '';
	if(is_array($delivery_idxs[$item['goodsno']])) $oi_delivery_idx = $delivery_idxs[$item['goodsno']][$item['optno']];
	else $oi_delivery_idx = $delivery_idxs[$item['goodsno']];

	// 모바일 특가 정보 price에서 차감 dn 2013-05-21
	$query = "
	insert into ".GD_ORDER_ITEM." set $qrTmp2
		ordno			= '$ordno',
		goodsno			= '$item[goodsno]',
		goodsnm			= '$item[goodsnm]',
		opt1			= '".mysql_real_escape_string($item[opt][0])."',
		opt2			= '".mysql_real_escape_string($item[opt][1])."',
		`optno`			= '{$item[optno]}',
		addopt			= '".mysql_real_escape_string($addopt)."',
		price			= '".($item[price]+$item[addprice])."',
		supply			= '$item[supply]',
		reserve			= '$item[reserve]',
		extra_reserve		= '{$item[extra_reserve]}',
		memberdc		= '$memberdc',
		ea				= '$item[ea]',
		maker			= '$maker',
		brandnm			= '$brandnm',
		tax				= '$tax',
		deli_msg		= '$item_deli_msg',
		stockable		= '$stockable',
		coupon = '$coupon',
		coupon_emoney = '$coupon_emoney',
		oi_delivery_type = '{$item[delivery_type]}',
		oi_goods_delivery = '{$item[goods_delivery]}',
		oi_special_discount_amount = '{$item[special_discount_amount]}',
		oi_delivery_idx = '{$oi_delivery_idx}',
		oi_area_idx = '{$area_idx}'
	";
	$db->query($query);

}

## 장바구니 리마인드
if($_SESSION['cartReminder'] > 0) {
	$cartReminder = Core::loader('CartReminder');
	$cartReminder->setCartReminderOrder();
}

## 쿠폰 사용 정보 저장
if($ArrCouponSql) foreach($ArrCouponSql as $v)$db->query($v);

// 복합과세 적용 - 15.04.28 - su
$order = new order();
$order->load($ordno);
$tax = $order->getTaxAmount();
$vat = $order->getVatAmount();
$taxfree = $order->getTaxFreeAmount();

if (in_array($_POST[settlekind],array("c","o","v","h","t"))){
	switch ($settlePg)
	{
		case "kcp":
        echo "<script>
          if(parent.document.getElementsByName('good_mny')[0].value == '".$settleprice."'){
            parent.kcp_AJAX();
          }else{
            alert('결제금액이 올바르지 않습니다.');
            parent.location.replace('order.php');
          }
          </script>";
        exit;
		case "allat":
			echo "<script>
				if(parent.document.getElementsByName('allat_amt')[0].value == '".$settleprice."'){
					parent.approval();
				}else{
					alert('결제금액이 올바르지 않습니다.');
					parent.location.replace('order.php');
				}
				</script>";
			exit;

		case "allatbasic":
			echo "<script>
			if(parent.document.getElementsByName('allat_amt')[0].value == '".$settleprice."'){
				parent.approval();
			}else{
				alert('결제금액이 올바르지 않습니다.');
				parent.location.replace('order.php');
			}
		</script>";
		exit;

		case "inicis":
			// 이니시스 4.1 은 복합과세 시 부가세와 면세를 전달  **주의!! 필드명 tax가 부과세임
			echo "<script>
				if(parent.document.getElementsByName('P_AMT')[0].value == '".$settleprice."'){
					parent.document.getElementsByName('P_TAX')[0].value = '".$vat."';
					parent.document.getElementsByName('P_TAXFREE')[0].value = '".$taxfree."';
					parent.on_card();
				}else{
					alert('결제금액이 올바르지 않습니다.');
					parent.location.replace('order.php');
				}
				</script>";
			exit;
		case "inipay":
			// 이니시스 5.0 은 복합과세 시 부가세와 면세를 전달  **주의!! 필드명 tax가 부과세임
			echo "<script>
				if(parent.document.getElementsByName('P_AMT')[0].value == '".$settleprice."'){
					parent.document.getElementsByName('P_TAX')[0].value = '".$vat."';
					parent.document.getElementsByName('P_TAXFREE')[0].value = '".$taxfree."';
					parent.on_card();
				}else{
					alert('결제금액이 올바르지 않습니다.');
					parent.location.replace('order.php');
				}
				</script>";
			exit;
		case "lgdacom":
			// 엘지데이콤 은 복합과세 시 면세를 전달
			echo "<script>
				if(parent.document.getElementsByName('LGD_AMOUNT')[0].value == '".$settleprice."'){
					parent.document.getElementsByName('LGD_TAXFREEAMOUNT')[0].value = '".$taxfree."';
					parent.launchCrossPlatform();
				}else{
					alert('결제금액이 올바르지 않습니다.');
					parent.location.replace('order.php');
				}
				</script>";
			exit;
		case "agspay":
			echo "<script>
				if(parent.document.getElementsByName('Amt')[0].value == '".$settleprice."'){
					parent.Pay();
				}else{
					alert('결제금액이 올바르지 않습니다.');
					parent.location.replace('order.php');
				}
				</script>";
			exit;
		case "easypay":
			echo "<script>
				if(parent.document.getElementsByName('sp_pay_mny')[0].value == '".$settleprice."'){
					parent.f_submit();
				}else{
					alert('결제금액이 올바르지 않습니다.');
					parent.location.replace('order.php');
				}
				</script>";
			exit;
		case "settlebank":
			echo "<script>
				if(parent.document.getElementsByName('PAmt')[0].value == '".$settleprice."'){
					parent.submitForm();
				}else{
					alert('결제금액이 올바르지 않습니다.');
					parent.location.replace('order.php');
				}
				</script>";
			exit;
		case 'mobilians' :
			exit('
			<script type="text/javascript">
			var f = parent.document.frmSettle;
			f.action = "'.$cfg['rootDir'].'/order/card/mobilians/card_gate.php?pc=true&isMobile=true";
			f.target = "ifrmHidden";
			f.submit();
			</script>
			');
		case 'danal' :
			exit('
			<script type="text/javascript">
			var f = parent.document.frmSettle;
			f.action = "'.$cfg['rootDir'].'/order/card/danal/card_gate.php?isMobile=true";
			f.target = "ifrmHidden";
			f.submit();
			</script>
			');
		case 'payco' :

			if($_POST['paycoType'] == 'CHECKOUT'){
				include $shopRootDir . '/order/card/payco/card_gate.php';
			}
			else {
				echo '
				<script type="text/javascript">
				var f = parent.document.frmSettle;

				//form 원본 정보
				var oriAction	= f.action;
				var oriTarget	= f.target;

				f.action = "'.$cfg['rootDir'].'/order/card/payco/card_gate.php?isMobile=Y";
				f.target = "ifrmHidden";
				f.submit();

				f.action = oriAction;
				f.target = oriTarget;
				</script>
				';
			}
			exit;

			break;
	}
	exit;
} else

if ($_POST[settlekind]=="d"){
	//전액할인,적립금 체크
	if($settleprice>0 || $discount==0){	//실 결제금액이 0보다 크거나 or 할인금액이 0원이면
		msg('유효한 결제금액이 아닙니다.',-1);
 		exit;
	}

	// 네이버 마일리지 결제 승인 API
	$ncashResult = $naverNcash->payment_approval($ordno, true);
	if($ncashResult===false)
	{
		msg('네이버 마일리지 사용에 실패하였습니다.', 'order_fail.php?ordno='.$ordno,'parent');
		exit();
	}
	ctlStep($ordno,1,"stock");
} else if ($_POST[settlekind]=="a"){
	// 네이버 마일리지 결제 승인 API
	$ncashResult = $naverNcash->payment_approval($ordno, false);
	if($ncashResult===false)
	{
		msg('네이버 마일리지 사용에 실패하였습니다.', 'order_fail.php?ordno='.$ordno,'parent');
		exit();
	}

	### 무통장 주문 송신
	@include $shopRootDir . '/lib/bank.class.php';
	$bk = new Bank( 'send', $ordno );
}

### 재고 처리
setStock($ordno);

### 상품구입시 적립금 사용
if ($sess[m_no] && $_POST[emoney]){
	setEmoney($sess[m_no],-$_POST[emoney],"상품구입시 적립금 결제 사용",$ordno);
}

### 현금영수증 신청
if ($_POST['cashreceipt'] == 'Y'){
	ob_start();
	@include $shopRootDir . '/lib/cashreceipt.class.php';
	$cashreceipt = new cashreceipt();
	$indata = array();
	$indata['ordno'] = $ordno;
	$indata['useopt'] = $_POST['cashuseopt'];
	$indata['certno'] = $_POST['cashcertno'];
	$resid = $cashreceipt->putUserReceipt($indata);
	ob_end_clean();
}

### 주문확인메일
$modeMail = 0;
if ($_POST[email] && $cfg["mailyn_0"]=="y"){
	$_POST['address'] = $_POST['address']. ' ' .$_POST['address_sub'];
	$_POST['str_settlekind'] = $r_settlekind[ $_POST['settlekind'] ];

	@include_once $shopRootDir . "/lib/automail.class.php";
	$automail = new automail();
	$automail->_set($modeMail,$_POST[email],$cfg);
	$automail->_assign($_POST);
	$automail->_assign('cart',$cart);
	$automail->_assign('deli_msg',$delivery['msg']);
	if ($_POST[settlekind]=="a"){
		$data = $db->fetch("select * from ".GD_LIST_BANK." where sno='$_POST[bankAccount]'");
		$automail->_assign($data);
	}
	$automail->_send();
}

### 주문확인 SMS
sendSmsCase('order',$_POST[mobileOrder]);

### 입금요청 SMS
if($_POST['settlekind'] == "a"){
	$data = $db->fetch("select * from ".GD_LIST_BANK." where sno='$_POST[bankAccount]'");
	$dataSms['account']		= $data['bank']." ".$data['account']." ".$data['name'];
	$GLOBALS['dataSms']		= $dataSms;
	sendSmsCase('account',$_POST[mobileOrder]);
}

echo "<script>parent.location.replace('order_end.php?ordno=$ordno');</script>";
//$db->viewLog();

?>
