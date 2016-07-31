<? /*********************************************************
 * ���ϸ�     :  list.php
 * ���α׷��� :	��ǰ����Ʈ ������
 * �ۼ���     :  dn
 * ������     :  2012.05.31
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

	// template_ ���� global ������ ����ϱ� ������ ���� ��.
	$_GET['category'] = $category = $categoryModel->getId();

	// ��ǰ�з� ������ ��ȯ ���ο� ���� ó��
	$whereArr	= getCategoryLinkQuery('CMGL0.category', Clib_Application::request()->get('category'));

	// ī�װ� �� ��ǰ���� for paging
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

	// ǰ�� ��ǰ ����
	if ($cfg_soldout['exclude_category']) {
		$query.= "and !( CMGG0.runout = 1 OR (CMGG0.usestock = 'o' AND CMGG0.usestock IS NOT NULL AND CMGG0.totstock < 1) ) ";
	}

	//DB Cache ��� 141030
	$dbCache = Core::loader('dbcache')->setLocation('goodslist');

	if (!$out = $dbCache->getCache($query)) {
  		$totalCount = $db->fetch($query); // ��ü ���ڵ�
		if ($totalCount && $dbCache) $dbCache->setCache($query, $totalCount);
  	} else {
  		$totalCount = $out;
  	}

	// GET������ �Ѱ��� view_type �Ҵ�
	if (!$_GET['view_type']) $_GET['view_type'] = $_COOKIE['goods_view_type'];

	// �˻��� �ִ� ��� Ÿ��Ʋ �缳�� �� ��ǰ���ּ� ����
	if (Clib_Application::request()->get('kw')) {
		$goodsDisplay = Core::loader('Mobile2GoodsDisplay');
		$goods_data = $goodsDisplay->getMobileCategoryDisplayGoods($_GET);
		$page_title = "<span class=\"sky_hilight\">'" . Clib_Application::request()->get('kw') . "'</span> �� �˻����";
		$page_title .= "(" . $goods_data['total'] . ")";
		$totalCount[0] = $goods_data['total'];
	}

	$tpl->assign(array(
		'page_title' => $page_title,
		'category' => $categoryModel->getId(),
		'kw' => Clib_Application::request()->get('kw'),
		'goods_total' => $totalCount[0],
	));

	// ���̹� ���ϸ���
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
