<?
/*********************************************************
* ���ϸ�     :  /mem/login.php
* ���α׷��� :	����ϼ� �α���
* �ۼ���     :  dn
* ������     :  2012.07.10
**********************************************************/	
include "../_header.php";

### ȸ����������
if( $sess ){
	go("../index.php");
}

if (!$_GET['returnUrl']) $returnUrl = $_SERVER['HTTP_REFERER'];
else $returnUrl = $_GET['returnUrl'];

if(!$returnUrl) $returnUrl = $mobileRootDir;

//�Ҽȷα���
$socialMemberServiceList = $socialMemberService->getEnabledServiceList();
if (in_array(SocialMemberService::FACEBOOK, $socialMemberServiceList)) {
	$facebookService = SocialMemberService::getMember(SocialMemberService::FACEBOOK);
	$tpl->assign('csslight','light');
	$tpl->assign('FacebookLoginURL', $facebookService->getMobileLoginURL($returnUrl));
}
if (in_array(SocialMemberService::PAYCO, $socialMemberServiceList)) {
	$paycoService = SocialMemberService::getMember(SocialMemberService::PAYCO);
	$tpl->assign('csslight','light');
	$tpl->assign('PaycoLoginURL', $paycoService->getMobileLoginURL($returnUrl));
}

$tpl->print_('tpl');

?>