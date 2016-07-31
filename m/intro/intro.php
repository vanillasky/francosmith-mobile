<?
include dirname(__FILE__) . "/../_header.php";
@include $shopRootDir . "/conf/config.mobileShop.main.php";

if ( $cfg['introUseYNMobile'] != 'Y' ){ // 인트로 미사용
	header("location:index.php");
}

$tpl->print_('tpl');
?>