<?
include "../_header.php";

$data = array();

$serialize_goods_data = $_COOKIE['todayGoodsMobile'];
$goods_arr = unserialize(stripslashes($serialize_goods_data));

if(!empty($goods_arr) && is_array($goods_arr)) {
	$idx = 0;
	foreach($goods_arr as $goods_row) {
		if($idx > 8) break;
		$query = " select use_only_adult from ".GD_GOODS." where goodsno='".$goods_row['goodsno']."' ";
		$res = $db->query($query);
		$row = $db->fetch($res,1);
		if($row['use_only_adult'] == '1' && !Clib_Application::session()->canAccessAdult()){
			if($GLOBALS['cfgMobileShop']['mobileShopRootDir'] == "/m2"){
				$skin_folder = "/skin_mobileV2";
			} else {
				$skin_folder = "/skin_mobile";
			}
			$goods_row['img'] = 'http://' . $_SERVER['HTTP_HOST'] . $GLOBALS['cfg']['rootDir'] . "/data" . $skin_folder . "/" . $GLOBALS['cfg']['tplSkinMobile'] . '/common/img/19.gif';
		}
		$goods_data[] = $goods_row;
		$idx ++;
	}
}

//페이코
if(is_file($shopRootDir . '/lib/payco.class.php')){
	$Payco = Core::loader('payco')->getButtonHtmlCode('CHECKOUT', true, 'goodsView');
	if($Payco) $tpl->assign('Payco', $Payco);
}

### 템플릿 출력
$tpl->assign('goods_data', $goods_data);
$tpl->print_('tpl');
?>