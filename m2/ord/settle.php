<?
$_POST[emoney] = str_replace(",","",$_POST[emoney]);
$_POST[coupon] = str_replace(",","",$_POST[coupon]);
$_POST[coupon_emoney] = str_replace(",","",$_POST[coupon_emoney]);

include dirname(__FILE__) . "/../_header.php";
@include $shopRootDir . "/lib/cart.class.php";
@include $shopRootDir . "/conf/config.pay.php";

if(method_exists('validation','xssCleanArray')){
	$_POST = validation::xssCleanArray($_POST, array(
		validation::DEFAULT_KEY => 'text',
		'memo'=>'disable',
	));
}

if (!$_POST[ordno]) msg("�ֹ���ȣ�� �������� �ʽ��ϴ�","order.php");
//debug($_POST); exit;
### ȸ������ ��������
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

### ������ ��ȿ�� üũ
chkEmoney($set[emoney],$_POST[emoney]);

$mobilians = Core::loader('Mobilians');
$danal = Core::loader('Danal');

if ($_POST['save_mode'] != '') {
	$load_config_ncash = $config->load('ncash');
}
else{
	$load_config_ncash = array();
}

$cart = new Cart($_COOKIE[gd_isDirect]);

### �ֹ��� ���� üũ
$cart -> chkOrder();

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
if ($delivery[type]=="�ĺ�" && $delivery[freeDelivery] =="1") {
	$msg_delivery = "- 0��";
} else {
	$msg_delivery = $delivery[msg];
}
if($delivery[price] && !$delivery[msg]){
	$msg_delivery = number_format($delivery[price])."��";
}
$cart -> delivery = $delivery[price];
$cart -> totalprice += $delivery[price];

### ��ٱ��� ���� ���� ���� üũ
if (count($cart->item)==0) msg("�ֹ������� �������� �ʽ��ϴ�","../index.php");

### ������ ����
$_POST['coupon'] = $cart -> coupon;
$discount = $_POST[coupon] + $_POST[emoney] + $cart->dcprice + $_POST['totalUseAmount'.$load_config_ncash['api_id']] + $cart->special_discount_amount;
if ($cart->totalprice - $discount < 0){
	## �����αݾ��� ��ǰ�հ�׺��� ������, ���ο� ���Ǵ� �������� �����Ѵ�.
	$_POST[emoney] = $cart->totalprice - $_POST[coupon]-$cart->dcprice - $_POST['totalUseAmount'.$load_config_ncash['api_id']] - $cart->special_discount_amount;
}

### ����, ������ �ߺ� ��� üũ
if (! $set['emoney']['useduplicate']) {
	if ($_POST['emoney'] > 0 && ($_POST['coupon'] > 0 || $_POST['coupon_emoney'] > 0)) {
		msg('�����ݰ� ���� ����� �ߺ�������� �ʽ��ϴ�.',-1);
		exit;
	}
}

### �ֹ����� üũ
chkCartMobile(&$cart, $cfgMobileShop['vtype_goods']);

### �����ݾ� ����
$discount = $_POST[coupon] + $_POST[emoney] + $cart->dcprice + $_POST['totalUseAmount'.$load_config_ncash['api_id']] + $cart->special_discount_amount;
$_POST[settleprice] = $cart->totalprice - $discount;

### ȸ�� �߰� ������ ����
switch($member['add_emoney_type']) {
	case 'goods':
		$tmp_price = $cart->goodsprice;
		break;
	case 'settle_amt':
		$tmp_price = $_POST[settleprice];
		break;
	default:
		$tmp_price = 0;
		break;
}
$addreserve = getExtraReserve($member['add_emoney'], $member['add_emoney_type'], $member['add_emoney_std_amt'], $tmp_price, $cart);

### �ֹ��ݾ��� 0�� ��� (������/���ΰ�����)
if ($_POST[settleprice]==0 && $discount>0){
	$_POST[settlekind] = "d";	// ���ΰ���
}

### �������ܿ� ���� ����
switch ($_POST[settlekind]){

	case "a":	// �������Ա�

		### �������Ա� ���� ����Ʈ
		$res = $db->query("select * from ".GD_LIST_BANK." where useyn='y'");
		while ($data= $db->fetch($res)) $bank[] = $data;
		break;
	case "c":	// �ſ�ī��
	case "o":	// ������ü
	case "v":	// �������
	case "h":	// �ڵ���
	case "p":	// ����Ʈ
		@include $shopRootDir.'/conf/pg.'.$cfg['settlePg'].'.php';
		@include $shopRootDir.'/conf/pg_mobile.'.$cfg['settlePg'].'.php';
		if (isset($pg_mobile) && isset($pg['id']) && strlen($pg['id']) > 0) {
			ob_start();
			include $shopRootDir."/order/card/$cfg[settlePg]/mobile/card_gate.php";
			$card_gate = ob_get_contents();
			ob_end_clean();
			$tpl->assign('card_gate',$card_gate);
		}
		break;

	case "d":	// ���ΰ��� (�����ݾ��� 0�� ���)

		break;

	case "t":	//������ ����
		$payco = Core::loader('payco');
		if($payco->checkNcash($load_config_ncash['useyn'], $_POST['totalUseAmount'.$load_config_ncash['api_id']]) == true){
			$payco->msgLocate("PAYCO ���������� ���̹� ���ϸ��� �� ĳ�� ����� �Ұ��մϴ�.", $_POST['paycoType'], 'Y');
		}
		$card_gate = "<div style='text-align: center; margin: 10px 0px 10px 0px;'>������ ������� ���񽺸� �������Դϴ�.</div>";
		$tpl->assign('card_gate', $card_gate);
	break;

}

### �ֹ�����Ÿ ����
$_POST[memo] = htmlspecialchars(stripslashes($_POST[memo]), ENT_QUOTES);

if($_POST['updateMemberInfo']=='y' && $sess[m_no]){
	$zipcode = @implode("-",$_POST['zipcode']);
	$phone = @implode("-",$_POST['phoneReceiver']);
	$mobile = @implode("-",$_POST['mobileReceiver']);

	$query = "update ".GD_MEMBER." set
			zipcode = '$zipcode',
			zonecode = '".$_POST['zonecode']."',
			address = '".$_POST['address']."',
			address_sub = '".$_POST['address_sub']."',
			road_address = '".$_POST['road_address']."',
			phone = '$phone',
			mobile = '$mobile'
			 where m_no=".$sess['m_no'];
	$db->query($query);
}

// ���̹� Ncash
if($load_config_ncash['useyn'] == 'Y'){

	$load_config_ncash['ncash_emoney'] = $_POST['mileageUseAmount'.$load_config_ncash['api_id']];
	$load_config_ncash['ncash_cash'] = $_POST['cashUseAmount'.$load_config_ncash['api_id']];
	$load_config_ncash['totalAccumRate'] = $_POST['baseAccumRate'] + $_POST['addAccumRate'];
	if(in_array($load_config_ncash['save_mode'],array('choice','both'))) $load_config_ncash['save_mode'] = $_POST['save_mode'];	// ���� ��ġ

	// ���̹� ����Ʈ ������ �� ȸ���߰������� 0������ ����
	if( $load_config_ncash['save_mode'] == 'ncash' ) $addreserve = 0;

	// Ʈ����� ���̵� ��Ű�� ����
	setcookie('reqTxId',$_POST['reqTxId'.$load_config_ncash['api_id']],0);

	$tpl->assign('ncash',$load_config_ncash);
}
$tpl->assign('NaverMileageAmount', include dirname(__FILE__).'/../../shop/proc/naver_mileage/use_amount_type_1.php');

if($cfg['settleCellPg'] === 'mobilians'){
	$tpl->assign('MobiliansEnabled', $mobilians->isEnabled());
}
else if($cfg['settleCellPg'] === 'danal'){
	$tpl->assign('DanalEnabled', $danal->isEnabled());
}

//debug($_POST);
//debug($cart);
//exit;
### ���ø� ���
$tpl->assign($_POST);
$tpl->assign('cart',$cart);
$tpl->define(array(
			'orderitem'	=> '/proc/orderitem.htm',
			));
$tpl->print_('tpl');

?>