<?

include "../_header.php";

### ����üũ
if ($_GET['mode'] == "add_qna" && $cfg['qnaAuth_W'] && $cfg['qnaAuth_W'] > $sess['level']) msg("��ǰ���� �ۼ� ������ �����ϴ�", -1);
if ($_GET['mode'] == "reply_qna" && $cfg['qnaAuth_P'] && $cfg['qnaAuth_P'] > $sess['level']) msg("��ǰ���� �亯 ������ �����ϴ�", -1);

### �����Ҵ�
$mode		= $_GET[mode];
$goodsno	= $_GET[goodsno];
$sno		= $_GET[sno];

### ��ǰ ����Ÿ
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

### ����� �̹��� ������ ���� ���ø� ġȯ�ڵ� �������̵� ó��
if ($goods['use_mobile_img'] === '1') {
	$goods['img_s'] = $goods['img_x'];
} else if ($goods['use_mobile_img'] === '0') {
	$imgArr = explode('|', $goods[$goods['img_pc_x']]);
	$goods['img_s'] = $imgArr[0];
}

### ȸ������
if($mode != 'mod_qna' && $sess['m_no']){
	list($data['name'],$data['nickname']) = $db-> fetch("select name,nickname from ".GD_MEMBER." where m_no='".$sess['m_no']."' limit 1");
	if($data['nickname'])$data['name'] = $data['nickname'];
} //end if

### ��ǰ �������亯
if ( $mode == 'mod_qna' ){
	$query = "select b.m_no, b.m_id, a.subject, a.contents, a.name, a.secret, a.email, a.phone, a.rcv_sms, a.rcv_email from ".GD_GOODS_QNA." a left join ".GD_MEMBER." b on a.m_no=b.m_no where a.sno='$sno'";
	$data = $db->fetch($query,1);
	// 2013-01-16 dn ��ǰ QA �Խ��� ��ȸ�� �� ��й�ȣ �Է��� ���� �� �������� ���� ���� ���ǰ� üũ �� ��ȸ�� ���� üũ �Ͽ� ������ �̵�
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

// ���� ������ ó��
$data['email'] = ($_POST['email']) ? $_POST['email'] : $data['email'];
$data['phone'] = ($_POST['phone']) ? $_POST['phone'] : $data['phone'];
if($_POST['secret']) $data['chksecret']= " checked";
$data['subject'] = ($_POST['subject']) ? $_POST['subject'] : $data['subject'];
$data['contents'] = ($_POST['contents']) ? $_POST['contents'] : $data['contents'];

$termsPolicyCollection4 = getTermsGuideContents('terms', 'termsPolicyCollection4');
$tpl->assign('termsPolicyCollection4', $termsPolicyCollection4);

// ���Ẹ�ȼ��� ��ǰ���� ó��url
$tpl->assign('goodsQNAActionUrl',$sitelink->link('goods/indb.php','ssl'));

### ���ø� ���
$key_file = preg_replace( "'^.*$mobileRootDir/'si", "", $_SERVER['SCRIPT_NAME'] );
$key_file = preg_replace( "'\.php$'si", "_v2.htm", $key_file );

if(is_file($tpl->template_dir.'/'.$key_file)) {
	$tpl->define( array(
		'tpl' => $key_file,
	) );
}

$tpl->print_('tpl');

?>