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
	### ȸ�������� ���ô�� & ȸ����۰� �������� �� �⺻Ű
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

### ��ǰ ����
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

	### ����� �̹��� ������ ���� ���ø� ġȯ�ڵ� �������̵� ó��
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

/* 2013.04.03 dn ��ǰ �ı�� �ۼ� ���� �߰� ���� */
if($_GET['goodsno']) {
	$tpl->assign('goodsno', $_GET['goodsno']);
}
/* 2013.04.03 dn ��ǰ �ı�� �ۼ� ���� �߰� �� */

/* ��Ų�� �ٲ�鼭 ������ ���¸� �����ؾ� �ؼ� �ٽ� �ѹ� �����´�. ���� 1���� ��������, �ٲ� ��Ų������ ajax�� �ٸ� �������� ȣ���Ͽ� �����͸� �����´� */
// ��ǰ �ı� ��������

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

	### ����� �̹��� ������ ���� ���ø� ġȯ�ڵ� �������̵� ó��
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

	/* encoding ����Ͽ� ���� �ؾ� �� */
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
			$review_data['point_star'] .= '<span class="active">��</span>';
		}
		else {
			$review_data['point_star'] .= '��';
		}
	}

	$review_data[authdelete] = 'Y'; # ���� �����ʱⰪ

	if ( empty($cfg['reviewWriteAuth']) || isset($sess) || !empty($review_data[m_no]) ){ // ȸ������ or ȸ�� or �ۼ���==ȸ��
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

	$review_data[authdelete] = ( $review_data[reply_cnt] > 0 ? 'N' : $review_data[authdelete] ); # ��� �ִ� ��� ���� �Ұ�

	$review_loop[] = $review_data;

}

$tpl->assign('review_cnt', $pg_review->recode['total']);
$tpl->assign('review_loop', $review_loop);
$tpl->assign( 'pg', $pg );

### ���ø� ���
$tpl->print_('tpl');

?>