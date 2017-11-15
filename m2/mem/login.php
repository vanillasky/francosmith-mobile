<?
/*********************************************************
* 파일명     :  /mem/login.php
* 프로그램명 :	모바일샵 로그인
* 작성자     :  dn
* 생성일     :  2012.07.10
**********************************************************/	
include "../_header.php";

### 회원인증여부
if( $sess ){
	go("../index.php");
}

if (!$_GET['returnUrl']) $returnUrl = $_SERVER['HTTP_REFERER'];
else $returnUrl = $_GET['returnUrl'];

if(!$returnUrl) $returnUrl = $mobileRootDir;

//소셜로그인
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