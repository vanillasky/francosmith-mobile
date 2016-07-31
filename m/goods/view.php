<?php
	include dirname(__FILE__) . "/../_header.php";
	@include $shopRootDir . "/conf/config.pay.php";
	@include $shopRootDir . "/conf/sns.cfg.php";
	@include $shopRootDir . "/conf/coupon.php";
	@include $shopRootDir . '/lib/Lib_Robot.php';

if(!$set['emoney']['cut'])$set['emoney']['cut']=0;
$set['emoney']['base'] = pow(10,$set['emoney']['cut']);

try {

	$goodsHelper   = Clib_Application::getHelperClass('front_goods');

	$goodsModel    = $goodsHelper->getGoodsModel(Clib_Application::request()->get('goodsno'));
	if (!$goodsModel->hasLoaded()) {
		throw new Clib_Exception('상품정보가 없습니다.');
	}
	else if (!$goodsModel->getOpenMobile()) {
		throw new Clib_Exception('해당상품은 진열이 허용된 상품이 아닙니다.');
	}

	$categoryModel = $goodsHelper->getCategoryModel(Clib_Application::request()->get('category'), $goodsModel);

	// 성인 인증이 필요한 상품일 경우
	// 모바일 에서는 인증 수단이 없으므로, 카테고리 페이지로 리디렉션
	if ($goodsModel->getUseOnlyAdult() && !Clib_Application::session()->canAccessAdult()) {
		Clib_Application::response()->redirect(
			$goodsHelper->getGoodsViewUrlMobile($goodsModel)
		);
	}

	$goodsno = $goodsModel->getId();

	// 카테고리 정보 설정, 접근 권한 체크
	if (!$goodsHelper->canAccessLinkedCategory($goodsModel)) {
		throw new Clib_Exception('이용 권한이 없습니다.\\n회원등급이 낮거나 회원가입이 필요합니다.');
	}

	// 페이지뷰 카운팅
	if (Lib_Robot::isRobotAccess() === false) {
		$db->silent(true);
		$db->query("INSERT INTO ".GD_GOODS_PAGEVIEW." SET date = CURDATE(), goodsno = $goodsno, cnt = 1 ON DUPLICATE KEY UPDATE cnt = cnt + 1");
		$db->silent();
	}

	$data = $goodsHelper->getGoodsDataArray($goodsModel, $categoryModel);

	$tpl->assign(array('clevel'	=> $categoryModel->getLevel(),
					   'slevel'=> Clib_Application::session()->getMemberLevel(),
					   'level_auth'=> $categoryModel->getLevelAuth()));

	Clib_Application::storage()->toGlobal();

	### 템플릿 출력
	$tpl->assign($data);
	$tpl->print_('tpl');

}
catch (Clib_Exception $e) {
	Clib_Application::response()->jsAlert($e)->historyBack();
}
