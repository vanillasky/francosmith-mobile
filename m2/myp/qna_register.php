<?
include dirname(__FILE__) . "/../_header.php"; chkMemberMobile();

### 변수할당
$mode		= $_GET[mode];
$sno		= $_GET[sno];

### 1:1 문의
$data['m_id'] = $sess['m_id'];

$order_res = $db->query("SELECT o.ordno, o.orddt FROM gd_order o WHERE o.m_no=$sess[m_no] ORDER BY orddt DESC");

$idx = 0;

while ($order_data=$db->fetch($order_res)){

	$idx ++;

	$order_data[idx] = $idx;

	$select_count_item = '(select count(*) from '.GD_ORDER_ITEM.' as s_oi where s_oi.ordno=[s]) as count_item';

	$goodsnm_query = $db->_query_print('SELECT '.$select_count_item.', oi.goodsnm FROM '.GD_ORDER_ITEM.' oi WHERE oi.ordno=[s]', $order_data['ordno'], $order_data['ordno']);
	
	$res_goodsnm = $db->_select($goodsnm_query);
	$row_goodsnm = $res_goodsnm[0];

	$order_data['goodsnm'] = $row_goodsnm['goodsnm'];
	$order_data['orddt'] = substr($order_data['orddt'], 0, 10);
	if($order_data['count_item'] > 1) $order_data['goodsnm'] .= ' 외 '.($row_goodsnm['count_item'] - 1).'건';
	$order_list[] = $order_data;

	unset($row_goodsnm, $res_goodsnm, $goosnm_query);
}

// 개인정보수집 및 이용에 대한 안내
$termsPolicyCollection4 = getTermsGuideContents('terms', 'termsPolicyCollection4');
$tpl->assign('termsPolicyCollection4', $termsPolicyCollection4);

$tpl->assign('order_cnt', $idx);
$tpl->assign('order_list', $order_list);

### 무료보안서버 회원처리url
$tpl->assign('myqnaActionUrl',$sitelink->link_mobile('myp/indb.php','ssl'));

### 템플릿 출력
$tpl->print_('tpl');

?>