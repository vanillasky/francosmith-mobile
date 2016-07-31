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
		throw new Clib_Exception('��ǰ������ �����ϴ�.');
	}
	else if (!$goodsModel->getOpenMobile()) {
		throw new Clib_Exception('�ش��ǰ�� ������ ���� ��ǰ�� �ƴմϴ�.');
	}

	$categoryModel = $goodsHelper->getCategoryModel(Clib_Application::request()->get('category'), $goodsModel);

	// ���� ������ �ʿ��� ��ǰ�� ���
	// ����� ������ ���� ������ �����Ƿ�, ī�װ� �������� ���𷺼�
	if ($goodsModel->getUseOnlyAdult() && !Clib_Application::session()->canAccessAdult()) {
		Clib_Application::response()->redirect(
			$goodsHelper->getGoodsViewUrlMobile($goodsModel)
		);
	}

	$goodsno = $goodsModel->getId();

	// ī�װ� ���� ����, ���� ���� üũ
	if (!$goodsHelper->canAccessLinkedCategory($goodsModel)) {
		throw new Clib_Exception('�̿� ������ �����ϴ�.\\nȸ������� ���ų� ȸ�������� �ʿ��մϴ�.');
	}

	// �������� ī����
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

	### ���ø� ���
	$tpl->assign($data);
	$tpl->print_('tpl');

}
catch (Clib_Exception $e) {
	Clib_Application::response()->jsAlert($e)->historyBack();
}
