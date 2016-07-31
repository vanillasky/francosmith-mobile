<?

include "../_header.php";

### 접근체크
if ($_GET['mode'] == "add_qna" && $cfg['qnaAuth_W'] && $cfg['qnaAuth_W'] > $sess['level']) msg("상품문의 작성 권한이 없습니다", -1);
if ($_GET['mode'] == "reply_qna" && $cfg['qnaAuth_P'] && $cfg['qnaAuth_P'] > $sess['level']) msg("상품문의 답변 권한이 없습니다", -1);

### 변수할당
$mode		= $_GET[mode];
$goodsno	= $_GET[goodsno];
$sno		= $_GET[sno];

### 상품 데이타
$query = "
select
	goodsnm,img_s,price,use_mobile_img,img_i,img_m,img_l,img_x,img_pc_x
from
	".GD_GOODS." a
	left join ".GD_GOODS_OPTION." b on a.goodsno=b.goodsno and go_is_deleted <> '1' and go_is_display = '1'
where
	a.goodsno='$goodsno'
";
$goods = $db->fetch($query,1);

### 모바일 이미지 개선용 기존 템플릿 치환코드 오버라이드 처리
if ($goods['use_mobile_img'] === '1') {
	$goods['img_s'] = $goods['img_x'];
} else if ($goods['use_mobile_img'] === '0') {
	$imgArr = explode('|', $goods[$goods['img_pc_x']]);
	$goods['img_s'] = $imgArr[0];
}

### 회원정보
if($mode != 'mod_qna' && $sess['m_no']){
	list($data['name'],$data['nickname']) = $db-> fetch("select name,nickname from ".GD_MEMBER." where m_no='".$sess['m_no']."' limit 1");
	if($data['nickname'])$data['name'] = $data['nickname'];
} //end if

### 상품 질문과답변
if ( $mode == 'mod_qna' ){
	$query = "select b.m_no, b.m_id, a.subject, a.contents, a.name, a.secret, a.email, a.phone, a.rcv_sms, a.rcv_email from ".GD_GOODS_QNA." a left join ".GD_MEMBER." b on a.m_no=b.m_no where a.sno='$sno'";
	$data = $db->fetch($query,1);
	// 2013-01-16 dn 상품 QA 게시판 비회원 글 비밀번호 입력후 수정 폼 보여지게 수정 관련 세션값 체크 및 비회원 여부 체크 하여 페이지 이동
	$qna_auth = unserialize($_SESSION['qna_auth']);
	if(!$qna_auth) $qna_auth = array();
	if(!in_array($sno, $qna_auth)) {
		if(!$data['m_no']) {
			go('./goods_qna_pass.php?mode=auth_nomember&sno='.$sno);
		}
	}
	$data['chksecret']= "";
	if($data['secret'])$data['chksecret']= " checked";
}
else {
	$data['m_id'] = $sess['m_id'];
}

// 받은 데이터 처리
$data['email'] = ($_POST['email']) ? $_POST['email'] : $data['email'];
$data['phone'] = ($_POST['phone']) ? $_POST['phone'] : $data['phone'];
if($_POST['secret']) $data['chksecret']= " checked";
$data['subject'] = ($_POST['subject']) ? $_POST['subject'] : $data['subject'];
$data['contents'] = ($_POST['contents']) ? $_POST['contents'] : $data['contents'];

$termsPolicyCollection4 = getTermsGuideContents('terms', 'termsPolicyCollection4');
$tpl->assign('termsPolicyCollection4', $termsPolicyCollection4);

// 무료보안서버 상품문의 처리url
$tpl->assign('goodsQNAActionUrl',$sitelink->link('goods/indb.php','ssl'));

### 템플릿 출력
$key_file = preg_replace( "'^.*$mobileRootDir/'si", "", $_SERVER['SCRIPT_NAME'] );
$key_file = preg_replace( "'\.php$'si", "_v2.htm", $key_file );

if(is_file($tpl->template_dir.'/'.$key_file)) {
	$tpl->define( array(
		'tpl' => $key_file,
	) );
}

$tpl->print_('tpl');

?>