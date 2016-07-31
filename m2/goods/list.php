<? /*********************************************************
 * 파일명     :  list.php
 * 프로그램명 :	상품리스트 페이지
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

	$goodsHelper   = Clib_Application::getHelperClass('front_goods_mobile');

	// 카테고리
	$categoryModel = $goodsHelper->getCategoryModel(Clib_Application::request()->get('category'));
	if (!$categoryModel->hasLoaded()) {
		throw new Clib_Exception('분류페이지에 카테고리가 지정되지 않았습니다.');
	}

	// 권한 체크
	if (!$categoryModel->checkPermission(Clib_Application::session()->getMemberLevel())) {
		throw new Clib_Exception('이용 권한이 없습니다.\\n회원등급이 낮거나 회원가입이 필요합니다.');
	}

	// 카테고리 진열 허용 여부 체크
	$vtypeMobile = ($cfgMobileShop['vtype_category'] == 1 ? 'mobile' : '');
	if (!Clib_Application::session()->isAdmin() && getCateHideCnt($categoryModel->getId(), $vtypeMobile) > 0) {
		throw new Clib_Exception('해당분류는 진열이 허용된 분류가 아닙니다.');
	}

	// 페이지 제목
	$page_title = $categoryModel->getCatnm();

	// 카테고리 상품 목록 설정
	$lstcfg = $categoryModel->getConfig();

	// template_ 에서 global 변수로 사용하기 때문에 설정 함.
	$_GET['category'] = $category = $categoryModel->getId();

	// 상품분류 연결방식 전환 여부에 따른 처리
	$whereArr	= getCategoryLinkQuery('CMGL0.category', Clib_Application::request()->get('category'));

	// 카테고리 총 상품개수 for paging
	$query = " SELECT ";
	$query.= " COUNT(".$whereArr['distinct']." CMGG0.goodsno) AS __CNT__ ";
	$query.= " FROM ".GD_GOODS." AS CMGG0 ";
	$query.= " INNER JOIN ".GD_GOODS_LINK." AS CMGL0 ON CMGG0.goodsno = CMGL0.goodsno ";
	$query.= " WHERE  (CMGL0.hidden = '0') ";
	$query.= " and ".$whereArr['where'];
	if ($cfgMobileShop['vtype_goods'] == 1) {
		$query.= " and (CMGG0.open_mobile = '1') ";
	} else {
		$query.= " and (CMGG0.open = '1') ";
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

	$tpl->assign(array(
		'page_title' => $page_title,
		'category' => $categoryModel->getId(),
		'kw' => Clib_Application::request()->get('kw'),
		'goods_total' => $totalCount[0],
	));

	// 네이버 마일리지
	$naverNcash = Core::loader('naverNcash');
	if ($naverNcash->canUseMobile() === false) $naverNcash->useyn = "N";
	if ($naverNcash->useyn == 'Y' && $naverNcash->baseAccumRate) {
		$naverMileageAccumrateList = array();
		foreach ($cart->item as $key => $item) {
			$exceptionYN = $naverNcash->exception_goods(array(array('goodsno' => $item['goodsno'])));
			if ($exceptionYN == 'N') {
				$cart->item[$key]['NaverMileageAccum'] = true;
				$naverMileageAccumrateList[$item['goodsno']]['NaverMileageAccum'] = true;
			}
		}
		$tpl->assign('NaverMileageAccum', include dirname(__FILE__).'/../../shop/proc/naver_mileage/goods_accum_rate_type_3.php');
	}
	$tpl->print_('tpl');

} catch (Clib_Exception $e) {
	Clib_Application::response()->jsAlert($e)->historyBack();
}
