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

		if (is_array($_POST[multi_ea])) {
			$_keys = array_keys($_POST[multi_ea]);
			for ($i=0, $m=sizeof($_keys);$i<$m;$i++) {
				$_opt = $_POST[multi_opt][ $_keys[$i] ];
				$_ea = $_POST[multi_ea][ $_keys[$i] ];
				$_addopt = $_POST[multi_addopt][ $_keys[$i] ];
				$_addopt_inputable = $_POST[multi_addopt_inputable][ $_keys[$i] ];
				$rc = $cart->addCart($_POST[goodsno], $_opt, $_addopt, $_addopt_inputable, $_ea, $_POST[goodsCoupon]);
			}
		}
		else {
			$rc = $cart->addCart($_POST[goodsno], $_POST[opt], $_POST[addopt], $_POST[_addopt_inputable], $_POST[ea], $_POST[goodsCoupon]);
		}

		if ($rc === 1) {
			$result = array(
				'msg'			=> '��ǰ�� ��ٱ��Ͽ� ��ҽ��ϴ�',
			);
		} else if ($rc === 0) {
			$result = array(
				'msg'			=> '��ǰ�� ��ٱ��Ͽ� �̹� �ֽ��ϴ�',
			);
		}

	break;
	case 'addWishlist':

		if(!$sess['m_id']){
			$result = array(
				'msg'			=> '�α����� ���ֽñ� �ٶ��ϴ�',
				'url'			=> '../mem/login.php',
			);
			break;
		}

		// ��Ƽ�ɼ�
		if ($_POST[_multi_ea]) {
			$_keys = array_keys($_POST[_multi_ea]);
			for ($i=0, $m=sizeof($_keys);$i<$m;$i++) {
				$_opt = $_POST[multi_opt][ $_keys[$i] ];
				$_addopt = $_POST[multi_addopt][ $_keys[$i] ];
				$_addopt_inputable = $_POST[multi_addopt_inputable][ $_keys[$i] ];

				$opt = @explode("|",implode("|",$_opt));
				$addopt = @implode("|",$_addopt);
				$addopt_inputable = @implode("|",$_addopt_inputable);

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
					'msg'			=> '��ǰ�� ���߽��ϴ�',
					);
				}
				else{
					$result = array(
						'msg'			=> '�̹� ���� ��ǰ�Դϴ�',
					);
				}
			}
		}
		else {
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
					'msg'			=> '��ǰ�� ���߽��ϴ�',
				);
			}
			else{
				$result = array(
					'msg'			=> '�̹� ���� ��ǰ�Դϴ�',
				);
			}
		}

	break;
	case 'getGoodsQna':
		$sno = $_POST['sno'];
		$password = $_POST['password'];
		$result = $db->fetch('SELECT * FROM gd_goods_qna WHERE sno='.$sno.' AND password=md5("'.$password.'")', true);

		$reply_query = 'SELECT * FROM gd_goods_qna WHERE parent='.$sno.' AND parent != sno';
		$res_reply = $db->_select($reply_query);

		$result['reply'] = $res_reply;


		break;
}

echo $json->encode($result);
?>
