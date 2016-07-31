<?
include dirname(__FILE__) . "/../_header.php";

$queryString = '';
$getQueryString = http_build_query($_GET);
$postQueryString = http_build_query($_POST);

if ($getQueryString) {
	$queryString = '?'.$getQueryString;
}
if ($postQueryString) {
	if ($queryString === '') {
		$queryString = '?'.$postQueryString;
	}
	else {
		$queryString .= '&'.$postQueryString;
	}
}

if (is_file($tpl->template_dir.'/goods/review_register.htm')) {
	header('Location: ../goods/review_register.php'.$queryString);
	exit();
}

/* 2013.04.03 dn ��ǰ�ı� �ۼ� ������ �߰� */
### ����üũ
if ($_GET['mode'] == "add_review" && $cfg['reviewAuth_W'] && $cfg['reviewAuth_W'] > $sess['level']) msg("�̿��ı� �ۼ� ������ �����ϴ�", -1);
if ($_GET['mode'] == "reply_review" && $cfg['reviewAuth_P'] && $cfg['reviewAuth_P'] > $sess['level']) msg("�̿��ı� �亯 ������ �����ϴ�", -1);

### �����Ҵ�
$mode		= $_GET[mode];
$goodsno	= $_GET[goodsno];
$sno		= $_GET[sno];

### �ı� ���ε� �̹��� ���� ����
if($cfg['reviewFileNum']){
	$reviewFileNum = $cfg['reviewFileNum'];
} else {
	$reviewFileNum = 1;
}

### ��ǰ ����Ÿ
$query = "
select
	goodsnm,img_s,price,img_i,img_m,img_l,use_mobile_img,img_x,img_pc_x
from
	".GD_GOODS." a
	left join ".GD_GOODS_OPTION." b on a.goodsno=b.goodsno and go_is_deleted <> '1' and go_is_display = '1'
where
	a.goodsno='$goodsno'
";
if (($goods = $db->fetch($query,1)) == false && ($sess['level'] < 80) ) {
	msg('�ش� ��ǰ�� �����Ǿ� �̿� �ı⸦ �ۼ� �� �� �����ϴ�.', -1);
	exit;
}

### ����� �̹��� ������ ���� ���ø� ġȯ�ڵ� �������̵� ó��
if ($goods['use_mobile_img'] === '1') {
	$goods['img_s'] = $goods['img_x'];
} else if ($goods['use_mobile_img'] === '0') {
	$imgArr = explode('|', $goods[$goods['img_pc_x']]);
	$goods['img_s'] = $imgArr[0];
}

### ȸ������
if($mode != 'mod_review' && $sess['m_no']){
	list($data['name'],$data['nickname']) = $db-> fetch("select name,nickname from ".GD_MEMBER." where m_no='".$sess['m_no']."' limit 1");
	if($data['nickname'])$data['name'] = $data['nickname'];
} //end if

### ��ǰ ����
if($mode == 'add_review'){
	for($ii=1;$ii<=$reviewFileNum;$ii++){
		$data[fileupload] .= "
			<tr>
				<td width=20 nowrap>".$ii."</td>
				<td width=100%>
				<input type=file name='file[]' class='attach' style='width:100%'>
				</td>
			</tr>";
	}
}
if ( $mode == 'mod_review' ){
	$query = "select a.sno, b.m_no, b.m_id, a.subject, a.contents, a.point, a.name, a.attach from ".GD_GOODS_REVIEW." a left join ".GD_MEMBER." b on a.m_no=b.m_no where a.sno='$sno'";
	$data = $db->fetch($query,1);

	$data['point'] = array( $data['point'] => 'checked' );

	if ($data[attach] == 1) {
		$data[image] = '<img src="../data/review/'.'RV'.sprintf("%010s", $data[sno]).'" width="20" style="border:1 solid #cccccc" onclick=popupImg("../data/review/'.'RV'.sprintf("%010s", $data[sno]).'","../") class=hand>';
	}
	else $data[image] = '';

}
else {
	$data['m_id'] = $sess['m_id'];
}

// ���� ������ ó��
$data['subject'] = ($_POST['subject']) ? $_POST['subject'] : $data['subject'];
$data['contents'] = ($_POST['contents']) ? $_POST['contents'] : $data['contents'];
if($_POST['point']) $data['point'] = array( $_POST['point'] => 'selected' );

### ���ø� ���
$tpl->print_('tpl');

?>