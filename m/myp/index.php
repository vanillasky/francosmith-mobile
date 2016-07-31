<?php

	include dirname(__FILE__) . "/../_header.php";

	chkMemberMobile();

	$query = "select count(*) from ".GD_ORDER." where m_no='$sess[m_no]';";
	list($cnt_order) = $db->fetch($query);

	$query = "select count(*) from ".GD_MEMBER_WISHLIST." where m_no='$sess[m_no]';";
	list($cnt_wishlist) = $db->fetch($query);

	list ($emoney) = $db->fetch("select emoney from ".GD_MEMBER." where m_no='$sess[m_no]'"); # 현재 적립금
	
	$query = "
	SELECT distinct a.sno,c.*,a.goodsno FROM
		gd_coupon_apply a
		LEFT JOIN gd_coupon_applymember b ON a.sno=b.applysno
		LEFT JOIN gd_coupon c ON a.couponcd = c.couponcd
	WHERE ((a.membertype = 1 and a.member_grp_sno='$sess[groupsno]') OR (a.membertype = 2 and b.m_no='$sess[m_no]') OR a.membertype = 0)
		AND ((c.sdate <= '".date("Y-m-d H:i:s")."' AND c.edate >= '".date("Y-m-d H:i:s")."' AND c.priodtype='0') OR (c.priodtype='1' AND ADDDATE(a.regdt,INTERVAL c.sdate DAY) >= '".date("Y-m-d")." 00:00:00'))
	ORDER BY couponcd";

	$res = $db->query($query);
	while ($data=$db->fetch($res)){
		$query = "select count(*) from gd_coupon_order where applysno='$data[sno]' and m_no ='".$sess[m_no]."'";
		list($cnt) = $db -> fetch($query);
		if(!$cnt){
			$cnt_coupon++;
		}
	}

	$today = date("YmdH");
	$query = "
	SELECT coupon.*,down.sno download_sno
	FROM gd_offline_coupon coupon,gd_offline_download down
	WHERE down.coupon_sno=coupon.sno
		AND	coupon.`status`!='disuse'
		AND	concat(coupon.start_year,coupon.start_mon,coupon.start_day,coupon.start_time) <= '$today'
		AND concat(coupon.end_year,coupon.end_mon,coupon.end_day,coupon.end_time) >= '$today'
		AND	down.m_no='$sess[m_no]'
	ORDER BY coupon.sno DESC";
	$result = $db->select($query);
	if($result)foreach($result as $data){
		$query = "select count(*) from gd_coupon_order where
			download_sno='$data[download_sno]'
			AND m_no='$sess[m_no]'";
		list($ordercnt) = $db->fetch($query);
		if($ordercnt==0) $cnt_coupon++;
	}

	$tpl->print_('tpl');
?>