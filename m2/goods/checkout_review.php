<?php

include dirname(__FILE__).'/../_header.php';
@include $shopRootDir.'/lib/page.class.php';
@include $shopRootDir.'/conf/config.checkout_review.php';

$pg = new Page(1,10);
$pg->field = "PR.PurchaseReviewId as sno, PR.PurchaseReviewScore, PR.Title, PR.CreateYmdt, PR.ProductName, PR.ProductID";
$db_table = " ".GD_NAVERCHECKOUT_PURCHASEREVIEW." AS PR";

$pg->setQuery($db_table,$where,$sort="PR.CreateYmdt desc");
$pg->exec();

$res = $db->query($pg->query);

while ($reviewData = $db->fetch($res)){

	if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
		$reviewData = validation::xssCleanArray($reviewData, array(
		    validation::DEFAULT_KEY => 'text',
		    'Title' => array('html', 'ent_noquotes'),
		));
	}

	$reviewData['idx'] = $pg->idx--;

	$reviewData['Title'] = nl2br(htmlspecialchars($reviewData['Title']));

	$query = 'select b.goodsnm,b.img_s,c.price,b.use_mobile_img,b.img_i,b.img_m,b.img_l,b.img_x,b.img_pc_x
	from
		'.GD_GOODS." b
		left join ".GD_GOODS_OPTION." c on b.goodsno=c.goodsno and link and go_is_deleted <> '1' and go_is_display = '1'
	where
		b.goodsno = '" . $reviewData['ProductID'] . "'";
	$goodsData = $db->fetch($query, true);
	if($goodsData) {
		$reviewData = array_merge($reviewData, $goodsData);
	}
	
	### 모바일 이미지 개선용 기존 템플릿 치환코드 오버라이드 처리
	if ($reviewData['use_mobile_img'] === '1') {
		$reviewData['img_s'] = $reviewData['img_x'];
	} else if ($reviewData['use_mobile_img'] === '0') {
		$imgArr = explode('|', $reviewData[$reviewData['img_pc_x']]);
		$reviewData['img_s'] = $imgArr[0];
	}

	//네이버페이 상품후기 평점
	if ($reviewData[PurchaseReviewScore] == "0") {
		$reviewData[PurchaseReviewScore] = "불만족";
	} else if ($reviewData[PurchaseReviewScore] == "1") {
		$reviewData[PurchaseReviewScore] = "보통";
	} else {
		$reviewData[PurchaseReviewScore] = "만족";
	}

	$review_loop[] = $reviewData;

}



$tpl->assign('review_total', $pg->recode['total']);
$tpl->assign('review_loop', $review_loop);
$tpl->assign( 'pg', $pg );

### 템플릿 출력
$tpl->print_('tpl');

?>