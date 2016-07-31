<?
/*********************************************************
* 파일명     :  /mem/nomember_order.php
* 프로그램명 :	모바일샵 회원 가입
* 작성자     :  dn
* 생성일     :  2012.08.14
**********************************************************/
@include dirname(__FILE__) . "/../lib/library.php";
@include $shopRootDir . "/conf/config.php";
@include $shopRootDir . "/conf/config.pay.php";
@include $shopRootDir . "/lib/cart.class.php";
@include $shopRootDir . "/conf/coupon.php";

if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
	$_POST = validation::xssCleanArray($_POST, array(
		validation::DEFAULT_KEY	=> 'text',
		'password'				=> 'disable'
	));
}

if ($_POST[mode]=="chkRealName"){

	header ("Cache-Control: no-cache, must-revalidate");
	header ("Pragma: no-cache");
	echo "
	<script>
	parent.document.frmAgree.action = '';
	parent.document.frmAgree.target = '';
	parent.document.frmAgree.submit();
	</script>
	";
	exit;
}

$mode = $_POST['mode'];
unset($_POST['mode']);

switch($mode) {
	case 'member_join' :

		include $shopRootDir."/conf/fieldset.php";
		@include $shopRootDir."/conf/mobile_fieldset.php";
		$dormant = Core::loader('dormant');

		if (!$joinset[grp]) $joinset[grp] = 1;

		// 회원저장에서 만14세 미만 회원가입 허용상태
		$mUnder14 = Core::loader('memberUnder14Join');
		if ($_POST['birth']) $birthday = trim(sprintf("%04d%02d%02d",$_POST['birth_year'],$_POST['birth'][0],$_POST['birth'][1]));
		$under14Code = $mUnder14->joinIndb($birthday);
		$under14 = 0;
		if ( $under14Code == 'rejectJoin' ) { // 만14세 미만 회원가입 거부
			msg('만 14세 미만의 경우 회원가입을 허용하지 않습니다.', -1);
		}
		else if ( $under14Code == 'undecidableRejectJoin' ) { // 만14세 미만 판단불가로 회원가입 거부
			msg('만14세 미만을 확인할 수 없으므로 회원가입을 허용하지 않습니다. 관리자에게 문의해 주세요.', -1);
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

		### 아이디 입력형식
		if (preg_match('/^[a-zA-Z0-9_-]{6,16}$/',$_POST['m_id']) == 0) msg('아이디 입력 형식 오류입니다', -1);

		//패스워드 입력형식
		if($_POST['passwordSkin'] === 'Y'){
			if(passwordPatternCheck($_POST['password']) === false) msg('10~16자의 영문대소문자,숫자,특수문자를 조합하여 사용할 수 있습니다.', -1);
		}

		### 거부 아이디 필터링
		if (find_in_set(strtoupper($_POST[m_id]),strtoupper($joinset[unableid]))) msg("사용 불가능한 아이디입니다", -1);

		### 필수값 체크
		@include $shopRootDir ."/conf/mobile_fieldset.php";

		if (!$_POST['m_id'] || !$_POST['password'] || !$_POST['name']) {
			msg('회원 가입시, 회원아이디, 비밀번호, 이름은 필수 입력값입니다. 다시 시도해 주세요', 'join.php', 'parent');
		}

		### 아이디 중복여부 체크
		list ($chk) = $db->fetch("select m_id from ".GD_MEMBER." where m_id='$_POST[m_id]'");
		if ($chk) msg("이미 등록된 아이디입니다",'join.php');

		### 이메일 중복여부 체크
		if (isset($_POST['email']) === true) {
			list ($chk) = $db->fetch("select email from ".GD_MEMBER." where email='".$_POST['email']."'");
			if(!$chk){
				list($chk) = $dormant->checkDormantEmail($_POST['email']);
			}
			if ($chk) msg("이미 등록된 이메일입니다",'join.php');
		}

		### 닉네임 중복여부 체크
		if (isset($_POST['nickname']) === true) {
			list ($chk) = $db->fetch("select nickname from ".GD_MEMBER." where nickname='".$_POST['nickname']."'");
			if(!$chk){
				$chk = $dormant->getDormantInfo('checkNickname', $_POST);
			}
			if ($chk) msg("이미 등록된 닉네임입니다",'join.php');
		}

		# 아이핀 추가
		if ($_POST[rncheck] == "ipin") {
			### dupeinfo 를 필드 dupeinfo 값과 비교한다.
			list ($chk) = $db->fetch("select count(*) as cnt from ".GD_MEMBER." where dupeinfo = '".$_POST['dupeinfo']."'");
			if ($chk < 1) {
				$chk = $dormant->getCountDupeinfoFromDormant($_POST['dupeinfo']);
			}
			if ($chk > 0) {
				msg("이미 회원등록한 고객입니다.", 'join.php');
			}
		} else {
			### dupeinfo 값으로도 체크한다.
			if ($_POST['dupeinfo']) {
				list ($chk) = $db->fetch("select count(*) as cnt from ".GD_MEMBER." where dupeinfo = '".$_POST['dupeinfo']."'");
				if ($chk < 1) {
					$chk = $dormant->getCountDupeinfoFromDormant($_POST['dupeinfo']);
				}
				if ($chk > 0) {
					msg("이미 회원등록한 고객입니다.", 'join.php');
				}
			}
		}

		### 회원재가입기간 체크
		if ( $joinset[rejoin] > 0 ){
			$rejoindt = date('Ymd', time() - (($joinset[rejoin]-1)*86400));
			if ($_POST['dupeinfo']) {
				list ($chk) = $db->fetch("select regdt from ".GD_LOG_HACK." where dupeinfo='".$_POST['dupeinfo']."' and date_format( regdt, '%Y%m%d' ) >={$rejoindt} order by regdt desc limit 1");
				if($_POST['dupeinfo'] && $chk) msg("회원탈퇴 후 {$joinset[rejoin]}일 동안 재가입할 수 없습니다.\\n회원님은 {$chk}에 탈퇴하셨습니다.",-1);
			}
		}

		### 변수 재설정
		$ins_arr['name'] = $_POST['name'];
		$ins_arr['nickname'] = $_POST['nickname'];
		$ins_arr['sex'] = ($_POST['sex']) ? $_POST['sex'] : 'm';
		$ins_arr['birth_year'] = $_POST['birth_year'];
		if ($_POST['birth']) {
			$ins_arr['birth'] = trim(sprintf("%02d%02d",$_POST['birth'][0],$_POST['birth'][1]));
		}

		$ins_arr['calendar'] = ($_POST['calendar']) ? $_POST['calendar'] : 's';
		$ins_arr['email'] = $_POST['email'];
		$ins_arr['mailling'] =  ($_POST['mailling']) ? 'y' : 'n';
		$ins_arr['zipcode'] = @implode('-', $_POST['zipcode']);
		$ins_arr['zonecode'] = $_POST['zonecode'];
		$ins_arr['address'] = $_POST['address'];
		$ins_arr['road_address'] = $_POST['road_address'];
		$ins_arr['address_sub'] = $_POST['address_sub'];
		$ins_arr['mobile'] = @implode("-",$_POST['mobile']);
		$mobile = @implode("-",$_POST['mobile']);
		$ins_arr['sms'] =  ($_POST['sms']) ? 'y' : 'n';
		$ins_arr['phone'] = @implode('-', $_POST['phone']);
		$ins_arr['fax'] = @implode('-', $_POST['fax']);
		$ins_arr['company'] = $_POST['company'];
		$ins_arr['service'] = $_POST['service'];
		$ins_arr['item'] = $_POST['item'];
		$ins_arr['busino'] = preg_replace("/[^0-9-]+/",'',$_POST['busino']);

		$ins_arr['marriyn'] = ($_POST['marriyn']) ? $_POST['marriyn'] : 'n';
		if ($_POST['marridate']) {
			$ins_arr['marridate'] = trim(sprintf("%4d%02d%02d",$_POST['marridate'][0],$_POST['marridate'][1],$_POST['marridate'][2]));
		}
		$ins_arr['job'] = $_POST['job'];
		if (is_array($_POST['interest'])) {
			$ins_arr['interest'] = array_sum($_POST['interest']);
		}
		$ins_arr['birth_year'] = $_POST['birth_year'];
		if ($_POST['birth']) {
			$ins_arr['birth'] = trim(sprintf("%02d%02d",$_POST['birth'][0],$_POST['birth'][1]));
		}
		$ins_arr['memo'] = $_POST['memo'];

		$ins_arr['ex1'] = $_POST['ex1'];
		$ins_arr['ex2'] = $_POST['ex2'];
		$ins_arr['ex3'] = $_POST['ex3'];
		$ins_arr['ex4'] = $_POST['ex4'];
		$ins_arr['ex5'] = $_POST['ex5'];
		$ins_arr['ex6'] = $_POST['ex6'];

		$ins_arr['foreigner'] = ($_POST['foreigner']) ? $_POST['foreigner'] : '1';
		$ins_arr['private1']  = 'y';
		$ins_arr['private2']  = $_POST['private2'];
		$ins_arr['private3']  = $_POST['private3'];
		$ins_arr['inflow']    = 'mobileshop';

		if (is_array($checked_mobile['reqField'])) {
			foreach($checked_mobile[reqField] as $key=>$value) {
				if ($key == 'mailling' || $key == 'sms') continue;
				if ($_POST[$key] == '') {
					msg('필수입력값('.$key.')이 누락되어 있습니다.  다시 시도해주세요.', 'join.php', 'parent');
				}
			}
		}

		$query = "
		insert into ".GD_MEMBER." set
			m_id		= '$_POST[m_id]',
			password	= password('$_POST[password]'),
			status		= '$joinset[status]',
			under14		= '$under14',
			emoney		= '$joinset[emoney]',
			level		= '$joinset[grp]',
			regdt		= now(),
			recommid	= '$_POST[recommid]',
			LPINFO		= '$_COOKIE[LPINFO]',
			dupeinfo	= '$_POST[dupeinfo]',
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
			### 적립금 내역 입력
			if($joinset[emoney] > 0)
			{
				$code = codeitem('point');
				$query = "
				insert into ".GD_LOG_EMONEY." set
					m_no	= '$m_no',
					emoney	= '$joinset[emoney]',
					memo	= '" . $code['01'] . "',
					regdt	= now()
				";
				$db->query($query);
			}

			### 추천인 적립금 체크 shop/member/indb.php와 같은 로직
			if($checked_mobile['useField']['recommid'] == "checked" && $_POST['recommid']){


				# 자기 자신은 추천을 못하게 처리
				if( $_POST['recommid'] != $_POST['m_id'] ){

					list ($recomm_m_id,$recomm_m_no) = $db->fetch("select m_id,m_no from ".GD_MEMBER." where m_id='".$_POST['recommid']."'");

					# 추천인이 있는경우
					if($recomm_m_id){

						# 추천인에게 적립금 적립
						if($joinset['recomm_emoney'] > 0){
							$dormantMember = false;
							$dormantMemberDataArray = array('m_id' => $_POST['recommid']);
							$dormantMember = $dormant->checkDormantMember($dormantMemberDataArray, 'm_id');

							$query = "
							insert into ".GD_LOG_EMONEY." set
								m_no	= '".$recomm_m_no."',
								emoney	= '".$joinset['recomm_emoney']."',
								memo	= '".$_POST['m_id']." 회원의 추천으로 포인트 적립',
								regdt	= now()
							";
							$db->query($query);

							if($dormantMember === true){
								$strSQL = $dormant->getEmoneyUpdateQuery($recomm_m_no, $joinset['recomm_emoney']);
							}
							else {
								$strSQL = "UPDATE ".GD_MEMBER." SET emoney = emoney + '".$joinset['recomm_emoney']."' WHERE m_no = '".$recomm_m_no."'";
							}

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
			if (strlen($mobile)>7) {
                		sendSmsCase('join',$mobile);
			}

			### 회원가입메일
			if ( $_POST[email] && $cfg[mailyn_10] == 'y' )
			{
				// 수신거부설정 상태
				$acceptAgreeData = array();
				if(function_exists('setAcceptAgreeData')){
					$acceptAgreeData = setAcceptAgreeData($ins_arr['mailling'], $ins_arr['sms']);
				}

				$modeMail = 10;
				include $shopRootDir."/lib/automail.class.php";
				$automail = new automail();
				$automail->_set($modeMail,$_POST[email],$cfg);
				$automail->_assign($acceptAgreeData);
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
				msg('관리자 승인후 가입처리됩니다\n 홈으로 이동하여\n쇼핑을 계속 이용해 주세요', $sitelink->link_mobile('mem/join.php?mode=endjoin'), 'parent');
			}
			else {
				$result = $session->login($_POST['m_id'],$_POST['password']);
				member_log( $session->m_id );

				msg('회원 가입되었습니다.\n홈으로 이동하여\n쇼핑을 계속 이용해 주세요', $sitelink->link_mobile('mem/join.php?mode=endjoin'), 'parent');
			}


		}
		else {
			msg('회원 가입중 오류가 발생했습니다. 다시 시도해 주세요', 'join.php', 'parent');
		}
		break;

	case 'member_addinfo' :

		$upd_arr = array();

		### 변수 재설정
		$upd_arr['name'] = $_POST['name'];
		$upd_arr['nickname'] = $_POST['nickname'];
		$upd_arr['sex'] = ($_POST['sex']) ? $_POST['sex'] : 'm';
		$upd_arr['birth_year'] = $_POST['birth_year'];
		if ($_POST['birth']) {
			$upd_arr['birth'] = trim(sprintf("%02d%02d",$_POST['birth'][0],$_POST['birth'][1]));
		}

		$upd_arr['calendar'] = ($_POST['calendar']) ? $_POST['calendar'] : 's';
		$upd_arr['zipcode'] = @implode('-', $_POST['zipcode']);
		$upd_arr['zonecode'] = $_POST['zonecode'];
		$upd_arr['address'] = $_POST['address'];
		$upd_arr['address_sub'] = $_POST['address_sub'];
		$upd_arr['phone'] = @implode('-', $_POST['phone']);

		$upd_arr['fax'] = @implode('-', $_POST['fax']);
		$upd_arr['company'] = $_POST['company'];
		$upd_arr['service'] = $_POST['service'];
		$upd_arr['item'] = $_POST['item'];
		$upd_arr['busino'] = preg_replace("/[^0-9-]+/",'',$_POST['busino']);

		$upd_arr['marriyn'] = ($_POST['marriyn']) ? $_POST['marriyn'] : 'n';
		if ($_POST['marridate']) {
			$upd_arr['marridate'] = trim(sprintf("%4d%02d%02d",$_POST['marridate'][0],$_POST['marridate'][1],$_POST['marridate'][2]));
		}
		$upd_arr['job'] = $_POST['job'];
		if (is_array($_POST['interest'])) {
			$upd_arr['interest'] = array_sum($_POST['interest']);
		}
		$upd_arr['memo'] = $_POST['memo'];

		$upd_arr['ex1'] = $_POST['ex1'];
		$upd_arr['ex2'] = $_POST['ex2'];
		$upd_arr['ex3'] = $_POST['ex3'];
		$upd_arr['ex4'] = $_POST['ex4'];
		$upd_arr['ex5'] = $_POST['ex5'];
		$upd_arr['ex6'] = $_POST['ex6'];

		$upd_arr['private1']  = 'y';

		$upd_query = $db->_query_print('UPDATE '.GD_MEMBER.' SET [cv] WHERE m_no=[i]', $upd_arr, $sess['m_no']);
		$result = $db->query($upd_query);

		if($result) {
			go($sitelink->link_mobile('mem/join.php?mode=endjoin'), 'parent');
		}
		else {
			msg('추가정보 입력중 오류가 발생했습니다. PC 화면에서 다시 시도해 주세요', '/m2/index.php', 'parent');
		}

		break;
		case 'confirm_password' :
			if ($_POST['password']) {
				$checkQuery = "SELECT COUNT(m_id) FROM ".GD_MEMBER." WHERE m_id = '".$sess['m_id']."' AND password in (PASSWORD('".$_POST['password']."'),OLD_PASSWORD('".$_POST['password']."'),MD5('".$_POST['password']."'))";
				list($_SESSION['sess']['confirm_password']) = $db->fetch($checkQuery);
			}
			if (!$_SESSION['sess']['confirm_password']) {
				msg('비밀번호를 정확하게 입력해 주세요.', 'myinfo.php', 'parent');
			} else {
				go($sitelink->link_mobile('mem/myinfo.php'), 'parent');
			}
			break;
		case 'modMember' :
			include $shopRootDir."/conf/fieldset.php";
			@include $shopRootDir."/conf/mobile_fieldset.php";
			$dormant = Core::loader('dormant');

		### 중복 로그인 세션 체크
		if ($_POST['m_id'] != $sess['m_id']) msg('로그인 세션 정보가 중복되었습니다. 다시 로그인하여 주십시오.','./logout.php');

		### 변수 재설정
		if($_POST['name']) $ins_arr['name'] = $_POST['name'];
		if($_POST['nickname']) $ins_arr['nickname'] = $_POST['nickname'];
		if($_POST['birth_year']) $ins_arr['birth_year'] = $_POST['birth_year'];
		if($_POST['birth']) $ins_arr['birth'] = trim(sprintf("%02d%02d",$_POST['birth'][0],$_POST['birth'][1]));
		if($_POST['zipcode']) $ins_arr['zipcode'] = (@implode('',$_POST['zipcode'])) ? @implode('-', $_POST['zipcode']) : '';
		if($_POST['phone']) $ins_arr['phone'] = (@implode('',$_POST['phone'])) ? @implode('-', $_POST['phone']) : '';
		if($_POST['mobile']) $ins_arr['mobile'] = (@implode('',$_POST['mobile'])) ? @implode('-',$_POST['mobile']) : '';
		if($_POST['fax']) $ins_arr['fax'] = (@implode('',$_POST['fax'])) ? @implode('-', $_POST['fax']) : '';
		if($_POST['busino']) $ins_arr['busino'] = preg_replace("/[^0-9-]+/",'',$_POST['busino']);
		if(is_array($_POST['interest'])) $ins_arr['interest'] = array_sum($_POST['interest']);
		if($_POST['marriyn']) $ins_arr['marriyn'] = ($_POST['marriyn']) ? $_POST['marriyn'] : 'n';
		if($_POST['marridate']) $ins_arr['marridate'] = ($_POST['marridate']) ? trim(sprintf("%4d%02d%02d",$_POST['marridate'][0],$_POST['marridate'][1],$_POST['marridate'][2])) : '';
		if($_POST['mailling']) $ins_arr['mailling'] =  ($_POST['mailling']) ? 'y' : 'n';
		if($_POST['sms']) $ins_arr['sms'] =  ($_POST['sms']) ? 'y' : 'n';
		if($_POST['calendar']) $ins_arr['calendar'] = ($_POST['calendar']) ? $_POST['calendar'] : 's';
		if($_POST['sex']) $ins_arr['sex'] = ($_POST['sex']) ? $_POST['sex'] : 'm';
		if($_POST['foreigner']) $ins_arr['foreigner'] = ($_POST['foreigner']) ? $_POST['foreigner'] : '1';
		if($_POST['email']) $ins_arr['email'] = $_POST['email'];
		if($_POST['zonecode']) $ins_arr['zonecode'] = $_POST['zonecode'];
		if($_POST['address']) $ins_arr['address'] = $_POST['address'];
		if($_POST['road_address']) $ins_arr['road_address'] = $_POST['road_address'];
		if($_POST['address_sub']) $ins_arr['address_sub'] = $_POST['address_sub'];
		if($_POST['company']) $ins_arr['company'] = $_POST['company'];
		if($_POST['service']) $ins_arr['service'] = $_POST['service'];
		if($_POST['item']) $ins_arr['item'] = $_POST['item'];
		if($_POST['job']) $ins_arr['job'] = $_POST['job'];
		if($_POST['memo']) $ins_arr['memo'] = $_POST['memo'];
		if($_POST['ex1']) $ins_arr['ex1'] = $_POST['ex1'];
		if($_POST['ex2']) $ins_arr['ex2'] = $_POST['ex2'];
		if($_POST['ex3']) $ins_arr['ex3'] = $_POST['ex3'];
		if($_POST['ex4']) $ins_arr['ex4'] = $_POST['ex4'];
		if($_POST['ex5']) $ins_arr['ex5'] = $_POST['ex5'];
		if($_POST['ex6']) $ins_arr['ex6'] = $_POST['ex6'];
		$ins_arr['private2'] = ($_POST['private2YN']) ? 'y' : 'n';
		$ins_arr['private3'] = ($_POST['private3YN']) ? 'y' : 'n';

		if (is_array($checked_mobile['reqField'])) {
			foreach($checked_mobile['reqField'] as $key=>$value) {
				if ($key == 'mailling' || $key == 'sms') continue;
				if ($ins_arr[$key] == '') {
					msg('필수입력값('.$key.')이 누락되어 있습니다.  다시 시도해주세요.', -1);
				}
			}
		}

		### 비밀번호 검사
		if($_POST['newPassword'] && $_POST['pwd_chk'] == 'y') {
			list ($chk) = $db->fetch("SELECT COUNT(m_id) FROM ".GD_MEMBER." WHERE m_id = '".$_POST['m_id']."' AND password in (PASSWORD('".$_POST['originalPassword']."'),OLD_PASSWORD('".$_POST['originalPassword']."'),MD5('".$_POST['originalPassword']."'))");
			if (!$chk) msg('현재 비밀번호를 정확하게 입력하여 주세요.', -1);

			//패스워드 입력형식
			if($_POST['passwordSkin'] === 'Y'){
				if(passwordPatternCheck($_POST['newPassword']) === false) msg('10~16자의 영문대소문자,숫자,특수문자를 조합하여 사용할 수 있습니다.', -1);
			} else {
				// 사용 가능 여부 (6자 이상 21~7E 까지 ascii)
				if (!preg_match('/^[\x21-\x7E]{6,}$/',$_POST['newPassword'])) msg('비밀번호는 6자 이상 이어야 합니다.', -1);
			}

			$password_query = " ,password = password('".$_POST['newPassword']."'), password_moddt = NOW() ";
		}
		else {
			$password_query = '';
		}

		### 닉네임 중복여부 체크
		if($ins_arr['nickname']) {
			list ($chk) = $db->fetch("select nickname from ".GD_MEMBER." where nickname='".$ins_arr['nickname']."' and m_id != '".$_POST['m_id']."'");
			if(!$chk){
				$chk = $dormant->getDormantInfo('checkNickname', $_POST);
			}
			if ($chk) msg('이미 등록된 닉네임입니다. 다시 작성해 주십시오.', -1);
		}

		### 이메일 중복여부 체크
		if($ins_arr['email']) {
			list ($chk) = $db->fetch("select email from ".GD_MEMBER." where email='".$ins_arr['email']."' and m_id != '".$_POST['m_id']."'");
			if(!$chk){
				list($chk) = $dormant->checkDormantEmail($ins_arr['email'], 'email');
			}
			if ($chk) msg('이미 등록된 이메일입니다. 다시 작성해 주십시오.', -1);
		}

		// 수신동의설정 안내메일
		$sendAcceptAgreeMail = false;
		$originalMailling = $oroginalSms = '';
		list($originalMailling, $oroginalSms) = $db->fetch("SELECT mailling, sms FROM ".GD_MEMBER." WHERE  m_id = '".$_POST['m_id']."' ");
		if($ins_arr['mailling'] != $originalMailling || $ins_arr['sms'] != $oroginalSms){
			$sendAcceptAgreeMail = true;
		}

		$qr = '';
		foreach ($ins_arr as $k=>$v) {
			$qr .= " ".$k." = '".$v."' ";
			if ($k != 'private3')  $qr .= ",";
		}
		$query = " update ".GD_MEMBER." set ";
		if($_POST['dupeinfo']) $query .= " dupeinfo	= '".$_POST['dupeinfo']."', rncheck = 'ipin', ";
		$query .= $qr;
		$query .= $password_query;
		$query .= " where m_no = '$sess[m_no]' ";

		if($db->query($query)){
			$_SESSION['sess']['endConfirm'] = 'y';

			// 수신동의설정 안내메일
			if($sendAcceptAgreeMail === true && function_exists('sendAcceptAgreeMail')){
				sendAcceptAgreeMail($ins_arr['email'], $ins_arr['mailling'], $ins_arr['sms']);
			}

			//추가동의항목 동의여부
			if (is_array($_POST['consents']) && count($_POST['consents'])>0){
				foreach ($_POST['consents'] as $key => $value){
					list($consentMemberSno) = $db->fetch("SELECT sno FROM ".GD_MEMBER_CONSENT." WHERE m_no = '".$sess['m_no']."' AND consent_sno = '".$key."'");
					if ($consentMemberSno){
						$query = "UPDATE ".GD_MEMBER_CONSENT." SET consentyn = '".$value."' WHERE m_no = '".$sess['m_no']."' AND consent_sno = '".$key."'";
					} else {
						$query = "INSERT INTO ".GD_MEMBER_CONSENT." SET m_no = '".$sess['m_no']."', consent_sno = '".$key."', consentyn = '".$value."', regdt=now()";
					}
					$db->query($query);
				}
			}

			// 회원정보 수정 이벤트
			$info_cfg = $config->load('member_info');

			if ($info_cfg['event_use'] && (int)$info_cfg['event_emoney'] > 0) {
				$now = date('Y-m-d H:i:s');
				if ( $now >= $info_cfg['event_start_date'] && $now <= $info_cfg['event_end_date'] ) {

					$query = "select count(*) from ".GD_MEMBER." where m_no = ".$sess['m_no']." and regdt < '".$info_cfg['event_start_date']."'";	//이벤트 전에 가입했는지 체크
					list($isEvent) = $db->fetch($query);
					if($isEvent > 0){
						// 지급 내역
						$query = sprintf("SELECT count(sno) from ".GD_LOG_EMONEY." where m_no = %d and memo = '회원정보 수정 이벤트' and regdt between '%s' and '%s'",$sess['m_no'], $info_cfg['event_start_date'], $info_cfg['event_end_date'] );
						list($history) = $db->fetch($query);
						if ($history < 1) {

							$query = sprintf("update ".GD_MEMBER." set emoney = emoney + %d where m_no = %d", $info_cfg['event_emoney'], $sess['m_no']);
							$db->query($query);

							$query = sprintf("insert into ".GD_LOG_EMONEY." set m_no = %d, ordno = '', emoney = %d, memo = '회원정보 수정 이벤트', regdt = '%s'", $sess['m_no'], $info_cfg['event_emoney'], $now);
							$db->query($query);

							msg('"회원정보수정 이벤트"\n\n적립금 '.number_format($info_cfg['event_emoney']).'원이 지급되었습니다.', $sitelink->link_mobile('mem/myinfo.php'), 'parent');
							break(1);	// break case "modMember"
						}
					}
				}
			}

			msg('회원정보가 수정되었습니다.', $sitelink->link_mobile('mem/myinfo.php'), 'parent');
		} else {
			msg('회원정보 수정이 실패되었습니다. 다시 시도해 주십시오.', -1);
		}

		break;
}
?>