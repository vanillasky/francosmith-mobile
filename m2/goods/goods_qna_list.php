<?
$noDemoMsg = $indexLog = 1;
include "../_header.php";
@include $shopRootDir."/lib/page.class.php";
@include $shopRootDir."/lib/goods_qna.lib.php";

### 변수할당
$goodsno = $_GET[goodsno];
if(!$cfg['qnaWriteAuth']) $cfg['qnaWriteAuth'] = (isset($cfg['qnaAuth_W']) && !$cfg['qnaAuth_W']) ? "free" : ""; // 글쓰기 권한

### 페이지 설정
if(!$cfg['qnaListCnt']) $cfg['qnaListCnt'] = 5;

### 상품 질문과답변
$pg = new Page($_GET[page],$cfg['qnaListCnt']);
$pg -> field = "b.m_no, b.m_id,b.name as m_name,a.*";

if($_GET['goodsno']) {
	$where[] = "a.goodsno = '$_GET[goodsno]'";

	$qna_where[] = "a.goodsno = '$goodsno'";
	$qna_where[] = "a.parent = a.sno";
}
else if($_GET['isAll'] == 'Y'){
	$qna_where = array();
	$qna_where[] = "a.parent = a.sno";
}
else {
	chkMemberMobile();
	### 회원질문과 관련답글 & 회원답글과 관련질문 의 기본키
	$qna_sno = array();

	$res = $db->query( "select sno, parent from ".GD_GOODS_QNA." where m_no='$sess[m_no]'" );
	while ( $row = $db->fetch( $res ) ) {
		if ( $row['sno'] == $row['parent'] ) {
			$res_s = $db->query( "select sno from ".GD_GOODS_QNA." where parent='$row[sno]'" );
			while ( $row_s = $db->fetch( $res_s ) ) $qna_sno[] = $row_s['sno'];
		}
		else if ( $row['sno'] != $row['parent'] ){
			$qna_sno[] = $row['sno'];
			$qna_sno[] = $row['parent'];
		}
	}

	if ( count( $qna_sno ) ) $where[] = "a.sno in ('" . implode( "','", $qna_sno ) . "')";
	else $where[] = "0";

	$qna_where[] = "a.m_no = '$sess[m_no]'";
	$qna_where[] = "a.parent = a.sno";
}

$where[]="notice!='1'";
$pg->setQuery($db_table=GD_GOODS_QNA." a left join ".GD_MEMBER." b on a.m_no=b.m_no",$where,$sort="parent desc, ( case when parent=a.sno then 0 else 1 end ) asc, regdt desc");
$pg->exec();

$res = $db->query($pg->query);

while ($data=$db->fetch($res)){
	if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
		$data = validation::xssCleanArray($data, array(
			validation::DEFAULT_KEY => 'text',
			'contents' => array('html', 'ent_noquotes'),
			'subject' => array('html', 'ent_noquotes'),
		));
	}
	$data['contents'] = nl2br($data['contents']);

	### 원글 체크
	list($data['parent_m_no'],$data['secret'],$data['type']) = goods_qna_answer($data['sno'],$data['parent'],$data['secret']);
	list($data['answer_yn']) = goods_qna_answer_yn($data['sno']);

	### 권한체크
	if(!$cfg['qnaSecret']) $data['secret'] = 0;
	list($data['authmodify'],$data['authdelete'],$data['authview']) = goods_qna_chkAuth($data);

	### 비밀글 아이콘
	$data['secretIcon'] = 0;
	if($data['secret'] == '1') $data['secretIcon'] = 1;

	$query = "select b.goodsnm,b.img_s,c.price
			from
				".GD_GOODS." b
				left join ".GD_GOODS_OPTION." c on b.goodsno=c.goodsno and link and go_is_deleted <> '1' and go_is_display = '1'
			where
				b.goodsno = '" . $data[goodsno] . "'";
	list( $data[goodsnm], $data[img_s], $data[price] ) = $db->fetch($query);

	$data['img_html'] = goodsimgMobile($data[img_s],100);

	### 순번처리
	$data['idx'] = $pg->idx--;

	### 관리자 아이콘 출력
	list( $level ) = $db->fetch("select level from ".GD_MEMBER." where m_no!='' and m_no='".$data['m_no']."'");
	if ( $level == '100' && $adminicon ) $data['m_id'] = $data['name'] = "<img src='../data/skin/".$cfg['tplSkin']."/".$adminicon."' border=0>";

	if ($ici_admin) $data['accessable'] = true;
	else if ($data['secret'] != '1') $data['accessable'] = true;
	else if ($data['m_no'] > 0 && $sess['m_no'] == $data['m_no']) $data['accessable'] = true;
	else $data['accessable'] = false;

	$loop[] = $data;
}

/* 상품 문의글 작성 관련 추가 시작 */
if($_GET['goodsno']) {
	$tpl->assign('goodsno', $_GET['goodsno']);
}
/* 상품 문의글 작성 관련 추가 끝 */

// 상품 문의 가져오기


	$pg_qna = new Page(1,10);
	$pg_qna -> field = "b.m_no, b.m_id,b.name as m_name,a.*";

	$where[]="notice!='1'";
	$pg_qna->setQuery($db_table=GD_GOODS_QNA." a left join ".GD_MEMBER." b on a.m_no=b.m_no",$qna_where,$sort="notice desc, parent desc, ( case when parent=a.sno then 0 else 1 end ) asc, regdt desc");

	$pg_qna->exec();

	$res = $db->query($pg_qna->query);
	while ($qna_data=$db->fetch($res)){
		if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
			$qna_data = validation::xssCleanArray($qna_data, array(
				validation::DEFAULT_KEY => 'text',
				'contents' => array('html', 'ent_noquotes'),
			));
		}
		$qna_data['contents'] = nl2br($qna_data['contents']);
		$qna_data['idx'] = $pg_qna->idx--;

		### 원글 체크
		list($qna_data['parent_m_no'],$qna_data['secret'],$qna_data['type']) = goods_qna_answer($qna_data['sno'],$qna_data['parent'],$qna_data['secret']);

		$reply_query = "SELECT subject, contents, regdt, sno, m_no FROM ".GD_GOODS_QNA." WHERE parent='".$qna_data[sno]."' AND sno != parent";
		$reply_res = $db->_select($reply_query);

		$qna_data['reply'] =$reply_res;
		$qna_data['reply_cnt'] = count($reply_res);
		
		
		### 권한체크
		if(!$cfg['qnaSecret']) $qna_data['secret'] = 0;
		list($qna_data['authmodify'],$qna_data['authdelete'],$qna_data['authview']) = goods_qna_chkAuth($qna_data);
	
		### 댓글 권한 체크
		if(!empty($qna_data['reply'])){
			for($i=0; $i<$qna_data['reply_cnt']; $i++){
				list($qna_data['reply'][$i]['authmodify'],$qna_data['reply'][$i]['authdelete'],$qna_data['reply'][$i]['authview']) = goods_qna_chkAuth($qna_data['reply'][$i]);
			}
		}

		### 상품정보
		$query = "select b.goodsnm,b.img_s,c.price,b.use_mobile_img,b.img_i,b.img_m,b.img_l,b.img_x,b.img_pc_x
			from
				".GD_GOODS." b
				left join ".GD_GOODS_OPTION." c on b.goodsno=c.goodsno and link and go_is_deleted <> '1' and go_is_display = '1'
			where
				b.goodsno = '" . $qna_data[goodsno] . "'";
		$goodsData = $db->fetch($query, true);
		if($goodsData) {
			$qna_data = array_merge($qna_data, $goodsData);
		}

		### 모바일 이미지 개선용 기존 템플릿 치환코드 오버라이드 처리
		if ($qna_data['use_mobile_img'] === '1') {
			$qna_data['img_s'] = $qna_data['img_x'];
		} else if ($qna_data['use_mobile_img'] === '0') {
			$imgArr = explode('|', $qna_data[$qna_data['img_pc_x']]);
			$qna_data['img_s'] = $imgArr[0];
		}

		### 비밀글 아이콘
		$qna_data['secretIcon'] = 0;
		if($qna_data['secret'] == '1') $qna_data['secretIcon'] = 1;

		if($qna_data['name']) {
			$tmp_name = $qna_data['name'];
		}
		else {
			$tmp_name = $qna_data['m_id'];
		}

		if(!preg_match('/[0-9A-Za-z]/', substr($tmp_name, 0, 1))) {
			$division_num = 2;
		}
		else {
			$division_num = 1;
		}

		$qna_data['qna_name'] = substr($tmp_name, 0, $division_num).implode('', array_fill(0, intval((strlen($tmp_name) -1)/$division_num), '*'));

		if ($ici_admin) $qna_data['accessable'] = true;
		else if ($qna_data['secret'] != '1') $qna_data['accessable'] = true;
		else if ($qna_data['m_no'] > 0 && $sess['m_no'] == $qna_data['m_no']) $qna_data['accessable'] = true;
		else $qna_data['accessable'] = false;

		$qna_loop[] = $qna_data;

	}

$tpl->assign('qna_cnt', $pg_qna->recode['total']);
$tpl->assign('qna_loop', $qna_loop);
$tpl->assign( 'pg', $pg );

### 템플릿 출력
$key_file = preg_replace( "'^.*$mobileRootDir/'si", "", $_SERVER['SCRIPT_NAME'] );
$key_file = preg_replace( "'\.php$'si", "_v2.htm", $key_file );

if(is_file($tpl->template_dir.'/'.$key_file)) {
	$tpl->define( array(
		'tpl'			=> $key_file,
	) );
}

$tpl->print_('tpl');
?>