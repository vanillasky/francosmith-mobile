<?
/*********************************************************
* 파일명     :  /mem/nomember_order.php
* 프로그램명 :	모바일샵 회원 가입
* 작성자     :  dn
* 생성일     :  2012.08.14
**********************************************************/
include "../_header.php";
include $shopRootDir ."/conf/fieldset.php";
include $shopRootDir ."/conf/mobile_fieldset.php";

$hpauth = Core::loader('Hpauth');

$hpauthRequestData = $hpauth->getAuthRequestData();

### 회원인증여부
if($_GET['mode'] != 'addinfo' && $_GET['mode'] != 'endjoin' ) {
	if( $sess ){
		msg("고객님은 로그인 중입니다.", $code=-1 );
	}
}
else {
	if(!$sess && !$_SESSION['tmp_m_no']) {
		msg("잘못된 유입경로 입니다.", $code=-1);
	}
}

$mode = "joinMember";
$checked[sex][m] = $checked[calendar][s] = $checked[sex][m] = "checked";
if ($_POST[resno][1] && $_POST[resno][1][0]%2==0) $checked[sex][w] = "checked";
foreach ($checked[reqField] as $k => $v) $required[$k] = "required";

if ($_POST[resno][0]){
	$_POST[birth_year] = 1900 + substr($_POST[resno][0],0,2) + floor((substr($_POST[resno][1],0,1)-1)/2) * 1000;
	$_POST[birth][0] = substr($_POST[resno][0],2,2);
	$_POST[birth][1] = substr($_POST[resno][0],4,2);
}

// 가입폼작성에서 만14세 미만 회원가입 허용상태
if (strpos($_SERVER['HTTP_REFERER'],$_SERVER['PHP_SELF']) && isset($_POST['type'])) {
	$mUnder14 = Core::loader('memberUnder14Join');
	$under14Code = $mUnder14->joinWrite();
	if ( $under14Code == 'rejectJoin' ) { // 만14세 미만 회원가입 거부
		msg('만 14세 미만의 경우 회원가입을 허용하지 않습니다.', -1);
	}
	$tpl->assign('under14Code', $under14Code);
	$tpl->assign('under14Status', $mUnder14->under14Status);
	$customHeader .= '<script src="../..'.$cfg['rootDir'].'/lib/js/member.under14.js" type="text/javascript"></script>';
	$tpl->assign('customHeader', $customHeader);
}
else if ($socialMemberService->isEnabled() && $_GET['MODE'] === 'social_member_join') {
	$mUnder14 = Core::loader('memberUnder14Join');
	$under14Code = $mUnder14->joinWrite();
	$tpl->assign('under14Code', $under14Code);
	$tpl->assign('under14Status', $mUnder14->under14Status);
	$customHeader .= '<script src="../..'.$cfg['rootDir'].'/lib/js/member.under14.js" type="text/javascript"></script>';
	$tpl->assign('customHeader', $customHeader);
}

if ($socialMemberService->isEnabled()) {
	$_MODE = $_GET['MODE'];
	$_SOCIAL_CODE = $_GET['SOCIAL_CODE'];
	$socialMember = SocialMemberService::getMember($_SOCIAL_CODE);
	if (!isset($_MODE)) {
		$enabledSocialMemberServiceList = $socialMemberService->getEnabledServiceList();
		if (in_array(SocialMemberService::FACEBOOK, $enabledSocialMemberServiceList)) {
			$facebookMember = SocialMemberService::getMember(SocialMemberService::FACEBOOK);
			$tpl->assign('FacebookLoginURL', $facebookMember->getMobileLoginURL('../'));
		}
		$tplfile = 'mem/join_type.htm';
	}
	else if ($_MODE === 'social_member_join') {
		if ($socialMember->hasError()) {
			msg('시스템 장애가 발생하였습니다.\r\n고객센터로 문의하여주시기 바랍니다.', -1);
		}
		$name = $socialMember->getName();
		$email = $socialMember->getEmail();
		if (strlen($email) > 0) {
			$emailID = array_shift(explode('@', $email));
			list($memberID) = $db->fetch('SELECT m_id FROM '.GD_MEMBER.' WHERE email="'.$email.'"');
			if (strlen($memberID) < 1) {
				//휴면계정 조회
				$dormant = Core::loader('dormant');
				list($memberID) = $dormant->checkDormantEmail($email, 'm_id');
			}

			if (strlen($memberID) > 0) {
				msg($email.'\r\n다른 계정에서 사용중인 이메일 입니다.\r\n이미 가입하신 경우 다른 로그인 수단을 통해 로그인 해 주시기 바랍니다.', -1);
			}
			else {
				$tpl->assign('email', $email);
				$tpl->assign('email_id', $emailID);
				$tplfile = 'mem/social_member_join.htm';
			}
		}
		else {
			$tplfile = 'mem/social_member_join.htm';
		}

		// 생년월일 생일정의
		$birthday = $socialMember->getBirthday();
		if (strlen($birthday) == 8) {
			$birth_year = substr($birthday, 0, 4);
			$birth[0] = substr($birthday,4,2);
			$birth[1] = substr($birthday,6,2);
			$tpl->assign('birth_year', $birth_year);
			$tpl->assign('birth', $birth);
		}

		$tpl->assign('name', $name);
		$tpl->assign('MODE', $_MODE);
		$tpl->assign('SOCIAL_CODE', $_SOCIAL_CODE);
	}
	else if (strpos($_SERVER['HTTP_REFERER'],$_SERVER['PHP_SELF']) && isset($_POST['type'])) {
		$tplfile = 'mem/join.htm';
	}
	else {
		$tplfile = 'mem/agreement.htm';
	}
}
else {
	if (strpos($_SERVER['HTTP_REFERER'],$_SERVER['PHP_SELF']) && isset($_POST['type'])) {
		$tplfile = 'mem/join.htm';
	}
	else {
		$tplfile = 'mem/agreement.htm';
	}
}

if($_GET['mode'] == 'addinfo' && ($sess || $_SESSION['tmp_m_no'])) {
	$tplfile = "mem/addinfo.htm";
}

if($_GET['mode'] == 'endjoin' && ($sess || $_SESSION['tmp_m_no'])) {
	$tplfile = "mem/endjoin.htm";
	unset($_SESSION['adult']);
}

// 아이핀
if ($_POST['rncheck'] == 'ipin' || $_POST['rncheck'] == 'hpauthDream') {
	if ($_POST['sex'] == "M") 	$checked[sex][m] = "checked";
	else 						$checked[sex][w] = "checked";

	if (strlen($_POST['birthday']) == 8) {
		$_POST[birth_year] = substr($_POST['birthday'], 0, 4);
		$_POST[birth][0] = substr($_POST['birthday'],4,2);
		$_POST[birth][1] = substr($_POST['birthday'],6,2);
	}

	$_POST['name'] = $_POST['nice_nm'];

	if (strlen($_POST['mobile']) == 11) { //휴대폰자리가 11자리이면
		$mobile[0] = substr($_POST['mobile'],0,3);
		$mobile[1] = substr($_POST['mobile'],3,4);
		$mobile[2] = substr($_POST['mobile'],7,4);
	} else if (strlen($_POST['mobile']) == 10) { //휴대폰자리가 10자리이면
		$mobile[0] = substr($_POST['mobile'],0,3);
		$mobile[1] = substr($_POST['mobile'],3,3);
		$mobile[2] = substr($_POST['mobile'],6,4);
	}
	$_POST['mobile'] = $mobile;

}

$ipinyn = (empty($ipin['id']) ? 'n' : empty($ipin['useyn']) ? 'n': $ipin['useyn']);
$niceipinyn = (empty($ipin['code']) ? 'n' : empty($ipin['nice_useyn'])? 'n': $ipin['nice_useyn']);
$ipinStatus = ($ipinyn == 'y' || $niceipinyn == 'y') ? 'y' : 'n';

$res = $db->query("select category, catnm from ".GD_CATEGORY." where hidden='0' and length(category)=3");
while ( $category_one = $db->fetch( $res, 1 ) ) $category[] = $category_one;

// 회원가입시 휴대폰 본인확인사용하고 회원 가입시 휴대폰 번호 수정이 불가능하면 휴대폰번호 변경 불가
$mobileReadonly='';
if($hpauthRequestData['useyn']=='y' && $hpauthRequestData['modyn']=='n' && $_POST['rncheck']=='hpauthDream') $mobileReadonly='readonly';

//추가동의항목
$consentData = array();
$result = $db->query("SELECT * FROM ".GD_CONSENT." WHERE useyn = 'y' ORDER BY sno ASC");
while ($data = $db->fetch($result)){
	$data['requiredyn_text'] = $data['requiredyn'] == 'y' ? '필수' : '선택';
	$consentData[] = $data;
}

$tpl->assign($_POST);
$tpl->assign('ipinyn', $ipinyn);
$tpl->assign('niceipinyn', $niceipinyn);
$tpl->assign('ipinStatus', $ipinStatus);
$tpl->assign('hpauthyn', $hpauthRequestData['useyn']);
$tpl->assign('hpauthCPID', $hpauthRequestData['cpid']);
$tpl->assign('mobileReadonly', $mobileReadonly);
$tpl->assign('shopName', $cfg['shopName']);
$tpl->assign('ts_category_all', $category);
$tpl->define(array(
			'frmMember'		=> 'mem/_form.htm',
			'tpl'			=> $tplfile,
			));

### 무료보안서버 회원처리url
$tpl->assign('memActionUrl',$sitelink->link_mobile('mem/indb.php','ssl'));

// 추가동의항목
$tpl->assign('consentData', $consentData);

$tpl->print_('tpl');

?>