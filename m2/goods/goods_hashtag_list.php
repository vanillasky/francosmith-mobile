<?php
include dirname(__FILE__) . '/../_header.php';
$soldoutConfigPage = SHOPROOT.'/conf/config.soldout.php';
$snsConfigPage = SHOPROOT.'/conf/sns.cfg.php';

if(is_file($soldoutConfigPage)) include $soldoutConfigPage;
if(is_file($snsConfigPage)) include $snsConfigPage;

if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
	$_GET = validation::xssCleanArray($_GET, array(
		validation::DEFAULT_KEY	=> 'text'
	));
}

try {
	if(is_file(SHOPROOT.'/lib/hashtag.class.php')){
		$hashtagObj = Core::loader('hashtag');
	}

	$hashtag = Clib_Application::request()->get('hashtag');

	$goodsHelper   = Clib_Application::getHelperClass('front_goods_mobile');

	// 페이지 제목
	$hashtagPageTitle = "#".$hashtag;

	$query = "
		SELECT
			COUNT(*) AS __CNT__
		FROM
			".GD_GOODS." AS CMGG0
		INNER JOIN
			".GD_HASHTAG." AS CMGL0
		ON
			CMGG0.goodsno = CMGL0.goodsno AND CMGL0.hashtag='".$hashtag."'
	";
	if ($cfgMobileShop['vtype_goods'] == 1) {
		$query.= " WHERE (CMGG0.open_mobile = '1') ";
	} else {
		$query.= " WHERE (CMGG0.open = '1') ";
	}
	// 품절 상품 제외
	if ($cfg_soldout['exclude_category']) {
		$query.= "and !( CMGG0.runout = 1 OR (CMGG0.usestock = 'o' AND CMGG0.usestock IS NOT NULL AND CMGG0.totstock < 1) ) ";
	}

	//DB Cache 사용 141030
	$dbCache = Core::loader('dbcache')->setLocation('goodslist');

	if (!$out = $dbCache->getCache($query)) {
  		$totalCount = $db->fetch($query); // 전체 레코드
		if ($totalCount && $dbCache) $dbCache->setCache($query, $totalCount);
  	} else {
  		$totalCount = $out;
  	}

	// GET변수에 넘겨줄 view_type 할당
	if (!$_GET['view_type']) $_GET['view_type'] = $_COOKIE['goods_view_type'];

	// 검색어 있는 경우 타이틀 재설정 및 상품상세주소 설정
	if (Clib_Application::request()->get('kw')) {
		$goodsDisplay = Core::loader('Mobile2GoodsDisplay');
		$goods_data = $goodsDisplay->getMobileCategoryDisplayGoods($_GET);
		$page_title = "<span class=\"sky_hilight\">'" . Clib_Application::request()->get('kw') . "'</span> 의 검색결과";
		$page_title .= "(" . $goods_data['total'] . ")";
		$totalCount[0] = $goods_data['total'];
	}


	//SNS
	if(is_object($hashtagObj)){
		list($snsBtn, $msgKakao, $msg_kakaoStory) = $hashtagObj->getMobileSnsBtn();

		//kakaoTalk Link 3.5
		if($msgKakao['kakaoTalkLinkScript']){
			$customHeader .= $msgKakao['kakaoTalkLinkScript'];
		}

		$tpl->assign(array(
			'customHeader' => $customHeader,
			'snsBtn' => $snsBtn,
			'msg_kakaoStory_goodsurl' => $msg_kakaoStory['msg_kakaoStory_goodsurl'],
			'msg_kakaoStory_shopnm' => $msg_kakaoStory['msg_kakaoStory_shopnm'],
			'msg_kakaoStory_goodsnm' => $msg_kakaoStory['msg_kakaoStory_goodsnm'],
			'msg_kakaoStory_img_l' => $msg_kakaoStory['msg_kakaoStory_img_l'],
		));
	}

	$tpl->assign(array(
		'page_title' => $page_title,
		'hashtagPageTitle' => $hashtagPageTitle,
		'hashtag' => $hashtag,
		'kw' => Clib_Application::request()->get('kw'),
		'goods_total' => $totalCount[0],
	));

	$tpl->print_('tpl');

} catch (Clib_Exception $e) {
	Clib_Application::response()->jsAlert($e)->historyBack();
}
?>