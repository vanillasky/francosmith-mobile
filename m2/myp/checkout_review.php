<?
include dirname(__FILE__) . "/../_header.php";
@include $shopRootDir . "/lib/page.class.php";
@include $shopRootDir . "/conf/config.checkout_review.php";

/* 최초 1번만 가져오며, ajax로 다른 페이지를 호출하여 데이터를 가져온다 */
// 상품 후기 가져오기

if($_GET['goodsno']) {
	$tpl->assign('goodsno', $_GET['goodsno']);
}

$pg_review = new Page(1,10);
$pg_review->field = "PR.PurchaseReviewId as sno, PR.PurchaseReviewScore, PR.Title, PR.CreateYmdt, PR.ProductName, PR.ProductID";
$db_table = " ".GD_NAVERCHECKOUT_PURCHASEREVIEW." AS PR";

$pg_review->setQuery($db_table,$review_where=array("ProductID='$_GET[goodsno]'"),$sort="PR.CreateYmdt desc");
$pg_review->exec();

$res = $db->query($pg_review->query);

$review_cnt = 0;
while ($review_data=$db->fetch($res)){

	if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
		$review_data = validation::xssCleanArray($review_data, array(
		    validation::DEFAULT_KEY => 'text',
		    'Title' => array('html', 'ent_noquotes'),
		));
	}

	$review_data['idx'] = $pg_review->idx--;
	$review_data[Title] = nl2br(htmlspecialchars($review_data[Title]));

	$query = "select b.goodsnm,b.img_s,c.price,b.use_mobile_img,b.img_i,b.img_m,b.img_l,b.img_x,b.img_pc_x
	from
		".GD_GOODS." b
		left join ".GD_GOODS_OPTION." c on b.goodsno=c.goodsno and link and go_is_deleted <> '1' and go_is_display = '1'
	where
		b.goodsno = '" . $review_data[ProductID] . "'";
	$goodsData = $db->fetch($query, true);
	if($goodsData) {
		$review_data = array_merge($review_data, $goodsData);
	}

	### 모바일 이미지 개선용 기존 템플릿 치환코드 오버라이드 처리
	if ($review_data['use_mobile_img'] === '1') {
		$review_data['img_s'] = $review_data['img_x'];
	} else if ($review_data['use_mobile_img'] === '0') {
		$imgArr = explode('|', $review_data[$review_data['img_pc_x']]);
		$review_data['img_s'] = $imgArr[0];
	}
	
	//네이버페이 상품후기 평점
	if ($review_data[PurchaseReviewScore] == "0") {
		$review_data[PurchaseReviewScore] = "불만족";
	} else if ($review_data[PurchaseReviewScore] == "1") {
		$review_data[PurchaseReviewScore] = "보통";
	} else {
		$review_data[PurchaseReviewScore] = "만족";
	}

	
	$review_loop[] = $review_data;

}
$tpl->assign('review_cnt', $pg_review->recode['total']);
$tpl->assign('review_loop', $review_loop);
$tpl->assign( 'pg', $pg );

### 템플릿 출력
$tpl->print_('tpl');

?>