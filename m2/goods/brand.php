<?
/*********************************************************
 * 파일명     :  brand.php
 * 프로그램명 :	브랜드 페이지
 * 작성자     :  dn
 * 생성일     :  2012.05.31
 **********************************************************/

include dirname(__FILE__) . "/../_header.php";
@include dirname(__FILE__). "/../../shop/conf/config.soldout.php";

if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
	$_GET = validation::xssCleanArray($_GET, array(
		validation::DEFAULT_KEY	=> 'text'
	));
}

try {

	$goodsHelper = Clib_Application::getHelperClass('front_goods');

	//
	$brandModel = $goodsHelper->getBrandModel(Clib_Application::request()->get('brand'));

	### 환경변수 호출, 브랜드 명 assign.
	$lstcfg = $brandModel->getConfig();
	$page_title = $brandModel->getBrandnm();

	if ($_POST['kw']) { // 자바스크립트에서 UTF-8로 한글이 넘어와 반드시 EUC-KR로 변환해야 함
		$_POST['kw'] = iconv('UTF-8', 'EUC-KR', Clib_Application::request()->get('kw'));
	}

	// 브랜드 총 상품개수 for paging
	$query = " SELECT ";
	$query.= " COUNT(".$whereArr['distinct']." CMGG0.goodsno) AS __CNT__ ";
	$query.= " FROM ".GD_GOODS." AS CMGG0 ";
	$query.= " INNER JOIN ".GD_GOODS_BRAND." AS CMGL0 ON CMGG0.brandno = CMGL0.sno ";
	$query.= " and (CMGL0.sno = '".Clib_Application::request()->get('brand')."')";
	if ($cfgMobileShop['vtype_goods'] == 1) {
		$query.= " and (CMGG0.open_mobile = '1') ";
	} else {
		$query.= " and (CMGG0.open = '1') ";
	}

	// 품절 상품 제외
	if ($cfg_soldout['exclude_brand']) {
		$query.= "and !( CMGG0.runout = 1 OR (CMGG0.usestock = 'o' AND CMGG0.usestock IS NOT NULL AND CMGG0.totstock < 1) ) ";
	}

	//DB Cache 사용 141030
	$dbCache = Core::loader('dbcache')->setLocation('brandlist');

	if (!$out = $dbCache->getCache($query)) {
		$totalCount = $db->fetch($query); // 전체 레코드
		if ($totalCount && $dbCache) $dbCache->setCache($query, $totalCount);
	} else {
		$totalCount = $out;
	}
	
	// 브랜드 리스트
	$child_query = $db->_query_print('SELECT sno, brandnm FROM '.GD_GOODS_BRAND.' ORDER BY sort');
	$brand_list = $db->_select($child_query);

	// 템플릿 할당
	$tpl->assign(array(
		'page_title' => $page_title,
		'brand' => $brandModel->getId(),
		'goods_total' => $totalCount['__CNT__'],
	));

	$tpl->print_('tpl');

} catch (Clib_Exception $e) {
	Clib_Application::response()->jsAlert($e)->historyBack();
}
