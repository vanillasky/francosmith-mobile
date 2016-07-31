<?
include dirname(__FILE__) . "/../_header.php";
include $shopRootDir . "/conf/fieldset.php";
@include $shopRootDir . "/conf/config.mobileShop.main.php";

### 회원인증여부
if( $sess ){
	msg("고객님은 로그인 중입니다.", -1 );
}

$returnUrl = $mobileRootDir;

$tpl->assign($_POST);
$tpl->assign('realnameyn', (empty($realname[id]) ? 'n' : empty($realname[useyn])? 'n': $realname[useyn] ));
$tpl->assign('ipinyn', (empty($ipin[id]) ? 'n' : empty($ipin[useyn])? 'n': $ipin[useyn]));
$tpl->assign('shopName', $cfg['shopName']);

$tpl->print_('tpl');
?>