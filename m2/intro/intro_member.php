<?
include dirname(__FILE__) . "/../_header.php";
include $shopRootDir . "/conf/fieldset.php";
@include $shopRootDir . "/conf/config.mobileShop.main.php";

### ȸ����������
if( $sess ){
	msg("������ �α��� ���Դϴ�.", -1 );
}

if (!$_GET['returnUrl']) $returnUrl = $mobileRootDir; // @todo $_GET['returnUrl'] ����
else $returnUrl = $_GET['returnUrl'];

$loginActionUrl = "../mem/login_ok.php";

//�Ҽȷα���
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