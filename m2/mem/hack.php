<?php
include "../_header.php";

chkMemberMobile();

if(gettype($socialMemberService) == 'object'){
	if ($socialMemberService->isEnabled() && SocialMemberService::getPersistentData('social_code')) {
		list($password) = $db->fetch("SELECT password FROM " . GD_MEMBER . " WHERE m_no = '" . $sess['m_no'] . "'");
		if (strlen($password) < 1) {
			msg('��ϵ� ��й�ȣ�� �����ϴ�. \nȸ������������ ���� ��й�ȣ�� ����� �� �ֽ��ϴ�.', -1);
		}
	}
}

if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
	$_POST = validation::xssCleanArray($_POST, array(
	    validation::DEFAULT_KEY => 'text',
	));
}

//������ ��� Ȯ��
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
		msg('Ż������� �����Ͽ� �ּ���.', -1);
	}
	if(!$_POST['hack_password']){
		msg('��й�ȣ�� �Է��Ͽ� �ּ���.', -1);
	}

	if (!$_SESSION['sess']['confirm_hack']) {
		list($cnt) = $db->fetch("
			SELECT COUNT(password) FROM 
				" . GD_MEMBER . " 
			WHERE 
				m_no = '" . $sess['m_no'] . "' and password in (password('".$_POST['hack_password']."'),old_password('".$_POST['hack_password']."'),'".md5($_POST['hack_password'])."')
		");
		if($cnt < 1){
			msg('��й�ȣ�� ��ġ���� �ʽ��ϴ�', -1);			
		}
	} 

	// aceī����
	if(gettype($Acecounter)!='object'){
		@include $shopRootDir . '/lib/acecounter.class.php';
		$Acecounter = new Acecounter();
	}
	$Acecounter->member_hack();
	if($Acecounter->scripts){
		echo $Acecounter->scripts;
	}

	//������ �������� ���� ���� öȸ
	if ($socialMemberService->isEnabled() && SocialMemberService::getPersistentData('social_code') == 'PAYCO') {
		$socialMember = SocialMemberService::getMember(SocialMemberService::getPersistentData('social_code'));
		if ($socialMember->isSameMember()) $socialMember->removeServiceOff();
	}
	
	// Ż��α� ����
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
	
	// ����Ÿ ����
	$db->query("DELETE FROM " . GD_MEMBER . " WHERE m_no='" . $sess['m_no'] . "'");
	$db->query("DELETE FROM " . GD_LOG_EMONEY . " WHERE m_no='" . $sess['m_no'] . "'");
	$db->query("DELETE FROM " . GD_SNS_MEMBER . " WHERE m_no='" . $sess['m_no'] . "'");

	//ȸ��Ż�����
	if ($member['email'] && $cfg['mailyn_12'] == 'y'){
		$modeMail = 12;
		include $shopRootDir . '/lib/automail.class.php';
		$automail = new automail();
		$automail->_set($modeMail,$member[email],$cfg);
		$automail->_assign('name',$member[name]);
		$automail->_send();
	}

	msg( "���������� ȸ��Ż��ó���� ���εǾ����ϴ�. \\n\\n �׵��� �̿��� �ּż� �������� �����մϴ�.", "../mem/logout.php" );
}

$guideSecede = getTermsGuideContents('guide', 'guideSecede');
$tpl->assign('guideSecede', $guideSecede);
$tpl->print_('tpl');
?>