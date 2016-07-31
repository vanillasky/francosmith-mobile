<?
include dirname(__FILE__) . "/../_header.php";
@include $shopRootDir . "/lib/page.class.php";


if($_GET['goodsno']) {
	$where[] = "a.goodsno = '$_GET[goodsno]'";
	$review_where[] = "a.goodsno = '$_GET[goodsno]'";
	$review_where[] = "a.sno = a.parent";
}
else {
	chkMemberMobile();
	### 회원질문과 관련답글 & 회원답글과 관련질문 의 기본키
	$qna_sno = array();
	$res = $db->query( "select sno, parent from ".GD_GOODS_REVIEW." where m_no='$sess[m_no]'" );
	while ( $row = $db->fetch( $res ) ){
		if ( $row['sno'] == $row['parent'] ){
			$res_s = $db->query( "select sno from ".GD_GOODS_REVIEW." where parent='$row[sno]'" );
			while ( $row_s = $db->fetch( $res_s ) ) $qna_sno[] = $row_s['sno'];
		}
		else if ( $row['sno'] != $row['parent'] ){
			$qna_sno[] = $row['sno'];
			$qna_sno[] = $row['parent'];
		}
	}

	if ( count( $qna_sno ) ) $where[] = "a.sno in ('" . implode( "','", $qna_sno ) . "')";
	else $where[] = "0";

	$review_where[] = "a.m_no = '$sess[m_no]'";
	$review_where[] = "a.sno = a.parent";
}

### 상품 사용기
$pg = new Page($_GET[page], 20);
$pg->field = "distinct a.sno, a.goodsno, a.subject, a.contents, a.point, a.regdt, a.name, b.m_no, b.m_id, a.attach, a.parent";
$db_table = "".GD_GOODS_REVIEW." a left join ".GD_MEMBER." b on a.m_no=b.m_no";

$pg->setQuery($db_table,$where,$sort="a.parent desc, ( case when a.parent=a.sno then 0 else 1 end ) asc, a.regdt desc");
$pg->exec();

$res = $db->query($pg->query);
while ($data=$db->fetch($res)){

	if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
		$data = validation::xssCleanArray($data, array(
		    validation::DEFAULT_KEY => 'text',
		    'contents' => array('html', 'ent_noquotes'),
			'subject'=>'html',
		));
	}

	$data['idx'] = $pg->idx--;
	$data[contents] = nl2br(htmlspecialchars($data[contents]));
	$data[point] = sprintf( "%0d", $data[point]);

	$query = "select b.goodsnm,b.img_s,c.price,b.use_mobile_img,b.img_i,b.img_m,b.img_l,b.img_x,b.img_pc_x
	from
		".GD_GOODS." b
		left join ".GD_GOODS_OPTION." c on b.goodsno=c.goodsno and link and go_is_deleted <> '1' and go_is_display = '1'
	where
		b.goodsno = '" . $data[goodsno] . "'";
	$goodsData = $db->fetch($query, true);
	if($goodsData) {
		$data = array_merge($data, $goodsData);
	}

	### 모바일 이미지 개선용 기존 템플릿 치환코드 오버라이드 처리
	if ($data['use_mobile_img'] === '1') {
		$data['img_s'] = $data['img_x'];
	} else if ($data['use_mobile_img'] === '0') {
		$imgArr = explode('|', $data[$data['img_pc_x']]);
		$data['img_s'] = $imgArr[0];
	}

	if ($data[attach] == 1) {
		$data[image] = '<img src="../../shop/data/review/'.'RV'.sprintf("%010s", $data[sno]).'">';
	}
	else $data[image] = '';

	$loop[] = $data;
}

/* 2013.04.03 dn 상품 후기글 작성 관련 추가 시작 */
if($_GET['goodsno']) {
	$tpl->assign('goodsno', $_GET['goodsno']);
}
/* 2013.04.03 dn 상품 후기글 작성 관련 추가 끝 */

/* 스킨이 바뀌면서 데이터 형태를 변경해야 해서 다시 한번 가져온다. 최초 1번만 가져오며, 바뀐 스킨에서는 ajax로 다른 페이지를 호출하여 데이터를 가져온다 */
// 상품 후기 가져오기

$pg_review = new Page(1,10);
$pg_review->field = "a.sno, a.goodsno, a.subject, a.contents, a.point, a.regdt, a.name, b.m_no, b.m_id, a.attach, a.parent";
$db_table = "".GD_GOODS_REVIEW." a left join ".GD_MEMBER." b on a.m_no=b.m_no";

$pg_review->setQuery($db_table,$review_where,$sort="a.sno desc, a.regdt desc");
$pg_review->exec();

$res = $db->query($pg_review->query);

$review_cnt = 0;
while ($review_data=$db->fetch($res)){

	if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
		$review_data = validation::xssCleanArray($review_data, array(
		    validation::DEFAULT_KEY => 'text',
		    'contents' => array('html', 'ent_noquotes'),
		));
	}

	$review_data['idx'] = $pg_review->idx--;
	$review_data[contents] = nl2br(htmlspecialchars($review_data[contents]));
	$review_data[point] = sprintf( "%0d", $review_data[point]);

	$query = "select b.goodsnm,b.img_s,c.price,b.use_mobile_img,b.img_i,b.img_m,b.img_l,b.img_x,b.img_pc_x
	from
		".GD_GOODS." b
		left join ".GD_GOODS_OPTION." c on b.goodsno=c.goodsno and link and go_is_deleted <> '1' and go_is_display = '1'
	where
		b.goodsno = '" . $review_data[goodsno] . "'";
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

	$reply_query = "SELECT subject, contents, regdt, sno, m_no FROM ".GD_GOODS_REVIEW." WHERE parent='".$review_data[sno]."' AND sno != parent";

	$reply_res = $db->_select($reply_query);

	$review_data['reply'] =$reply_res;

	$review_data['reply_cnt'] = count($reply_res);


	if ($review_data[attach] == 1) {
		$review_data[image] = '<img src="../../shop/data/review/'.'RV'.sprintf("%010s", $review_data[sno]).'">';
	}
	else $review_data[image] = '';

	$review_data['review_name'] = '';

	/* encoding 고려하여 수정 해야 함 */
	if($review_data['name']) {
		$tmp_name = $review_data['name'];
	}
	else {
		$tmp_name = $review_data['m_id'];
	}

	if(!preg_match('/[0-9A-Za-z]/', substr($tmp_name, 0, 1))) {
		$division_num = 2;
	}
	else {
		$division_num = 1;
	}

	$review_data['review_name'] = substr($tmp_name, 0, $division_num).implode('', array_fill(0, intval((strlen($tmp_name) -1)/$division_num), '*'));


	for($i=0; $i<5; $i++) {

		if($i < $review_data['point']) {
			$review_data['point_star'] .= '<span class="active">★</span>';
		}
		else {
			$review_data['point_star'] .= '★';
		}
	}

	$review_data[authdelete] = 'Y'; # 삭제 권한초기값

	if ( empty($cfg['reviewWriteAuth']) || isset($sess) || !empty($review_data[m_no]) ){ // 회원전용 or 회원 or 작성자==회원
				$review_data[authdelete] = ( isset($sess) && $sess[m_no] == $review_data[m_no] ? 'Y' : 'N' );
			}

	if(!empty($review_data['reply'])){
		for($i=0; $i<$review_data['reply_cnt']; $i++){
			$review_data['reply'][$i][authdelete] = 'Y';
			if ( empty($cfg['reviewWriteAuth']) || isset($sess) || !empty($review_data['reply'][$i][m_no]) ){
				$review_data['reply'][$i][authdelete] = ( isset($sess) && $sess[m_no] == $review_data['reply'][$i][m_no] ? 'Y' : 'N' );
			}
		}
	}

	$review_data[authdelete] = ( $review_data[reply_cnt] > 0 ? 'N' : $review_data[authdelete] ); # 답글 있는 경우 삭제 불가

	$review_loop[] = $review_data;

}

$tpl->assign('review_cnt', $pg_review->recode['total']);
$tpl->assign('review_loop', $review_loop);
$tpl->assign( 'pg', $pg );

### 템플릿 출력
$tpl->print_('tpl');

?>