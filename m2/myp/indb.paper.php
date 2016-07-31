<?
include "../lib/library.php";

## �α�üũ
if(!$sess['m_no']){
	exit('{"result":"error","message":"�α��� �ϼž� �մϴ�."}');
}

$m_no = (int) $sess[m_no];
$number = $db->_escape(implode('-',$_POST['coupon_number']));

$today = date("YmdH");

$query = "
SELECT coupon.*,paper.sno paper_sno,down.sno download_sno,down.m_no
FROM gd_offline_coupon coupon,gd_offline_paper paper
	LEFT JOIN gd_offline_download down ON paper.sno=down.paper_sno AND down.m_no='$m_no'
WHERE coupon.sno=paper.coupon_sno
	AND paper.number='$number'
	AND CONCAT(coupon.start_year,coupon.start_mon,coupon.start_day,coupon.start_time) <= '$today'
	AND CONCAT(coupon.end_year,coupon.end_mon,coupon.end_day,coupon.end_time) >= '$today'
	AND coupon.`status`!='disuse'";

list($arCoupon) = $db->_select($query);

if(!$arCoupon[sno]||!$arCoupon[paper_sno]){
	exit('{"result":"error","message":"�ùٸ��� ���� ������ȣ �Դϴ�."}');
}

if($arCoupon['number_type']=='auto' && $arCoupon['download_sno']){
	exit('{"result":"error","message":"���� ������ȣ �Դϴ�."}');
}

if($arCoupon['publish_limit']=='limited' && $arCoupon['limit_paper'] > 0){
	$query = "SELECT count(*) cnt FROM gd_offline_download WHERE coupon_sno='$arCoupon[sno]'";
	list($arDowload) = $db->_select($query);
	if($arDowload['cnt'] >= $arCoupon['limit_paper']){
		exit('{"result":"error","message":"���� ��뷮�� �ʰ� �Ǿ����ϴ�."}');
	}
}

$query = "select count(*) from gd_offline_download down,
				gd_offline_paper paper,
				gd_offline_coupon coupon
			where down.m_no='$sess[m_no]'
				AND down.paper_sno=paper.sno
				AND paper.coupon_sno=coupon.sno
				AND coupon.sno='$arCoupon[sno]'";
list($cnt) = $db->fetch($query);
if($cnt){
	exit('{"result":"error","message":"�̹� �Է��Ͻ� ���� �Դϴ�."}');
}

if($arCoupon['number_type'] != 'duplication'){
	$query = "select count(*) from gd_offline_download where paper_sno='$arCoupon[paper_sno]'";
	list($cnt) = $db->fetch($query);
	if($cnt){
		exit('{"result":"error","message":"�̹� �Է��Ͻ� ���� �Դϴ�."}');
	}
}


$query = "insert into gd_offline_download set coupon_sno='$arCoupon[sno]',paper_sno='$arCoupon[paper_sno]',m_no='$m_no',regdt=now(),updatedt=now()";
$db->query($query);

$query = "update gd_offline_coupon set `status`='done',updatedt=now() where sno='$arCoupon[sno]'";
$db->query($query);

exit('{"result":"success","message":"������ȣ�� ���� �Ǿ����ϴ�."}');
?>
