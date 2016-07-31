<?

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

	// 서브 카테고리 갯수
	$cntSubCategory = $categoryModel->getSubCategoryCount();
	$category = $categoryModel->getId();

	$sort = Clib_Application::request()->get('sort');

	switch($sort) {
		case 'c.price' :
			$goods_sort = 'goods.goods_price';
			break;
		case 'c.price desc' :
			$goods_sort = 'goods.goods_price desc';
			break;
		case 'c.reserve desc' :
			$goods_sort = 'goods.goods_reserve desc';
			break;
		case 'b.goodsnm' :
			$goods_sort = 'goods.goodsnm';
			break;
		case 'b.maker' :
			$goods_sort = 'goods.maker';
			break;
		case 'c.regdt desc' :
		default :
			$goods_sort = 'goods.regdt desc';
			break;
	}

	// 파라미터 설정
	// 키워드 검색일 경우 category는 보내지 않음
	if(Clib_Application::request()->get('kw')) {
		$params = array(
			'page' => Clib_Application::request()->get('page', 1),
			'page_num' => Clib_Application::request()->get('page_num', 6),
			'keyword' => Clib_Application::request()->get('kw'),
			'sort' => $goods_sort,
		);
	}
	else {
		$params = array(
			'page' => Clib_Application::request()->get('page', 1),
			'page_num' => Clib_Application::request()->get('page_num', 6),
			'keyword' => Clib_Application::request()->get('kw'),
			'sort' => $goods_sort,
			'category' => $categoryModel->getId(),
		);
	}

	// 상품 목록
	$goodsCollection = $goodsHelper->getGoodsCollection($params);

	if (Clib_Application::request()->get('kw')) {
		$page_title = "<span class=\"sky_hilight\">'" . Clib_Application::request()->get('kw') . "'</span> 의 검색결과";
	}

	$pg = $goodsCollection->getPaging();
	$page_title .= "(" . $pg->recode['total'] . ")";

	$tpl->assign(array(
		'page_title' => $page_title,
		'category' => $categoryModel->getId(),
		'loopM'	=> $goodsHelper->getGoodsCollectionArray($goodsCollection, $categoryModel),
	));
	$tpl->print_('tpl');

} catch (Clib_Exception $e) {
	Clib_Application::response()->jsAlert($e)->historyBack();
}
