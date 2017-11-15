<?php
include "../_header.php";

chkMemberMobile();

if(gettype($socialMemberService) == 'object'){
	if ($socialMemberService->isEnabled() && SocialMemberService::getPersistentData('social_code')) {
		list($password) = $db->fetch("SELECT password FROM " . GD_MEMBER . " WHERE m_no = '" . $sess['m_no'] . "'");
		if (strlen($password) < 1) {
			msg('등록된 비밀번호가 없습니다. \n회원정보수정을 통해 비밀번호를 등록할 수 있습니다.', -1);
		}
	}
}

if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
	$_POST = validation::xssCleanArray($_POST, array(
	    validation::DEFAULT_KEY => 'text',
	));
}

//페이코 멤버 확인
list($connected_sns) = $db->fetch("SELECT connected_sns FROM ".GD_MEMBER." WHERE m_no = '".$sess['m_no']."'");
if ($socialMemberService->isEnabled() && !SocialMemberService::getPersistentData('social_code')) {
	if (strpos($connected_sns, 'PAYCO') > -1) {
		$socialMember = SocialMemberService::getMember('PAYCO');
		$paycoMember = $socialMemberService->getMember(SocialMemberService::PAYCO);

		$tpl->assign('PaycoSocialMemberHackURL', $paycoMember->getMobileHackURL());
	}
}

if (strpos($connected_sns, 'PAYCO') > -1) $tpl->assign('PaycoSocialMemberYn', 'Y');

if( $_POST['act'] == 'Y' && $sess && $_POST['hack_password'] ){
	if(!$_POST['hack_reason']){
		msg('탈퇴사유를 선택하여 주세요.', -1);
	}
	if(!$_POST['hack_password']){
		msg('비밀번호를 입력하여 주세요.', -1);
	}

	if (!$_SESSION['sess']['confirm_hack']) {
		list($cnt) = $db->fetch("
			SELECT COUNT(password) FROM 
				" . GD_MEMBER . " 
			WHERE 
				m_no = '" . $sess['m_no'] . "' and password in (password('".$_POST['hack_password']."'),old_password('".$_POST['hack_password']."'),'".md5($_POST['hack_password'])."')
		");
		if($cnt < 1){
			msg('비밀번호가 일치하지 않습니다', -1);			
		}
	} 

	// ace카운터
	if(gettype($Acecounter)!='object'){
		@include $shopRootDir . '/lib/acecounter.class.php';
		$Acecounter = new Acecounter();
	}
	$Acecounter->member_hack();
	if($Acecounter->scripts){
		echo $Acecounter->scripts;
	}

	//페이코 개인정보 제공 동의 철회
	if ($socialMemberService->isEnabled() && SocialMemberService::getPersistentData('social_code') == 'PAYCO') {
		$socialMember = SocialMemberService::getMember(SocialMemberService::getPersistentData('social_code'));
		if ($socialMember->isSameMember()) $socialMember->removeServiceOff();
	}
	
	// 탈퇴로그 저장
	list($dupeinfo) = $db->fetch("SELECT dupeinfo FROM " . GD_MEMBER . " WHERE m_no='" . $sess['m_no'] . "'");
	if($dupeinfo) $dupeinfoQuery = "dupeinfo	= '$dupeinfo',";
	$db->query(" 
		INSERT INTO  " . GD_LOG_HACK . " SET 
			m_id		= '" . $sess['m_id'] . "',
			name		= '" . $member['name'] . "',
			actor		= '1', 
			itemcd		= '" . @pow(2, $_POST['hack_reason']) . "',
			ip			= '" . $_SERVER['REMOTE_ADDR'] . "',
			$dupeinfoQuery
			regdt		= now()
	");
	
	// 데이타 삭제
	$db->query("DELETE FROM " . GD_MEMBER . " WHERE m_no='" . $sess['m_no'] . "'");
	$db->query("DELETE FROM " . GD_LOG_EMONEY . " WHERE m_no='" . $sess['m_no'] . "'");
	$db->query("DELETE FROM " . GD_SNS_MEMBER . " WHERE m_no='" . $sess['m_no'] . "'");

	//회원탈퇴메일
	if ($member['email'] && $cfg['mailyn_12'] == 'y'){
		$modeMail = 12;
		include $shopRootDir . '/lib/automail.class.php';
		$automail = new automail();
		$automail->_set($modeMail,$member[email],$cfg);
		$automail->_assign('name',$member[name]);
		$automail->_send();
	}

	msg( "정상적으로 회원탈퇴처리가 승인되었습니다. \\n\\n 그동안 이용해 주셔서 진심으로 감사합니다.", "../mem/logout.php" );
}

$guideSecede = getTermsGuideContents('guide', 'guideSecede');
$tpl->assign('guideSecede', $guideSecede);
$tpl->print_('tpl');
?>