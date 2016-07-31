<?php
include("../lib/library.php");

if (get_magic_quotes_gpc()) {
	stripslashes_all($_POST);
	stripslashes_all($_GET);
}

if ($_POST['mode']=="guest"){ // ��ȸ�� �ֹ���Ϻ���
	$ordno = (string)$_POST['ordno'];
	$nameOrder = (string)$_POST['ord_name'];

	// ���� ��ȿ�� ����
	$validation_check = array(
		'ordno'=>array('require'=>true,'pattern'=>'/^[0-9]+$/'),
		'nameOrder'=>array('require'=>true),
	);
	$chk_result = array_value_cheking($validation_check,array('ordno'=>$ordno,'nameOrder'=>$nameOrder));
	if(count($chk_result)) {
		msg("�ֹ��ڸ�� �ֹ���ȣ�� �������� �ʽ��ϴ�",-1);
	}

	// �ֹ���ȣ�� �ֹ��ڸ����� ��ȸ
	$query = $db->_query_print("select ordno from gd_order where ordno=[s] and nameOrder=[s]",$ordno,$nameOrder);
	$result = $db->_select($query);
	if($result[0]['ordno']) {
		setcookie("guest_ordno",$ordno,0,'/');
		setcookie("guest_nameOrder",$nameOrder,0,'/');
		go('../myp/orderlist.php');
	}
	else {
		msg("�ֹ��ڸ�� �ֹ���ȣ�� ��ġ�ϴ� �ֹ��� �������� �ʽ��ϴ�",-1);
	}
	exit;
}
else if ($_POST['mode']=="adult_guest") {

	include "../conf/fieldset.php";

	if ( $realname[useyn] == 'y' && !empty($realname[id]) ){

		// ���� ó�� �� ������ �̵��� �Ʒ� ���Ͽ��� ó�� ��.
		require_once( "./realname/RNCheckRequest.php" );
		exit;
	}
	else {
		msg("�������� ���񽺸� ����ϰ� ���� �ʽ��ϴ�.",-1);
	}
}
else { // ȸ�� �α��� �κ�
	if ($_GET['SOCIAL_CODE']) {
		include dirname(__FILE__).'/../../shop/lib/SocialMember/SocialMemberServiceLoader.php';
		$socialMember = SocialMemberService::getMember($_GET['SOCIAL_CODE']);
		$result = $socialMember->login();
		$_POST['returnUrl'] = $_GET['returnUrl'];
	}
	else {
		$m_id = (string)$_POST['m_id'];
		$password = (string)$_POST['password'];
		$saveLoginStatus = ($_POST['save_login_status'] == 'y' ? true : false);
		$result = $session->login($m_id, $password);
	}

	if($result!==true) {
		if($result==='NOT_FOUND') {
			msg('���̵� �Ǵ� ��й�ȣ �����Դϴ�',-1);
		}
		elseif($result==='NOT_ACCESS') {
			msg('������ �� ����Ʈ���� ���ε��� �ʾ� �α����� ���ѵ˴ϴ�.',-1);
		}
		if($result==='NOT_VALID') {
			msg('���̵� �Ǵ� ��й�ȣ �Է� ���� �����Դϴ�',-1);
		}
		exit;
	}

	//�⼮üũ���� ó��
	$attendance = Core::loader('attendance');
	$attendanceNo = $attendance->login_check($session->m_no, true);
	if ($attendanceNo) {
		$_SESSION['attendance'] = $attendanceNo;
	}

	// �ڵ��α���
	if ($saveLoginStatus === true) {
		// �������� ��� �ڵ��α��� ����
		if ($session->level >= 80) {
			expireCookieMemberInfo();
			msg('�ڵ��α��� ����� �����ڷ� �α��ν� ���Ȼ��� ������ ����� �� �����ϴ�.');
		}
		else {
			// ȸ���� ��쿡�� �ڵ��α��� ó��
			storeCookieMemberInfo($m_id, $password);
		}
	}


	### aceī���� ó�� �κ�
	$Acecounter = &load_class('Acecounter','Acecounter');
	$Acecounter->get_common_script();
	$Acecounter->member_login($session->m_id);
	if($Acecounter->scripts){
		echo $Acecounter->scripts;
	}

	// �α��� ���� ���
	if(function_exists('mobile_member_log')){
		mobile_member_log( $session->m_id );
	}


	## � üũ
	if ($session->level > 80) {
		include(SHOPROOT.'/proc/shop_warning_msg.php');
	}

	// �����̼� �з� ����
	$todayshop = & load_class('todayshop','todayshop');
	if ($todayshop->auth() && $todayshop->cfg['useTodayShop'] == 'y') {
		$ts_interest = unserialize(stripslashes($todayshop->cfg['interest']));
		if ($ts_interest['use'] == 'y') {
			// ���� �з��� ��ϵǾ� �ִ°�
			list($sc) = $db->fetch("SELECT category FROM ".GD_TODAYSHOP_SUBSCRIBE." WHERE m_id = '".$session->m_id."' AND category <> '' ");

			if (!$sc) $ext_param = '&interest=1';
			else	 {
				$ext_param = '&category='.$sc;
				$_POST['returnUrl'] = isset($_POST['returnUrl']) ? str_replace('today_goods.php','today_list.php',$_POST['returnUrl']) : str_replace('today_goods.php','today_list.php',$_SERVER['HTTP_REFERER']);
			}

		}
	}
}

if($_GET['sess_id'])
{
	$returnUrl = $sitelink->link_mobile('index.php','regular');
	go($returnUrl);
	exit;
}

$auth_date = getAdultAuthDate($session->m_id);
$auth_date = $auth_date['auth_date'];
$current_date = date("Y-m-d");
$auth_period = strtotime("+1 years", strtotime($auth_date)); 
$auth_period = date("Y-m-d", $auth_period);

if (!$_POST['returnUrl']) $_POST['returnUrl'] = $_SERVER['HTTP_REFERER'];

if(strpos($_SERVER['HTTP_REFERER'], 'intro/intro_adult.php') && ($auth_date == '0000-00-00' || $current_date > $auth_period) && ((int)($session->level) < 80) ){
	go($mobileRootDir.'/intro/intro_adult_login.php?returnUrl=' . urlencode($_POST['returnUrl']) . ($_SERVER['QUERY_STRING'] ? '&'.$_SERVER['QUERY_STRING'] : ''));
}
else{
	if(($auth_date != '0000-00-00' && $current_date < $auth_period) || ((int)($session->level) > 80)){
		$_SESSION['adult'] = 1;
	}
	$div = explode("/",$_POST['returnUrl']);
	if (preg_match("/http/",$div[0]) && in_array($div[count($div)-2],array("mem","myp"))) $_POST['returnUrl'] = "../index.php";
	$_POST['returnUrl'] = preg_match('/\?/',$_POST['returnUrl']) ? $_POST['returnUrl'].$ext_param : $_POST['returnUrl'].'?'.$ext_param;
	go($_POST['returnUrl']);
}
?>