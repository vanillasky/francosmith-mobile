<?php

include dirname(__FILE__).'/../_header.php';
@include $shopRootDir.'/lib/page.class.php';
@include $shopRootDir.'/conf/config.checkout_review.php';

$pg_review = new Page(1,10);
$pg_review->field = 'a.sno, a.goodsno, a.subject, a.contents, a.point, a.regdt, a.name, b.m_no, b.m_id, a.attach, a.parent, a.notice';
$db_table = GD_GOODS_REVIEW.' a left join '.GD_MEMBER.' b on a.m_no=b.m_no WHERE a.sno = a.parent or a.parent=0';

$pg_review->setQuery($db_table, null, 'notice desc, a.sno desc, a.regdt desc');
$pg_review->exec();

$res = $db->query($pg_review->query);

$review_cnt = 0;
while ($reviewData = $db->fetch($res)){

	if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
		$reviewData = validation::xssCleanArray($reviewData, array(
		    validation::DEFAULT_KEY => 'text',
		    'contents' => array('html', 'ent_noquotes'),
			'subject'=> array('html', 'ent_noquotes'),
		));
	}

	$reviewData['idx'] = $pg_review->idx--;

	$reviewData['contents'] = nl2br(htmlspecialchars($reviewData['contents']));
	$reviewData['point'] = sprintf( "%0d", $reviewData['point']);

	$query = 'select b.goodsnm,b.img_s,c.price,b.use_mobile_img,b.img_i,b.img_m,b.img_l,b.img_x,b.img_pc_x
	from
		'.GD_GOODS." b
		left join ".GD_GOODS_OPTION." c on b.goodsno=c.goodsno and link and go_is_deleted <> '1' and go_is_display = '1'
	where
		b.goodsno = '" . $reviewData['goodsno'] . "'";
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

	$replyQuery = "SELECT subject, contents, regdt, sno, m_no FROM ".GD_GOODS_REVIEW." WHERE parent='".$reviewData['sno']."' AND sno != parent";

	$replyResultSet = $db->_select($replyQuery);

	$reviewData['reply'] =$replyResultSet;

	$reviewData['reply_cnt'] = count($replyResultSet);

	if ($reviewData['attach'] == 1) {
		$upload_folder = '../..'.$cfg['rootDir'].'/data/review/';
		$tmp_img = '';
		for ($attachIndex = 0; $attachIndex < 10; $attachIndex++) {
			if ($attachIndex == 0) {
				$upload_file = 'RV'.sprintf('%010s', $reviewData['sno']);
			} else {
				$upload_file = 'RV'.sprintf('%010s', $reviewData['sno']).'_'.$attachIndex;
			}
			if (file_exists($upload_folder.$upload_file)) {
				$tmp_img .= '<br/><img src="'.$upload_folder.$upload_file.'" name="rv_attach_image[]" border="0"/>';
			}
			if ($tmp_img) {
				$reviewData['image'] = $tmp_img;
			}
		}
	}
	else {
		$reviewData['image'] = '';
	}

	$reviewData['review_name'] = '';

	/* encoding 고려하여 수정 해야 함 */
	if($reviewData['name']) {
		$tmp_name = $reviewData['name'];
	}
	else {
		$tmp_name = $reviewData['m_id'];
	}

	if(!preg_match('/[0-9A-Za-z]/', substr($tmp_name, 0, 1))) {
		$division_num = 2;
	}
	else {
		$division_num = 1;
	}

	$reviewData['review_name'] = substr($tmp_name, 0, $division_num).implode('', array_fill(0, intval((strlen($tmp_name) -1)/$division_num), '*'));


	for($i=0; $i<5; $i++) {

		if($i < $reviewData['point']) {
			$reviewData['point_star'] .= '<span class="active">★</span>';
		}
		else {
			$reviewData['point_star'] .= '★';
		}
	}

	$reviewData[authdelete] = 'Y'; # 삭제 권한초기값

	if ( empty($cfg['reviewWriteAuth']) || isset($sess) || !empty($reviewData[m_no]) ){ // 회원전용 or 회원 or 작성자==회원
				$reviewData[authdelete] = ( isset($sess) && $sess[m_no] == $reviewData[m_no] ? 'Y' : 'N' );
	}

	if(!empty($reviewData['reply'])){
		for($i=0; $i<$reviewData['reply_cnt']; $i++){
			$reviewData['reply'][$i][authdelete] = 'Y';
			if ( empty($cfg['reviewWriteAuth']) || isset($sess) || !empty($reviewData['reply'][$i][m_no]) ){
				$reviewData['reply'][$i][authdelete] = ( isset($sess) && $sess[m_no] == $reviewData['reply'][$i][m_no] ? 'Y' : 'N' );
			}
		}
	}
	$reviewData[authdelete] = ( $reviewData[reply_cnt] > 0 ? 'N' : $reviewData[authdelete] ); # 답글 있는 경우 삭제 불가

	$review_loop[] = $reviewData;

}

$tpl->assign('review_total', $pg_review->recode['total']);
$tpl->assign('review_cnt', $pg_review->recode['total']);
$tpl->assign('review_loop', $review_loop);
$tpl->assign( 'pg', $pg );



### 템플릿 출력
$tpl->print_('tpl');

?>