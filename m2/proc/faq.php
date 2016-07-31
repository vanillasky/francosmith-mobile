<?php

include dirname(__FILE__).'/../_header.php';

$codeitem = codeitem('faq');

# 압축코드 정의
$summary_search = array();
$summary_search[] = "/__shopname__/is";			# 쇼핑몰이름
$summary_search[] = "/__shopdomain__/is";		# 쇼핑몰주소
$summary_search[] = "/__shopcpaddr__/is";		# 사업장주소
$summary_search[] = "/__shopcoprnum__/is";		# 사업자등록번호
$summary_search[] = "/__shopcpmallceo__/is";	# 쇼핑몰 대표
$summary_search[] = "/__shopcpmanager__/is";	# 개인정보관리자
$summary_search[] = "/__shoptel__/is";			# 쇼핑몰 전화
$summary_search[] = "/__shopfax__/is";			# 쇼핑몰 팩스
$summary_search[] = "/__shopmail__/is";			# 쇼핑몰 이메일

$summary_replace = array();
$summary_replace[] = $cfg["shopName"];			# 쇼핑몰이름
$summary_replace[] = $cfg["shopUrl"];			# 쇼핑몰주소
$summary_replace[] = $cfg["address"];			# 사업장주소
$summary_replace[] = $cfg["compSerial"];		# 사업자등록번호
$summary_replace[] = $cfg["ceoName"];			# 쇼핑몰 대표
$summary_replace[] = $cfg["adminName"];			# 개인정보관리자
$summary_replace[] = $cfg["compPhone"];			# 쇼핑몰 전화
$summary_replace[] = $cfg["compFax"];			# 쇼핑몰 팩스
$summary_replace[] = $cfg["adminEmail"];		# 쇼핑몰 이메일

### FAQ
if (!$_GET[page_num]) $_GET[page_num] = 10       ;
if (!$_GET[sitemcd]) $_GET[sitemcd] = '02';

$pg = new Page($_GET[page],$_GET[page_num]);
$pg->field = "sno, itemcd, question, descant, answer";
$db_table = "".GD_FAQ."";

if ($_GET[sitemcd]){
	$where[] = "itemcd='$_GET[sitemcd]'";
}

$pg->setQuery($db_table,$where,$sort='sort');
$pg->exec();

$res = $db->query($pg->query);

$k = 0;
while ($data=$db->fetch($res)){

	$data['idx'] = $pg->idx--;

	if ( blocktag_exists( $data[descant] ) == false ){
		$data[descant] = nl2br($data[descant]);
	}

	$data[descant] = preg_replace( $summary_search, $summary_replace, $data[descant] );

	if ( blocktag_exists( $data[answer] ) == false ){
		$data[answer] = nl2br($data[answer]);
	}

	$data[answer] = preg_replace( $summary_search, $summary_replace, $data[answer] );

	$loop[] = $data;
	$k++;
}

$tpl->assign('item_cnt',$k);
$tpl->assign('loop',$loop);
$tpl->print_('tpl');

?>