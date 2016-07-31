<?php
	include dirname(__FILE__) . "/../_header.php";
	@include $shopRootDir . "/conf/config.pay.php";
	@include $shopRootDir . "/lib/page.class.php";

	$wishlistpage = true;

	chkMemberMobile();
	$mode = ($_POST[mode]) ? $_POST[mode] : $_GET[mode];

	if ($mode){
		$opt = @explode("|",implode("|",$_POST[opt]));
		$addopt = @implode("|",$_POST[addopt]);
	}

	switch ($mode){
		case "cart":
			chkOpenYn($_POST[goodsno],"D",-1);	//진열여부 체크
			@include $shopRootDir . "/lib/cart.class.php";
			$cart = new Cart;
			foreach ($_POST[sno] as $v){
				// 상품별 최소 구매수량 및 묶음 주문단위를 얻기 위해 상품 정보를 조회한다.
				list($min_ea, $sales_unit) = $db->fetch("select min_ea, sales_unit from ".GD_GOODS." where goodsno = '".$_POST[goodsno][$v]."'");

				$ea = 1;

				if ($min_ea < $sales_unit) {
					$min_ea = $sales_unit;
				}

				if ($ea < $min_ea) {
					$ea = $min_ea;
				}
				else {
					$ea = 1;
				}

				if (($remainder = $ea % $sales_unit) > 0) {
					$ea = $ea - $remainder;
				}

				$cart->addCart($_POST[goodsno][$v],array_notnull($_POST[opt][$v]),$_POST[addopt][$v],$_POST[addopt_inputable][$v],$ea,$_POST[goodsCoupon][$v]);
			}
			go("../goods/cart.php");
		break;
		case "delItem":
			$sno = implode(",",$_POST[sno]);
			$db->query("delete from ".GD_MEMBER_WISHLIST." where sno in ($sno)");
		break;
	}
	if ($mode) header("location:wishlist.php");

	$db_table = "
	".GD_MEMBER_WISHLIST." as w
	left join ".GD_GOODS." as a on w.goodsno=a.goodsno
	left join ".GD_GOODS_OPTION." as b on w.goodsno=b.goodsno and w.opt1=b.opt1 and w.opt2=b.opt2 and go_is_deleted <> '1' and go_is_display = '1'
	";

	$where[] = "w.m_no = $sess[m_no]";
	$where[] = "a.open_mobile";

	$pg = new Page($_GET[page]);
	$pg->field = "w.*,a.goodsnm,a.img_s,b.price,b.reserve";
	$pg->setQuery($db_table,$where,"sno desc");
	$pg->exec();

	$res = $db->query($pg->query);
	while ($data=$db->fetch($res,1)){

		### 필수옵션
		$data[opt]	= array_notnull(array(
					$data[opt1],
					$data[opt2],
					));
		### 선택옵션
		$addopt = array_notnull(explode("|",$data[addopt]));
		if ($addopt){
			$data[r_addopt] = $addopt;
			unset($r_addopt); $addprice = 0;
			foreach ($addopt as $v){
				list ($tmp[sno],$tmp[optnm],$tmp[opt],$tmp[price]) = explode("^",$v);
				$r_addopt[] = $tmp;
				$addprice += $tmp[price];
			}
			$data[addopt] = $r_addopt;
			$data[addprice] = $addprice;
		}

		// 입력옵션
		$addopt_inputable = array_notnull(explode("|",$data[addopt_inputable]));
		if ($addopt_inputable){
			$data[r_addopt_inputable] = $addopt_inputable;
			unset($r_addopt_inputable); $addprice = 0;
			foreach ($addopt_inputable as $v){
				list ($tmp[sno],$tmp[optnm],$tmp[opt],$tmp[price]) = explode("^",$v);
				$r_addopt_inputable[] = $tmp;
				$addprice += $tmp[price];
			}
			$data[addopt_inputable] = $r_addopt_inputable;
			$data[addprice] += $addprice;
		}

		### 상품 적립금 계산
		if($set['emoney']['useyn'] != 'n'){
			if(!$data['use_emoney']){
				if($set['emoney']['chk_goods_emoney'] == 0){
					$data['reserve'] = getDcprice($data['price'],$set['emoney']['goods_emoney'].'%');
				}else{
					$data['reserve'] = $set['emoney']['goods_emoney']+0;
				}
			}
		}
		else{
			$data['reserve'] = 0;
		}

		$loop[] = $data;
	}

	$tpl->assign('loop',$loop);
	$tpl->assign('pg',$pg);
	$tpl->print_('tpl');
?>
