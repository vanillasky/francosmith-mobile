<?
include dirname(__FILE__) . "/../_header.php";
@include $shopRootDir . "/conf/config.mobileShop.main.php";

if ( $cfg['introUseYNMobile'] != 'Y' ){ // ��Ʈ�� �̻��
	header("location:index.php");
}

$tpl->print_('tpl');
?>