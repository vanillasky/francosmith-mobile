<?php

@include dirname(__FILE__) . "/../lib/library.php";
@include $shopRootDir . "/lib/page.class.php";
@include $shopRootDir . "/lib/json.class.php";
@include $shopRootDir . "/conf/config.pay.php";
@include dirname(__FILE__). "/../../shop/conf/config.soldout.php";

$json = new Services_JSON();


try {

	$goodsHelper   = Clib_Application::getHelperClass('front_goods_mobile');

	// ī�װ�
	$categoryModel = $goodsHelper->getCategoryModel(Clib_Application::request()->get('category'));

	// �Ķ���� ����
	// Ű���尡 ���� ��쿡�� category�� ������ ����

	$goods_sort = Clib_Application::request()->get('listSort');

	if(!$goods_sort) {
		$goods_sort = 'goods.regdt desc';
	}

	if(Clib_Application::request()->get('kw')) {
		$params = array(
			'page' => Clib_Application::request()->get('page', 1) + 1,
			'page_num' => Clib_Application::request()->get('page_num', 6),
			'keyword' => iconv('utf-8','euc-kr',Clib_Application::request()->get('kw')),
			'sort' => $goods_sort,
		);
	}
	else {
		$params = array(
			'page' => Clib_Application::request()->get('page', 1) + 1,
			'page_num' => Clib_Application::request()->get('page_num', 6),
			'keyword' => iconv('utf-8','euc-kr',Clib_Application::request()->get('kw')),
			'sort' => $goods_sort,
			'category' => $categoryModel->getId(),
		);
	}

	if (!$params['sort']) $params['sort'] = Clib_Application::request()->get('listSort');

	// ��ǰ ���
	$goodsCollection = $goodsHelper->getGoodsCollection($params);

	$loop = $goodsHelper->getGoodsCollectionArray($goodsCollection, $categoryModel);
	$pg = $goodsCollection->getPaging();

	$listingEnd = (Clib_Application::request()->get('listingCnt') + count($loop) >= $pg->recode['total']) ? true : false;

	$result = array(
		'success'			=> true,
		'list'				=> $loop,
		'listingCnt'		=> count($loop),
		'listingEnd'		=> $listingEnd,
		'category'			=> $categoryModel->getId(),
		'mobileShopRootDir' => $cfgMobileShop['mobileShopRootDir'],
	);

	echo $json->encode($result);

} catch (Clib_Exception $e) {

}
