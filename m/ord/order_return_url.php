<?
@include dirname(__FILE__) . "/../lib/library.php";
@include $shopRootDir . "/conf/config.mobileShop.php";

$ordno = $_GET['ordno'];
$query = "select step from ".GD_ORDER." where ordno=".$ordno;
list($step) = $db->fetch($query);

if($step==1){
	go($cfgMobileShop['mobileShopRootDir']."/ord/order_end.php?ordno=".$ordno,"parent");
}
else{
	go($cfgMobileShop['mobileShopRootDir']."/ord/order_fail.php?ordno=".$ordno,"parent");
}
?>