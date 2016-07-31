<?

include dirname(__FILE__) . "/../_header.php"; 
@include $shopRootDir . "/lib/page.class.php";

chkMemberMobile();

$itemcds = codeitem( 'question' ); # 질문유형

### 1:1 문의.........2007-07-19 필드추가로불러옴=>b.name
$pg = new Page($_GET[page],10);
$pg->field = "distinct a.sno, a.parent, a.itemcd, a.subject, a.contents, a.ordno, a.regdt as regdt, b.m_no, b.m_id, b.name";
$db_table = "".GD_MEMBER_QNA." a left join ".GD_MEMBER." b on a.m_no=b.m_no";

$where[] = "
a.sno in (select sno from ".GD_MEMBER_QNA." where m_no='$sess[m_no]')
OR a.sno in (select parent from ".GD_MEMBER_QNA." where m_no='$sess[m_no]')
OR a.parent in (select sno from ".GD_MEMBER_QNA." where m_no='$sess[m_no]')
OR a.parent in (select parent from ".GD_MEMBER_QNA." where m_no='$sess[m_no]')
";
$pg->setQuery($db_table,$where,$sort="parent desc, ( case when parent=a.sno then 0 else 1 end ) asc, regdt desc");
$pg->exec();

$res = $db->query($pg->query);
while ($data=$db->fetch($res)){
	if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
		$data = validation::xssCleanArray($data, array(
			validation::DEFAULT_KEY => 'text',
			'subject'=>'html',
			'contents'=>'html',
		));
	}

	$data['idx'] = $pg->idx--;

	$data[authmodify] = ( isset($sess) && $sess[m_no] == $data[m_no] ? 'Y' : 'N' );
	$data[authdelete] = ( isset($sess) && $sess[m_no] == $data[m_no] ? 'Y' : 'N' );

	if ( $data[sno] == $data[parent] ){
		$data[authreply] = ( isset($sess) ? 'Y' : 'N' );
	}
	else $data[authreply] = 'N';

	if ( $data[sno] == $data[parent] ){ // 질문

		$data[itemcd] = $itemcds[ $data[itemcd] ];
		if($data['notice'] == 1) $data['itemcd'] = "공지사항";

		if ( isset($sess) && $sess[m_no] == $data[m_no] ){
			list( $data[replecnt] ) = $db->fetch("select count(*) from ".GD_MEMBER_QNA." where sno != parent and parent='$data[sno]'");
		}
		else {
			list( $data[replecnt] ) = $db->fetch("select count(*) from ".GD_MEMBER_QNA." where sno != parent and parent='$data[sno]' and m_no='$sess[m_no]'");
		}
	}

	$data[authdelete] = ( $data[replecnt] > 0 ? 'N' : $data[authdelete] ); # 답글 있는 경우 삭제 불가

	$data[contents] = nl2br($data[contents]);
	$loop[] = $data;
}

/* 스킨이 바뀌면서 데이터 형태를 변경해야 해서 다시 한번 가져온다. 최초 1번만 가져오며, 바뀐 스킨에서는 ajax로 다른 페이지를 호출하여 데이터를 가져온다 */
// 1:1 문의 가져오기
$pg_member_qna = new Page($_GET[page],10);
$pg_member_qna->field = "distinct a.sno, a.parent, a.itemcd, a.subject, a.contents, a.ordno, a.regdt as regdt, b.m_no, b.m_id, b.name, a.notice";
$db_table = "".GD_MEMBER_QNA." a left join ".GD_MEMBER." b on a.m_no=b.m_no";

$member_qna_where[] = "a.sno = a.parent";
$member_qna_where[] = "(a.m_no = '$sess[m_no]' or a.notice='1' or a.sno in (select parent from ".GD_MEMBER_QNA." where m_no='$sess[m_no]'))";

$pg_member_qna->setQuery($db_table,$member_qna_where,$sort="a.notice desc, a.sno desc, a.regdt desc");
$pg_member_qna->exec();

$res_member_qna = $db->query($pg_member_qna->query);
while ($data_member_qna=$db->fetch($res_member_qna)){
	if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
		$data_member_qna = validation::xssCleanArray($data_member_qna, array(
			validation::DEFAULT_KEY => 'text',
			'subject'=>'html',
			'contents'=>'html',
		));
	}

	$data_member_qna['idx'] = $pg_member_qna->idx--;

	$data_member_qna[itemcd] = $itemcds[ $data_member_qna[itemcd] ];
	$data_member_qna[contents] = nl2br($data_member_qna[contents]);

	if($data_member_qna['notice'] == 1) {	//notice 필드값이 1이면
		$data_member_qna['itemcd'] = "공지사항";	//질문유형을 공지사항으로 설정
	}

	$reply_query = "SELECT subject, contents, regdt FROM ".GD_MEMBER_QNA." WHERE parent='".$data_member_qna[sno]."' AND sno != parent";
	$reply_res = $db->_select($reply_query);

	$data_member_qna['reply'] =$reply_res;
	$data_member_qna['reply_cnt'] = count($reply_res);

	$member_qna_loop[] = $data_member_qna;
}


$tpl->assign( 'member_qna_loop', $member_qna_loop );
$tpl->assign('member_qna_cnt', $pg_member_qna->recode['total']);

$tpl->assign( 'pg', $pg );

### 템플릿 출력
$tpl->print_('tpl');

?>
