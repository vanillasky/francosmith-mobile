<?php

include dirname(__FILE__).'/../_header.php';

// 접근체크
if ($_GET['mode'] == 'add_review' && $cfg['reviewAuth_W'] && $cfg['reviewAuth_W'] > $sess['level']) {
	msg('이용후기 작성 권한이 없습니다', -1);
}
if ($_GET['mode'] == 'reply_review' && $cfg['reviewAuth_P'] && $cfg['reviewAuth_P'] > $sess['level']) {
	msg('이용후기 답변 권한이 없습니다', -1);
}

// 변수할당
$mode = $_GET['mode'];
$goodsno = $_GET['goodsno'];
$sno = $_GET['sno'];

// 후기 업로드 이미지 갯수 설정
if($cfg['reviewFileNum']){
	$reviewFileNum = $cfg['reviewFileNum'];
} else {
	$reviewFileNum = 1;
}

// 상품 데이타
if ($goodsno) {
	$query = '
	SELECT g.goodsnm, g.img_s, go.price, g.use_mobile_img, g.img_i, g.img_m, g.img_l, g.img_x, g.img_pc_x
	FROM '.GD_GOODS.' AS g
	LEFT JOIN '.GD_GOODS_OPTION.' go
	ON g.goodsno=go.goodsno AND go_is_deleted <> "1" AND go_is_display = "1"
	WHERE g.goodsno="'.$goodsno.'"
	';
	$goods = $db->fetch($query, true);

	### 모바일 이미지 개선용 기존 템플릿 치환코드 오버라이드 처리
	if ($goods['use_mobile_img'] === '1') {
		$goods['img_s'] = $goods['img_x'];
	} else if ($qna_data['use_mobile_img'] === '0') {
		$imgArr = explode('|', $goods[$goods['img_pc_x']]);
		$goods['img_s'] = $imgArr[0];
	}
}

// 회원정보
if ($mode != 'mod_review' && $sess['m_no']) {
	list($data['name'], $data['nickname']) = $db-> fetch('SELECT name, nickname FROM '.GD_MEMBER.' WHERE m_no='.$sess['m_no'].' LIMIT 1');
	if ($data['nickname']) {
		$data['name'] = $data['nickname'];
	}
}

// 상품 사용기
$data['m_id'] = $sess['m_id'];

// 받은 데이터 처리
$data['subject'] = ($_GET['subject']) ? $_GET['subject'] : $data['subject'];
$data['contents'] = ($_GET['contents']) ? $_GET['contents'] : $data['contents'];
if($_GET['point']) $data['point'] = array( $_GET['point'] => 'selected' );

// 템플릿 출력
$tpl->print_('tpl');

?>