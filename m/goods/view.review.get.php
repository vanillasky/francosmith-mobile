<?php

@include dirname(__FILE__) . "/../lib/library.php";
@include $shopRootDir . "/lib/page.class.php";
@include $shopRootDir . "/lib/json.class.php";

$json = new Services_JSON();

### 변수할당
$goodsno = $_GET[goodsno];
if ( file_exists( dirname(__FILE__) . '/../../shop/data/skin/' . $cfg['tplSkin'] . '/admin.gif' ) ) $adminicon = 'admin.gif';

### 페이지 설정
if(!$cfg['reviewListCnt']) $cfg['reviewListCnt'] = $_GET['pageNum'];

### 상품 사용기
$pg = new Page($_GET[page],$cfg['reviewListCnt']);
$pg->field = "b.m_no, b.m_id, a.sno, a.goodsno, a.subject, a.contents, a.point, a.regdt, a.emoney, a.name, b.name as m_name,a.parent";
$pg->setQuery($db_table="".GD_GOODS_REVIEW." a left join ".GD_MEMBER." b on a.m_no=b.m_no",$where=array("goodsno='$goodsno'"),$sort="parent desc, ( case when parent=a.sno then 0 else 1 end ) asc,regdt desc");
$pg->exec();
$totcnt = $pg -> recode[total]; //전체 글수

$res = $db->query($pg->query);
while ($data=$db->fetch($res)){
	// @qnibus 2015-06 XSS 취약으로 인해 필터링 구문 추가
	if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
		$data = validation::xssCleanArray($data, array(
			validation::DEFAULT_KEY => 'text',
			'contents' => array('html', 'ent_noquotes'),
			'subject' => array('html', 'ent_noquotes'),
		));
	}

	$data['idx'] = $pg->idx--;

	$data[type] = ( $data[sno] == $data[parent] ? 'Q' : 'A' );
	$data[name] = $data[name] ? $data[name] : $data[m_name];

	$data[authmodify] = $data[authdelete] = $data[authreply] = 'Y'; # 권한초기값

	if ( empty($cfg['reviewWriteAuth']) || isset($sess) || !empty($data[m_no]) ){ // 회원전용 or 회원 or 작성자==회원
		$data[authmodify] = ( isset($sess) && $sess[m_no] == $data[m_no] ? 'Y' : 'N' );
		$data[authdelete] = ( isset($sess) && $sess[m_no] == $data[m_no] ? 'Y' : 'N' );
	}

	list( $data[replecnt] ) = $db->fetch("select count(*) from ".GD_GOODS_REVIEW." where sno != parent and parent='$data[sno]'");
	$data[authdelete] = ( $data[replecnt] > 0 ? 'N' : $data[authdelete] ); # 답글 있는 경우 삭제 불가

	if ( $data[sno] == $data[parent] ){

		if ( empty($cfg['reviewWriteAuth']) ){ // 회원전용
			$data[authreply] = ( isset($sess) ? 'Y' : 'N' );
		}
	}else $data[authreply] = 'N';

	list( $level ) = $db->fetch("select level from ".GD_MEMBER." where m_no!='' and m_no='{$data[m_no]}'");
	if ( $level == '100' && $adminicon ) $data[m_id] = $data[name] = "<img src='../../shop/data/skin/{$cfg['tplSkin'] }/{$adminicon}' border=0>";
	if ( empty($data[m_no]) ) $data[m_id] = $data[name]; // 비회원명

	$data[contents] = "<pre>".strip_tags(($data[contents]))."</pre>";
	$data[point] = sprintf( "%0d", $data[point]);

	$loop[] = $data;
}

$listingEnd = ($_GET['listingCnt']+count($loop) >= $pg->recode[total]) ? true:false;

$result = array(
	'list'				=> $loop,
	'listingCnt'		=> count($loop),
	'listingEnd'		=> $listingEnd
);

echo $json->encode($result);
?>