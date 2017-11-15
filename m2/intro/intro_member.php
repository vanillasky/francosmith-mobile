<?
include dirname(__FILE__) . "/../_header.php";
include $shopRootDir . "/conf/fieldset.php";
@include $shopRootDir . "/conf/config.mobileShop.main.php";

### 회원인증여부
if( $sess ){
	msg("고객님은 로그인 중입니다.", -1 );
}

if (!$_GET['returnUrl']) $returnUrl = $mobileRootDir; // @todo $_GET['returnUrl'] 적용
else $returnUrl = $_GET['returnUrl'];

$loginActionUrl = "../mem/login_ok.php";

//소셜로그인
$socialMemberServiceList = $socialMemberService->getEnabledServiceList();
if (in_array(SocialMemberService::FACEBOOK, $socialMemberServiceList)) {
	$facebookService = SocialMemberService::getMember(SocialMemberService::FACEBOOK);
	$tpl->assign('FacebookLoginURL', $facebookService->getMobileLoginURL($returnUrl));
}
if (in_array(SocialMemberService::PAYCO, $socialMemberServiceList)) {
	$paycoService = SocialMemberService::getMember(SocialMemberService::PAYCO);
	$tpl->assign('PaycoLoginURL', $paycoService->getMobileLoginURL($returnUrl));
}

$tpl->assign($_POST);
$tpl->assign('realnameyn', (empty($realname[id]) ? 'n' : empty($realname[useyn])? 'n': $realname[useyn] ));
$tpl->assign('ipinyn', (empty($ipin[id]) ? 'n' : empty($ipin[useyn])? 'n': $ipin[useyn]));
$tpl->assign('shopName', $cfg['shopName']);

$tpl->print_('tpl');
?>