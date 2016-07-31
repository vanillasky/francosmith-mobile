<?php
	include dirname(__FILE__) . "/../_header.php";
	
	chkMemberMobile();
	if (!$sess && !$_COOKIE[guest_ordno]) go("../mem/login.php?returnUrl=$_SERVER[PHP_SELF]");
	
	
	# 회원의 사용가능 쿠폰 조회 쿼리 
	$query = "
	SELECT distinct a.sno, a.regdt as apply_regdt, a.sno as applysno,c.*,a.goodsno FROM
		gd_coupon_apply a
		LEFT JOIN gd_coupon_applymember b ON a.sno=b.applysno
		LEFT JOIN gd_coupon c ON a.couponcd = c.couponcd
	WHERE ((a.membertype = 1 and a.member_grp_sno='$sess[groupsno]') OR (a.membertype = 2 and b.m_no='$sess[m_no]') OR a.membertype = 0)
		AND ((c.sdate <= '".date("Y-m-d H:i:s")."' AND c.edate >= '".date("Y-m-d H:i:s")."' AND c.priodtype='0')
		     OR (c.priodtype='1' AND (c.edate >= '".date("Y-m-d H:i:s")."' OR  c.edate = '') AND ADDDATE(a.regdt,INTERVAL c.sdate DAY) >= '".date("Y-m-d")." 00:00:00'))
	ORDER BY couponcd";

	$res = $db->query($query);
	while ($data=$db->fetch($res)){
		$query = "select count(*) from gd_coupon_order where applysno='$data[applysno]' and m_no ='".$sess[m_no]."'";
		list($cnt) = $db -> fetch($query);
		if($cnt){
			$data['cnt'] = "사용";
		}else $data['cnt'] = "미사용";
		if ($data['priodtype'] == '0') $endTime = strtotime($data['edate']);
		else $endTime = strtotime($data['apply_regdt']) + (60*60*24*$data['sdate']);
		$data['remain_minute']=round(($endTime-time())/60);
		$data['remain_hour']=round($data['remain_minute']/60);
		$data['remain_date']=round($data['remain_hour']/24);
		$loop['goods'][] = $data;
	}

	$today = date("YmdH");
	$arAbility = array('sale'=>'0','save'=>'1');
	$query = "
	SELECT coupon.*,down.sno download_sno
	FROM gd_offline_coupon coupon,gd_offline_download down
	WHERE down.coupon_sno=coupon.sno
		AND	coupon.`status`!='disuse'
		AND	concat(coupon.start_year,coupon.start_mon,coupon.start_day,coupon.start_time) <= '$today'
		AND concat(coupon.end_year,coupon.end_mon,coupon.end_day,coupon.end_time) >= '$today'
		AND	down.m_no='$sess[m_no]'
	ORDER BY coupon.sno DESC";
	$result = $db->_select($query);
	foreach($result as $data){
		$data['coupon']=$data['coupon_name'];
		$data['sdate']=$data['start_year'].'-'.$data['start_mon'].'-'.$data['start_day'];
		$data['edate']=$data['end_year'].'-'.$data['end_mon'].'-'.$data['end_day'];
		$data['remain_minute']=round((strtotime($data['edate'].' '.$data['end_time'].':59:59')-time())/60);
		$data['remain_hour']=round($data['remain_minute']/60);
		$data['remain_date']=round($data['remain_hour']/24);
		$data['ability']=$arAbility[$data['coupon_type']];
		$data['price']=$data['coupon_price'].$data['currency'];
		$data['priodtype']='2';

		$query = "select count(*) from gd_coupon_order where
			downloadsno='$data[download_sno]'
			AND m_no='$sess[m_no]'";
		list($ordercnt) = $db->fetch($query);

		if($ordercnt==0) $data['cnt'] = "미사용";
		else $data['cnt'] = "사용";
		$loop['goods'][] = $data;
	}

	$tpl->assign('loop',$loop['goods']);
	$tpl->print_('tpl');
?>