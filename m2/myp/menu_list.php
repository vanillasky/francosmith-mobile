<?
/*********************************************************
* ���ϸ�     :  myp/menu_list.php
* ���α׷��� :	�޴� ����Ʈ ������
* �ۼ���     :  dn
* ������     :  2012.07.05
**********************************************************/	
include dirname(__FILE__) . "/../_header.php";

if($_GET['debug']) {
	$sess['m_no'] = 2;
}

if(!$sess['m_no']) {
	$data_cnt['order'] = '0';
	$data_cnt['emoney'] = '0';
	$data_cnt['coupon'] = '0';
	$data_cnt['wish'] = '0';
}
else {
	$data_cnt = Array(); 
	## �ֹ��Ǽ� �������� ##
	$order_query = $db->_query_print('SELECT COUNT(ordno) cnt_order FROM '.GD_ORDER.' WHERE m_no=[i]', $sess[m_no]);
	$res_order = $db->_select($order_query);
	$data_cnt['order'] = $res_order[0]['cnt_order'];
	
	if(!$data_cnt['order']) $data_cnt['order'] = 0;
	
	## �̸Ӵ� �������� 
	$emoney_query = $db->_query_print('SELECT emoney FROM '.GD_MEMBER.' WHERE m_no=[i]', $sess[m_no]);
	$res_emoney = $db->_select($emoney_query);
	$data_cnt['emoney'] = $res_emoney[0]['emoney'];
	
	if(!$data_cnt['emoney']) $data_cnt['emoney'] = 0;
	
	## ������ ���� �������� 
	
	## ���� ������ ��ȿ�� ȸ������ ����� ��������Ʈ ���ϱ� - ��뿩�δ� üũ�ȵ�.  
	$coupon_query = "
	SELECT distinct a.sno applysno,c.*,a.goodsno FROM
		gd_coupon_apply a
		LEFT JOIN gd_coupon_applymember b ON a.sno=b.applysno
		LEFT JOIN gd_coupon c ON a.couponcd = c.couponcd
	WHERE ((a.membertype = 1 and a.member_grp_sno='$sess[groupsno]') OR (a.membertype = 2 and b.m_no='$sess[m_no]') OR a.membertype = 0)
		AND ((c.sdate <= '".date("Y-m-d H:i:s")."' AND c.edate >= '".date("Y-m-d H:i:s")."' AND c.priodtype='0')
		     OR (c.priodtype='1' AND (c.edate >= '".date("Y-m-d H:i:s")."' OR  c.edate = '') AND ADDDATE(a.regdt,INTERVAL c.sdate DAY) >= '".date("Y-m-d")." 00:00:00'))
	ORDER BY couponcd";

	$coupon_usecnt = 0;
	$coupon_unusecnt = 0; 
	$res_coupon = $db->query($coupon_query);
	while ($data=$db->fetch($res_coupon)){
		$query = "select count(*) from gd_coupon_order where applysno='$data[applysno]' and m_no ='".$sess['m_no']."'";
		list($cnt) = $db -> fetch($query);		
		if($cnt>0){
			$coupon_usecnt ++; 
		} else {
			$coupon_unusecnt ++;
		}
	}
	
	$today = date("YmdH");
	list($offline_coupon_count) = $db->fetch('SELECT COUNT(coupon.sno)
	FROM gd_offline_coupon coupon,gd_offline_download down
	WHERE down.coupon_sno=coupon.sno
		AND	coupon.`status`!="disuse"
		AND	concat(coupon.start_year,coupon.start_mon,coupon.start_day,coupon.start_time) <= "'.$today.'"
		AND concat(coupon.end_year,coupon.end_mon,coupon.end_day,coupon.end_time) >= "'.$today.'"
		AND	down.m_no='.$sess['m_no']);
	$data_cnt['coupon'] = $coupon_unusecnt + $offline_coupon_count;
	
	if(!$data_cnt['coupon']) $data_cnt['coupon'] = 0;
	
	## ���ø���Ʈ ī��Ʈ ���ϱ� 
	$wish_query = $db->_query_print('SELECT COUNT(*) cnt_wish FROM '.GD_MEMBER_WISHLIST.' WHERE m_no=[i]', $sess['m_no']);
	$res_wish = $db->_select($wish_query);
	$data_cnt['wish'] = $res_wish[0]['cnt_wish'];
	
	if(!$data_cnt['wish']) $data_cnt['wish'] = 0;		

	## 1:1 ����
	$qna_query = $db->_query_print('SELECT COUNT(*) cnt_qna FROM '.GD_MEMBER_QNA.' WHERE sno=parent AND (m_no=[i] or notice=1 or sno in (select parent from '.GD_MEMBER_QNA.' where m_no='.$sess["m_no"].'))', $sess['m_no']);
	$res_qna = $db->_select($qna_query);
	$data_cnt['qna'] = $res_qna[0]['cnt_qna'];
	
	if(!$data_cnt['qna']) $data_cnt['qna'] = 0;		

	## ��ǰ�ı�
	$review_query = $db->_query_print('SELECT COUNT(*) cnt_review FROM '.GD_GOODS_REVIEW.' WHERE m_no=[i] AND sno=parent', $sess['m_no']);
	$res_review = $db->_select($review_query);
	$data_cnt['review'] = $res_review[0]['cnt_review'];
	
	if(!$data_cnt['review']) $data_cnt['review'] = 0;		

	## ��ǰ����
	$goods_qna_query = $db->_query_print('SELECT COUNT(*) cnt_goods_qna FROM '.GD_GOODS_QNA.' WHERE m_no=[i] AND sno=parent', $sess['m_no']);
	$res_goods_qna = $db->_select($goods_qna_query);
	$data_cnt['goods_qna'] = $res_goods_qna[0]['cnt_goods_qna'];
	
	if(!$data_cnt['goods_qna']) $data_cnt['goods_qna'] = 0;		



	// ȸ���׷�����
	$grp_profit = Core::loader('group_profit')->getGroupProfit();
	$tpl->assign('grp_profit', $grp_profit);
	
}


$tpl->assign(data_cnt, $data_cnt);
$tpl->print_('tpl');
?>