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

	// ī�װ�
	$categoryModel = $goodsHelper->getCategoryModel(Clib_Application::request()->get('category'));
	if (!$categoryModel->hasLoaded()) {
		throw new Clib_Exception('�з��������� ī�װ��� �������� �ʾҽ��ϴ�.');
	}

	// ���� üũ
	if (!$categoryModel->checkPermission(Clib_Application::session()->getMemberLevel())) {
		throw new Clib_Exception('�̿� ������ �����ϴ�.\\nȸ������� ���ų� ȸ�������� �ʿ��մϴ�.');
	}

	// ī�װ� ���� ��� ���� üũ
	$vtypeMobile = ($cfgMobileShop['vtype_category'] == 1 ? 'mobile' : '');
	if (!Clib_Application::session()->isAdmin() && getCateHideCnt($categoryModel->getId(), $vtypeMobile) > 0) {
		throw new Clib_Exception('�ش�з��� ������ ���� �з��� �ƴմϴ�.');
	}

	// ������ ����
	$page_title = $categoryModel->getCatnm();

	// ī�װ� ��ǰ ��� ����
	$lstcfg = $categoryModel->getConfig();

	// ���� ī�װ� ����
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

	// �Ķ���� ����
	// Ű���� �˻��� ��� category�� ������ ����
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

	// ��ǰ ���
	$goodsCollection = $goodsHelper->getGoodsCollection($params);

	if (Clib_Application::request()->get('kw')) {
		$page_title = "<span class=\"sky_hilight\">'" . Clib_Application::request()->get('kw') . "'</span> �� �˻����";
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
