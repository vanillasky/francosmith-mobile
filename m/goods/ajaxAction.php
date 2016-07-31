<?php

@include dirname(__FILE__) . "/../lib/library.php";
@include $shopRootDir . "/lib/page.class.php";
@include $shopRootDir . "/lib/json.class.php";
@include $shopRootDir . "/lib/cart.class.php";

$json = new Services_JSON();
$cart = new Cart;

$_POST = utf8ToEuckr($_POST);

$mode = ($_POST[mode]) ? $_POST[mode] : $_GET[mode];

if ($mode){
	$opt = @explode("|",implode("|",$_POST[opt]));
	$addopt = @implode("|",$_POST[addopt]);
	$addopt_inputable = @implode("|",$_POST[_addopt_inputable]);
}

switch($mode){
	case 'addCart':
		$msgs = rtnOpenYn($_POST[goodsno], 'D');

		if (!empty($msgs)) {
			$result = array(
				'msg'			=> $msgs[0] . ' ��ǰ�� ���� �Ǹ����� ��ǰ�� �ƴմϴ�.',
			);
			break;
		}

		$cart->addCart($_POST[goodsno],$_POST[opt],$_POST[addopt],$_POST[_addopt_inputable],$_POST[ea],$_POST[goodsCoupon]);
		$result = array(
			'msg'			=> '��ǰ�� ��ٱ��Ͽ� ��ҽ��ϴ�.',
		);
	break;
	case 'addWishlist':

		if(!$sess['m_id']){
			$result = array(
				'msg'			=> '�α����� ���ֽñ� �ٶ��ϴ�.',
			);
			break;
		}

		$query = "
		select * from
			".GD_MEMBER_WISHLIST."
		where
			m_no = '$sess[m_no]'
			and goodsno = '$_POST[goodsno]'
			and opt1 = '$opt[0]'
			and opt2 = '$opt[1]'
			and addopt = '$addopt'
			and addopt_inputable = '$addopt_inputable'
		";
		list ($chk) = $db->fetch($query);
		if (!$chk){
			$query = "
			insert into ".GD_MEMBER_WISHLIST." set
				m_no = '$sess[m_no]',
				goodsno = '$_POST[goodsno]',
				opt1 = '$opt[0]',
				opt2 = '$opt[1]',
				addopt = '$addopt',
				addopt_inputable = '$addopt_inputable',
				regdt = now()
			";
			$db->query($query);
			$result = array(
				'msg'			=> '��ǰ�� ���߽��ϴ�.',
			);
		}
		else{
			$result = array(
				'msg'			=> '�̹� ���� ��ǰ�Դϴ�.',
			);
		}

	break;
}

echo $json->encode($result);
?>
