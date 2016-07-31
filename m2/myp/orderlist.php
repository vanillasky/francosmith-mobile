<?php
	include dirname(__FILE__) . "/../_header.php";
	@include $shopRootDir . "/lib/page.class.php";

//	chkMemberMobile();

	if (!$sess && !$_COOKIE[guest_ordno]) go("../mem/login.php?returnUrl=$_SERVER[PHP_SELF]");

	$db_table = "".GD_ORDER."";
	if ($sess[m_no]) $where[] = "m_no = '$sess[m_no]'";
	else {
		$where[] = "ordno = '$_COOKIE[guest_ordno]'";
		$where[] = "nameOrder = '$_COOKIE[guest_nameOrder]'";
		$where[] = "m_no = ''";
	}

	$pg = new Page($_GET[page],10);
	$pg->setQuery($db_table,$where,"ordno desc");
	$pg->exec();

	$res = $db->query($pg->query);
	$idx = 0;
	while ($data=$db->fetch($res)){
		$order = Core::loader('order');
		$order->load($data['ordno']);

		$idx ++;
		$data[str_step] = (!$data[step2]) ? $r_step[$data[step]] : $r_step2[$data[step2]];
		$data[str_settlekind] = $r_settlekind[$data[settlekind]];
		$data[settleprice] = $order->getRealPrnSettleAmount();
		$data[idx] = $idx;

		$select_count_item = '(select count(*) from '.GD_ORDER_ITEM.' as s_oi where s_oi.ordno=[s]) as count_item';

		$goodsnm_query = $db->_query_print('SELECT '.$select_count_item.', oi.goodsnm FROM '.GD_ORDER_ITEM.' oi WHERE oi.ordno=[s]', $data['ordno'], $data['ordno']);

		$res_goodsnm = $db->_select($goodsnm_query);
		$row_goodsnm = $res_goodsnm[0];

		$data['goodsnm'] = $row_goodsnm['goodsnm'];

		if($row_goodsnm['count_item'] > 1) $data['goodsnm'] .= ' ¿Ü '.($row_goodsnm['count_item'] - 1).'°Ç';

		if(function_exists('settleIcon')){
			$data['str_settlekind'] = (settleIcon($data['settleInflow'])) ? settleIcon($data['settleInflow']) . ' ' . $data['str_settlekind'] : $data['str_settlekind'];
		}

		$loop[] = $data;

		unset($row_goodsnm, $res_goodsnm, $goosnm_query);
	}

	$tpl->assign('loop',$loop);
	$tpl->assign('pg',$pg);
	$tpl->print_('tpl');
?>