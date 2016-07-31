<?php
	include dirname(__FILE__) . "/../_header.php";
	@include $shopRootDir . "/lib/page.class.php";

	chkMemberMobile();

	list ($name, $emoney) = $db->fetch("select name, emoney from ".GD_MEMBER." where m_no='$sess[m_no]'"); # 현재 적립금

	list ($total) = $db->fetch("select count(*) from ".GD_LOG_EMONEY." where m_no='$sess[m_no]'"); # 총 레코드수

	### 목록
	$pg = new Page($_GET[page],10);
	$db_table = "".GD_LOG_EMONEY."";
	$pg->field = "*, date_format( regdt, '%Y.%m.%d' ) as regdts"; # 필드 쿼리
	$where[] = "m_no='$sess[m_no]' AND emoney != 0";
	$pg->setQuery($db_table,$where,$orderby="regdt desc");
	$pg->exec();

	$res = $db->query($pg->query);
	while ($data=$db->fetch($res)){
		$data['idx'] = $pg->idx--;
		$loop[] = $data;
	}

	$tpl->assign('loop',$loop);
	$tpl->assign('pg',$pg);
	$tpl->print_('tpl');
?>