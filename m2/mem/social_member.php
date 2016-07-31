<?php

include dirname(__FILE__).'/../lib/library.php';
include $shopRootDir.'/lib/SocialMember/SocialMemberServiceLoader.php';

$_MODE = $_REQUEST['MODE'];
$_SOCIAL_CODE = $_REQUEST['SOCIAL_CODE'];

if (isset($_REQUEST['user_identifier'])) {
	SocialMemberService::setPersistentData('user_identifier', $_REQUEST['user_identifier']);
}

$socialMember = SocialMemberService::getMember($_SOCIAL_CODE);

if (!$_GET['returnUrl']) $returnUrl = $_SERVER['HTTP_REFERER'];
else $returnUrl = $_GET['returnUrl'];

if(!$returnUrl) $returnUrl = $mobileRootDir;

switch ($_MODE) {
	case 'login':
		if (!$socialMember || $socialMember->hasError()) {
			msg('시스템 장애가 발생하였습니다.\r\n고객센터로 문의하여주시기 바랍니다.', '../');
		}
		if (isset($_REQUEST['error'])) {
			msg('페이스북 앱에서 사용자 정보에대한 사용권한을\r\n획득하지 못하여 로그인 할 수 없습니다.', '../');
		}
		if (isset($_REQUEST['error_code'])) {
			msg('페이스북 앱에서 사용자 정보에대한 사용권한을\r\n획득하지 못하여 로그인 할 수 없습니다.', '../');
		}

		SocialMemberService::updateIdentifierIfChanged($socialMember);
		if (SocialMemberService::existsMember($socialMember)) {
			echo '<script type="text/javascript">location.replace("./login_ok.php?SOCIAL_CODE='.$_SOCIAL_CODE.'&returnUrl='.$returnUrl.'");</script>';
		}
		else {
			echo '<script type="text/javascript">location.replace("./join.php?MODE=social_member_join&SOCIAL_CODE='.$_SOCIAL_CODE.'");</script>';
		}
		break;
	case 'join':

		include $shopRootDir."/conf/fieldset.php";

		// 생년월일 정의
		if (strlen($_POST['birthday']) === 8) { // 아이핀 & 휴대폰인증
			$_POST['birthday'] = $_POST['birthday'];
		}
		else if ($_POST['birth']) { // 생년월일 수기입력
			$_POST['birthday'] = trim(sprintf("%04d%02d%02d",$_POST['birth_year'],$_POST['birth'][0],$_POST['birth'][1]));
		}

		// 회원저장에서 만14세 미만 회원가입 허용상태
		if (file_exists($shopRootDir.'/lib/memberUnder14Join.class.php') === true) {
			$mUnder14 = Core::loader('memberUnder14Join');
			$under14Code = $mUnder14->joinIndb($_POST['birthday']);
			$under14 = 0;
			if ( $under14Code == 'rejectJoin' ) { // 만14세 미만 회원가입 거부
				msg('만 14세 미만의 경우 회원가입을 허용하지 않습니다.');
				exit;
			}
			else if ( $under14Code == 'undecidableRejectJoin' ) { // 만14세 미만 판단불가로 회원가입 거부
				msg('만14세 미만을 확인할 수 없으므로 회원가입을 허용하지 않습니다. 관리자에게 문의해 주세요.');
				exit;
			}
			else if ( $under14Code == 'adminStatus' ) { // 만14세 미만 회원가입 관리자 승인 후 가입
				$joinset['status'] = 0;
				$under14 = 1;
			}
			else if ( $under14Code == 'undecidableAdminStatus' ) { // 만14세 미만 판단불가로 회원가입 관리자 승인 후 가입
				$joinset['status'] = 0;
			}
			else if ( $under14Code == 'over14' ) { // 만14세 이상
				$under14 = 2;
			}

			// 'under14' 필드 존재여부
			$fRes = $db->_select("SHOW COLUMNS FROM ".GD_MEMBER." WHERE field='under14'");
			if ($fRes[0]['Field'] != '') {
				$under14FieldYn = 'Y';
			}
		}

		if (!$socialMember || $socialMember->hasError()) {
			msg('시스템 장애가 발생하였습니다.\r\n고객센터로 문의하여주시기 바랍니다.');
			exit;
		}
		if (SocialMemberService::existsMember($socialMember)) {
			msg('이미 등록되어있는 회원입니다.\r\n자동로그인 처리 됩니다.', '../main/index.php', 'parent');
		}

		if ($socialMember->getLoginStatus() !== true) {
			msg(SocialMemberService::getServiceName($socialMember->getCode()).'에서 연결이 종료되었습니다.\r\n다시 진행하여주시기 바랍니다.', './join.php', 'parent');
		}


		if (!$joinset[grp]) $joinset[grp] = 1;

		### 아이디 입력형식
		if (preg_match('/^[a-zA-Z0-9_-]{6,16}$/',$_POST['m_id']) == 0) msg('아이디 입력 형식 오류입니다', -1);

		### 거부 아이디 필터링
		if (find_in_set(strtoupper($_POST[m_id]),strtoupper($joinset[unableid]))) msg("사용 불가능한 아이디입니다", -1);

		### 필수값 체크
		@include $shopRootDir ."/conf/mobile_fieldset.php";

		if (!$_POST['m_id'] || !$_POST['name']) {
			msg('회원 가입시 회원아이디, 이름은 필수 입력값입니다. 다시 시도해 주세요', -1);
		}

		### 아이디 중복여부 체크
		list ($chk) = $db->fetch("select m_id from ".GD_MEMBER." where m_id='$_POST[m_id]'");
		if ($chk) msg("이미 등록된 아이디입니다", -1);

		### 이메일 중복여부 체크
		if (isset($_POST['email']) === true) {
			list ($chk) = $db->fetch("select email from ".GD_MEMBER." where email='".$_POST['email']."'");
			if ($chk) msg("이미 등록된 이메일입니다", -1);
		}

		# 아이핀 추가
		if ($_POST[rncheck] == "ipin") {
			### dupeinfo 를 필드 dupeinfo 값과 비교한다.
			list ($chk) = $db->fetch("select count(*) as cnt from ".GD_MEMBER." where dupeinfo = '".$_POST['dupeinfo']."'");
			if ($chk > 0) {
				msg("이미 회원등록한 고객입니다.", -1);
			}
		} else {
			### 주민등록번호 중복여부 체크
			list ($chk) = $db->fetch("select resno1 from ".GD_MEMBER." where resno1='$resno1' and resno2='$resno2'");
			if ($resno1 && $resno2 && $chk){
				msg("이미 등록된 주민등록번호입니다", -1);
			}
			### dupeinfo 값으로도 체크한다.
			if ($_POST['dupeinfo']) {
				list ($chk) = $db->fetch("select count(*) as cnt from ".GD_MEMBER." where dupeinfo = '".$_POST['dupeinfo']."'");
				if ($chk > 0) {
					msg("이미 회원등록한 고객입니다.", -1);
				}
			}
		}

		### 회원재가입기간 체크
		if ( $joinset[rejoin] > 0 ){
			$rejoindt = date('Ymd', time() - (($joinset[rejoin]-1)*86400));
			list ($chk) = $db->fetch("select regdt from ".GD_LOG_HACK." where resno1='$resno1' and resno2='$resno2' and date_format( regdt, '%Y%m%d' ) >={$rejoindt} order by regdt desc limit 1");
			if($resno1 && $resno2 && $chk) msg("회원탈퇴 후 {$joinset[rejoin]}일 동안 재가입할 수 없습니다.\\n회원님은 {$chk}에 탈퇴하셨습니다.",-1);

			if ($_POST['dupeinfo']) {
				list ($chk) = $db->fetch("select regdt from ".GD_LOG_HACK." where dupeinfo='".$_POST['dupeinfo']."' and date_format( regdt, '%Y%m%d' ) >={$rejoindt} order by regdt desc limit 1");
				if($_POST['dupeinfo'] && $chk) msg("회원탈퇴 후 {$joinset[rejoin]}일 동안 재가입할 수 없습니다.\\n회원님은 {$chk}에 탈퇴하셨습니다.",-1);
			}
		}

		if ($_POST[rncheck] != "ipin") {
			### 주민번호와 성별 체크
			if ($_POST[resno][1] && $_POST[sex]) {
				$resno_01 = substr($_POST[resno][1], 0, 1);
				if ( ( ($resno_01 == "2" || $resno_01 == "4") && $_POST[sex]=="m") || ( ($resno_01 == "1" || $resno_01 == "3") && $_POST[sex]=="w") ){
					msg("주민등록번호와 성별이 일치하지 않습니다.", -1);
					exit;
				}
			}
		}

		### 변수 재설정
		$ins_arr['name'] = $_POST['name'];
		$ins_arr['nickname'] = $_POST['nickname'];
		$ins_arr['sex'] = $_POST['sex'];
		if (strlen($_POST['birthday']) === 8) {
			$ins_arr['birth_year'] = substr($_POST['birthday'], 0, 4);
			$ins_arr['birth'] = substr($_POST['birthday'], 4, 4);
		}

		$ins_arr['calendar'] = $_POST['calendar'];
		$ins_arr['email'] = $_POST['email'];
		$ins_arr['mailling'] =  ($_POST['mailling']) ? 'y' : 'n';
		$ins_arr['zipcode'] = @implode('-', $_POST['zipcode']);
		$ins_arr['address'] = $_POST['address'];
		$ins_arr['road_address'] = $_POST['road_address'];
		$ins_arr['address_sub'] = $_POST['address_sub'];
		if (strlen($_POST['mobile']) === 10) {
			$ins_arr['mobile'] = preg_replace('/([0-9]{3})([0-9]{3})([0-9]{4})/', '${1}-${2}-${3}', $_POST['mobile']);
		}
		else if (strlen($_POST['mobile']) === 11) {
			$ins_arr['mobile'] = preg_replace('/([0-9]{3})([0-9]{4})([0-9]{4})/', '${1}-${2}-${3}', $_POST['mobile']);
		}
		$ins_arr['sms'] =  ($_POST['sms']) ? 'y' : 'n';
		$ins_arr['phone'] = @implode('-', $_POST['phone']);
		$ins_arr['fax'] = @implode('-', $_POST['fax']);
		$ins_arr['company'] = $_POST['company'];
		$ins_arr['service'] = $_POST['service'];
		$ins_arr['item'] = $_POST['item'];
		$ins_arr['busino'] = preg_replace("/[^0-9-]+/",'',$_POST['busino']);

		$ins_arr['marriyn'] = $_POST['marriyn'];
		if ($_POST['marridate']) {
			$ins_arr['marridate'] = trim(sprintf("%4d%02d%02d",$_POST['marridate'][0],$_POST['marridate'][1],$_POST['marridate'][2]));
		}
		$ins_arr['job'] = $_POST['job'];
		if (is_array($_POST['interest'])) {
			$ins_arr['interest'] = array_sum($_POST['interest']);
		}
		$ins_arr['memo'] = $_POST['memo'];

		$ins_arr['ex1'] = $_POST['ex1'];
		$ins_arr['ex2'] = $_POST['ex2'];
		$ins_arr['ex3'] = $_POST['ex3'];
		$ins_arr['ex4'] = $_POST['ex4'];
		$ins_arr['ex5'] = $_POST['ex5'];
		$ins_arr['ex6'] = $_POST['ex6'];

		$ins_arr['private1']  = 'y';
		$ins_arr['private2']  = $_POST['chk_termsThirdPerson'];
		$ins_arr['private3']  = $_POST['chk_termsEntrust'];
		if ($under14FieldYn == 'Y') $ins_arr['under14'] = $under14;
		$ins_arr['inflow']    = 'mobileshop';

		$query = "
		insert into ".GD_MEMBER." set
			m_id		= '$_POST[m_id]',
			password	= password('$_POST[password]'),
			status		= '$joinset[status]',
			emoney		= '$joinset[emoney]',
			level		= '$joinset[grp]',
			regdt		= now(),
			recommid	= '$_POST[recommid]',
			LPINFO		= '$_COOKIE[LPINFO]',
			dupeinfo	= '$_POST[dupeinfo]',
			foreigner	= '$_POST[foreigner]',
			pakey		= '$_POST[pakey]',
			rncheck		= '$_POST[rncheck]',
		";
		$qr = "";
		foreach ($ins_arr as $k=>$v) {
			$qr .= " ".$k." = '".$v."' ";
			if ($k != 'inflow')  $qr .= ",";
		}
		$query .= $qr;

		$db->query($query);
		$m_no = $db->_last_insert_id();

		//추가동의항목 동의여부
		if (is_array($_POST['consent']) && count($_POST['consent'])>0){
			foreach ($_POST['consent'] as $key => $value){
				$query = "INSERT INTO ".GD_MEMBER_CONSENT." SET m_no = '".$m_no."', consent_sno = '".$key."', consentyn = '".$value."', regdt=now()";
				$db->query($query);
			}
		}

		if($m_no) {
			// 소셜계정 연결
			$socialMember->connect($m_no);

			### 적립금 내역 입력
			$code = codeitem('point');
			$query = "
			insert into ".GD_LOG_EMONEY." set
				m_no	= '$m_no',
				emoney	= '$joinset[emoney]',
				memo	= '" . $code['01'] . "',
				regdt	= now()
			";
			$db->query($query);


			### 추천인 적립금 체크 shop/member/indb.php와 같은 로직
			if($checked_mobile['useField']['recommid'] == "checked" && $_POST['recommid']){


				# 자기 자신은 추천을 못하게 처리
				if( $_POST['recommid'] != $_POST['m_id'] ){

					list ($recomm_m_id,$recomm_m_no) = $db->fetch("select m_id,m_no from ".GD_MEMBER." where m_id='".$_POST['recommid']."'");

					# 추천인이 있는경우
					if($recomm_m_id){

						# 추천인에게 적립금 적립
						if($joinset['recomm_emoney'] > 0){
							$query = "
							insert into ".GD_LOG_EMONEY." set
								m_no	= '".$recomm_m_no."',
								emoney	= '".$joinset['recomm_emoney']."',
								memo	= '".$_POST['m_id']." 회원의 추천으로 포인트 적립',
								regdt	= now()
							";
							$db->query($query);

							$strSQL = "UPDATE ".GD_MEMBER." SET emoney = emoney + '".$joinset['recomm_emoney']."' WHERE m_no = '".$recomm_m_no."'";
							$db->query($strSQL);
						}

						# 추천한사람(가입자) 적립금 적립
						if($joinset['recomm_add_emoney'] > 0){
							$query = "
							insert into ".GD_LOG_EMONEY." set
								m_no	= '".$m_no."',
								emoney	= '".$joinset['recomm_add_emoney']."',
								memo	= '".$_POST['recommid']." 회원을 추천하여 포인트 적립',
								regdt	= now()
							";
							$db->query($query);

							$strSQL = "UPDATE ".GD_MEMBER." SET emoney = emoney + '".$joinset['recomm_add_emoney']."' WHERE m_no = '".$m_no."'";
							$db->query($strSQL);
						}

					# 추천인이 없는경우
					} else {
						$query = "
						insert into ".GD_LOG_EMONEY." set
							m_no	= '".$m_no."',
							emoney	= '0',
							memo	= '추천인아이디의 오류로 적립안됨',
							regdt	= now()
						";
						$db->query($query);
					}
				}
			}

            ### 회원가입SMS
			if (strlen($ins_arr['mobile'])>7) {
                		sendSmsCase('join',$ins_arr['mobile']);
			}

			### 회원가입메일
			if ( $_POST[email] && $cfg[mailyn_10] == 'y' )
			{
				$modeMail = 10;
				include $shopRootDir."/lib/automail.class.php";
				$automail = new automail();
				$automail->_set($modeMail,$_POST[email],$cfg);
				$automail->_assign($_POST);
				$automail->_send();
			}

			### 회원가입쿠폰 발급
			$date = date('Y-m-d H:i:s');
			$query = "select * from ".GD_COUPON." where coupontype = 2 and (( priodtype = 1 ) or ( priodtype = 0 and sdate <= '$date' and edate >= '$date' ))";
			$res = $db->query($query);
			$couponCnt=0;
			while($data = $db->fetch($res)){

				$query = "select count(a.sno) from ".GD_COUPON_APPLY." a left join ".GD_COUPON_APPLY."member b on a.sno=b.applysno where a.couponcd='$data[couponcd]' and b.m_no = '$m_no'";
				list($cnt) = $db->fetch($query);
				if(!$cnt){
					$newapplysno = new_uniq_id('sno',GD_COUPON_APPLY);
					$query = "insert into ".GD_COUPON_APPLY." set
								sno				= '$newapplysno',
								couponcd		= '$data[couponcd]',
								membertype		= '2',
								member_grp_sno  = '',
								regdt			= now()";
					$db->query($query);

					$query = "insert into ".GD_COUPON_APPLY."member set m_no='$m_no', applysno ='$newapplysno'";
					$db->query($query);
					$couponCnt++;
				}
			}

			if($joinset['status']=='0') {
				$_SESSION['tmp_m_no'] = $m_no;
				msg('관리자 승인후 가입처리됩니다.', 'join.php?mode=endjoin', 'parent');
			}
			else {
				// 소셜로그인 처리
				$result = $socialMember->login();
				go('join.php?mode=endjoin', 'parent');
			}


		}
		else {
			msg('회원 가입중 오류가 발생했습니다. 다시 시도해 주세요', -1);
		}
		break;
}