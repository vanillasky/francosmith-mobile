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
				'msg'			=> $msgs[0] . ' 상품은 현재 판매중인 상품이 아닙니다.',
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
				'msg'			=> '상품을 장바구니에 담았습니다',
			);
		} else if ($rc === 0) {
			$result = array(
				'msg'			=> '상품이 장바구니에 이미 있습니다',
			);
		}

	break;
	case 'addWishlist':

		if(!$sess['m_id']){
			$result = array(
				'msg'			=> '로그인을 해주시기 바랍니다',
				'url'			=> '../mem/login.php',
			);
			break;
		}

		// 멀티옵션
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
					'msg'			=> '상품을 찜했습니다',
					);
				}
				else{
					$result = array(
						'msg'			=> '이미 찜한 상품입니다',
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
					'msg'			=> '상품을 찜했습니다',
				);
			}
			else{
				$result = array(
					'msg'			=> '이미 찜한 상품입니다',
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
