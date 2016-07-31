<?
/*********************************************************
* ���ϸ�     :  /mem/nomember_order.php
* ���α׷��� :	����ϼ� ȸ�� ����
* �ۼ���     :  dn
* ������     :  2012.08.14
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

		// ȸ�����忡�� ��14�� �̸� ȸ������ ������
		$mUnder14 = Core::loader('memberUnder14Join');
		if ($_POST['birth']) $birthday = trim(sprintf("%04d%02d%02d",$_POST['birth_year'],$_POST['birth'][0],$_POST['birth'][1]));
		$under14Code = $mUnder14->joinIndb($birthday);
		$under14 = 0;
		if ( $under14Code == 'rejectJoin' ) { // ��14�� �̸� ȸ������ �ź�
			msg('�� 14�� �̸��� ��� ȸ�������� ������� �ʽ��ϴ�.', -1);
		}
		else if ( $under14Code == 'undecidableRejectJoin' ) { // ��14�� �̸� �ǴܺҰ��� ȸ������ �ź�
			msg('��14�� �̸��� Ȯ���� �� �����Ƿ� ȸ�������� ������� �ʽ��ϴ�. �����ڿ��� ������ �ּ���.', -1);
		}
		else if ( $under14Code == 'adminStatus' ) { // ��14�� �̸� ȸ������ ������ ���� �� ����
			$joinset['status'] = 0;
			$under14 = 1;
		}
		else if ( $under14Code == 'undecidableAdminStatus' ) { // ��14�� �̸� �ǴܺҰ��� ȸ������ ������ ���� �� ����
			$joinset['status'] = 0;
		}
		else if ( $under14Code == 'over14' ) { // ��14�� �̻�
			$under14 = 2;
		}

		### ���̵� �Է�����
		if (preg_match('/^[a-zA-Z0-9_-]{6,16}$/',$_POST['m_id']) == 0) msg('���̵� �Է� ���� �����Դϴ�', -1);

		//�н����� �Է�����
		if($_POST['passwordSkin'] === 'Y'){
			if(passwordPatternCheck($_POST['password']) === false) msg('10~16���� ������ҹ���,����,Ư�����ڸ� �����Ͽ� ����� �� �ֽ��ϴ�.', -1);
		}

		### �ź� ���̵� ���͸�
		if (find_in_set(strtoupper($_POST[m_id]),strtoupper($joinset[unableid]))) msg("��� �Ұ����� ���̵��Դϴ�", -1);

		### �ʼ��� üũ
		@include $shopRootDir ."/conf/mobile_fieldset.php";

		if (!$_POST['m_id'] || !$_POST['password'] || !$_POST['name']) {
			msg('ȸ�� ���Խ�, ȸ�����̵�, ��й�ȣ, �̸��� �ʼ� �Է°��Դϴ�. �ٽ� �õ��� �ּ���', 'join.php', 'parent');
		}

		### ���̵� �ߺ����� üũ
		list ($chk) = $db->fetch("select m_id from ".GD_MEMBER." where m_id='$_POST[m_id]'");
		if ($chk) msg("�̹� ��ϵ� ���̵��Դϴ�",'join.php');

		### �̸��� �ߺ����� üũ
		if (isset($_POST['email']) === true) {
			list ($chk) = $db->fetch("select email from ".GD_MEMBER." where email='".$_POST['email']."'");
			if(!$chk){
				list($chk) = $dormant->checkDormantEmail($_POST['email']);
			}
			if ($chk) msg("�̹� ��ϵ� �̸����Դϴ�",'join.php');
		}

		### �г��� �ߺ����� üũ
		if (isset($_POST['nickname']) === true) {
			list ($chk) = $db->fetch("select nickname from ".GD_MEMBER." where nickname='".$_POST['nickname']."'");
			if(!$chk){
				$chk = $dormant->getDormantInfo('checkNickname', $_POST);
			}
			if ($chk) msg("�̹� ��ϵ� �г����Դϴ�",'join.php');
		}

		# ������ �߰�
		if ($_POST[rncheck] == "ipin") {
			### dupeinfo �� �ʵ� dupeinfo ���� ���Ѵ�.
			list ($chk) = $db->fetch("select count(*) as cnt from ".GD_MEMBER." where dupeinfo = '".$_POST['dupeinfo']."'");
			if ($chk < 1) {
				$chk = $dormant->getCountDupeinfoFromDormant($_POST['dupeinfo']);
			}
			if ($chk > 0) {
				msg("�̹� ȸ������� ���Դϴ�.", 'join.php');
			}
		} else {
			### dupeinfo �����ε� üũ�Ѵ�.
			if ($_POST['dupeinfo']) {
				list ($chk) = $db->fetch("select count(*) as cnt from ".GD_MEMBER." where dupeinfo = '".$_POST['dupeinfo']."'");
				if ($chk < 1) {
					$chk = $dormant->getCountDupeinfoFromDormant($_POST['dupeinfo']);
				}
				if ($chk > 0) {
					msg("�̹� ȸ������� ���Դϴ�.", 'join.php');
				}
			}
		}

		### ȸ���簡�ԱⰣ üũ
		if ( $joinset[rejoin] > 0 ){
			$rejoindt = date('Ymd', time() - (($joinset[rejoin]-1)*86400));
			if ($_POST['dupeinfo']) {
				list ($chk) = $db->fetch("select regdt from ".GD_LOG_HACK." where dupeinfo='".$_POST['dupeinfo']."' and date_format( regdt, '%Y%m%d' ) >={$rejoindt} order by regdt desc limit 1");
				if($_POST['dupeinfo'] && $chk) msg("ȸ��Ż�� �� {$joinset[rejoin]}�� ���� �簡���� �� �����ϴ�.\\nȸ������ {$chk}�� Ż���ϼ̽��ϴ�.",-1);
			}
		}

		### ���� �缳��
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
					msg('�ʼ��Է°�('.$key.')�� �����Ǿ� �ֽ��ϴ�.  �ٽ� �õ����ּ���.', 'join.php', 'parent');
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

		//�߰������׸� ���ǿ���
		if (is_array($_POST['consent']) && count($_POST['consent'])>0){
			foreach ($_POST['consent'] as $key => $value){
				$query = "INSERT INTO ".GD_MEMBER_CONSENT." SET m_no = '".$m_no."', consent_sno = '".$key."', consentyn = '".$value."', regdt=now()";
				$db->query($query);
			}
		}

		if($m_no) {
			### ������ ���� �Է�
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

			### ��õ�� ������ üũ shop/member/indb.php�� ���� ����
			if($checked_mobile['useField']['recommid'] == "checked" && $_POST['recommid']){


				# �ڱ� �ڽ��� ��õ�� ���ϰ� ó��
				if( $_POST['recommid'] != $_POST['m_id'] ){

					list ($recomm_m_id,$recomm_m_no) = $db->fetch("select m_id,m_no from ".GD_MEMBER." where m_id='".$_POST['recommid']."'");

					# ��õ���� �ִ°��
					if($recomm_m_id){

						# ��õ�ο��� ������ ����
						if($joinset['recomm_emoney'] > 0){
							$dormantMember = false;
							$dormantMemberDataArray = array('m_id' => $_POST['recommid']);
							$dormantMember = $dormant->checkDormantMember($dormantMemberDataArray, 'm_id');

							$query = "
							insert into ".GD_LOG_EMONEY." set
								m_no	= '".$recomm_m_no."',
								emoney	= '".$joinset['recomm_emoney']."',
								memo	= '".$_POST['m_id']." ȸ���� ��õ���� ����Ʈ ����',
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

						# ��õ�ѻ��(������) ������ ����
						if($joinset['recomm_add_emoney'] > 0){
							$query = "
							insert into ".GD_LOG_EMONEY." set
								m_no	= '".$m_no."',
								emoney	= '".$joinset['recomm_add_emoney']."',
								memo	= '".$_POST['recommid']." ȸ���� ��õ�Ͽ� ����Ʈ ����',
								regdt	= now()
							";
							$db->query($query);

							$strSQL = "UPDATE ".GD_MEMBER." SET emoney = emoney + '".$joinset['recomm_add_emoney']."' WHERE m_no = '".$m_no."'";
							$db->query($strSQL);
						}

					# ��õ���� ���°��
					} else {
						$query = "
						insert into ".GD_LOG_EMONEY." set
							m_no	= '".$m_no."',
							emoney	= '0',
							memo	= '��õ�ξ��̵��� ������ �����ȵ�',
							regdt	= now()
						";
						$db->query($query);
					}
				}
			}

            ### ȸ������SMS
			if (strlen($mobile)>7) {
                		sendSmsCase('join',$mobile);
			}

			### ȸ�����Ը���
			if ( $_POST[email] && $cfg[mailyn_10] == 'y' )
			{
				// ���Űźμ��� ����
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

			### ȸ���������� �߱�
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
				msg('������ ������ ����ó���˴ϴ�\n Ȩ���� �̵��Ͽ�\n������ ��� �̿��� �ּ���', $sitelink->link_mobile('mem/join.php?mode=endjoin'), 'parent');
			}
			else {
				$result = $session->login($_POST['m_id'],$_POST['password']);
				member_log( $session->m_id );

				msg('ȸ�� ���ԵǾ����ϴ�.\nȨ���� �̵��Ͽ�\n������ ��� �̿��� �ּ���', $sitelink->link_mobile('mem/join.php?mode=endjoin'), 'parent');
			}


		}
		else {
			msg('ȸ�� ������ ������ �߻��߽��ϴ�. �ٽ� �õ��� �ּ���', 'join.php', 'parent');
		}
		break;

	case 'member_addinfo' :

		$upd_arr = array();

		### ���� �缳��
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
			msg('�߰����� �Է��� ������ �߻��߽��ϴ�. PC ȭ�鿡�� �ٽ� �õ��� �ּ���', '/m2/index.php', 'parent');
		}

		break;
		case 'confirm_password' :
			if ($_POST['password']) {
				$checkQuery = "SELECT COUNT(m_id) FROM ".GD_MEMBER." WHERE m_id = '".$sess['m_id']."' AND password in (PASSWORD('".$_POST['password']."'),OLD_PASSWORD('".$_POST['password']."'),MD5('".$_POST['password']."'))";
				list($_SESSION['sess']['confirm_password']) = $db->fetch($checkQuery);
			}
			if (!$_SESSION['sess']['confirm_password']) {
				msg('��й�ȣ�� ��Ȯ�ϰ� �Է��� �ּ���.', 'myinfo.php', 'parent');
			} else {
				go($sitelink->link_mobile('mem/myinfo.php'), 'parent');
			}
			break;
		case 'modMember' :
			include $shopRootDir."/conf/fieldset.php";
			@include $shopRootDir."/conf/mobile_fieldset.php";
			$dormant = Core::loader('dormant');

		### �ߺ� �α��� ���� üũ
		if ($_POST['m_id'] != $sess['m_id']) msg('�α��� ���� ������ �ߺ��Ǿ����ϴ�. �ٽ� �α����Ͽ� �ֽʽÿ�.','./logout.php');

		### ���� �缳��
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
					msg('�ʼ��Է°�('.$key.')�� �����Ǿ� �ֽ��ϴ�.  �ٽ� �õ����ּ���.', -1);
				}
			}
		}

		### ��й�ȣ �˻�
		if($_POST['newPassword'] && $_POST['pwd_chk'] == 'y') {
			list ($chk) = $db->fetch("SELECT COUNT(m_id) FROM ".GD_MEMBER." WHERE m_id = '".$_POST['m_id']."' AND password in (PASSWORD('".$_POST['originalPassword']."'),OLD_PASSWORD('".$_POST['originalPassword']."'),MD5('".$_POST['originalPassword']."'))");
			if (!$chk) msg('���� ��й�ȣ�� ��Ȯ�ϰ� �Է��Ͽ� �ּ���.', -1);

			//�н����� �Է�����
			if($_POST['passwordSkin'] === 'Y'){
				if(passwordPatternCheck($_POST['newPassword']) === false) msg('10~16���� ������ҹ���,����,Ư�����ڸ� �����Ͽ� ����� �� �ֽ��ϴ�.', -1);
			} else {
				// ��� ���� ���� (6�� �̻� 21~7E ���� ascii)
				if (!preg_match('/^[\x21-\x7E]{6,}$/',$_POST['newPassword'])) msg('��й�ȣ�� 6�� �̻� �̾�� �մϴ�.', -1);
			}

			$password_query = " ,password = password('".$_POST['newPassword']."'), password_moddt = NOW() ";
		}
		else {
			$password_query = '';
		}

		### �г��� �ߺ����� üũ
		if($ins_arr['nickname']) {
			list ($chk) = $db->fetch("select nickname from ".GD_MEMBER." where nickname='".$ins_arr['nickname']."' and m_id != '".$_POST['m_id']."'");
			if(!$chk){
				$chk = $dormant->getDormantInfo('checkNickname', $_POST);
			}
			if ($chk) msg('�̹� ��ϵ� �г����Դϴ�. �ٽ� �ۼ��� �ֽʽÿ�.', -1);
		}

		### �̸��� �ߺ����� üũ
		if($ins_arr['email']) {
			list ($chk) = $db->fetch("select email from ".GD_MEMBER." where email='".$ins_arr['email']."' and m_id != '".$_POST['m_id']."'");
			if(!$chk){
				list($chk) = $dormant->checkDormantEmail($ins_arr['email'], 'email');
			}
			if ($chk) msg('�̹� ��ϵ� �̸����Դϴ�. �ٽ� �ۼ��� �ֽʽÿ�.', -1);
		}

		// ���ŵ��Ǽ��� �ȳ�����
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

			// ���ŵ��Ǽ��� �ȳ�����
			if($sendAcceptAgreeMail === true && function_exists('sendAcceptAgreeMail')){
				sendAcceptAgreeMail($ins_arr['email'], $ins_arr['mailling'], $ins_arr['sms']);
			}

			//�߰������׸� ���ǿ���
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

			// ȸ������ ���� �̺�Ʈ
			$info_cfg = $config->load('member_info');

			if ($info_cfg['event_use'] && (int)$info_cfg['event_emoney'] > 0) {
				$now = date('Y-m-d H:i:s');
				if ( $now >= $info_cfg['event_start_date'] && $now <= $info_cfg['event_end_date'] ) {

					$query = "select count(*) from ".GD_MEMBER." where m_no = ".$sess['m_no']." and regdt < '".$info_cfg['event_start_date']."'";	//�̺�Ʈ ���� �����ߴ��� üũ
					list($isEvent) = $db->fetch($query);
					if($isEvent > 0){
						// ���� ����
						$query = sprintf("SELECT count(sno) from ".GD_LOG_EMONEY." where m_no = %d and memo = 'ȸ������ ���� �̺�Ʈ' and regdt between '%s' and '%s'",$sess['m_no'], $info_cfg['event_start_date'], $info_cfg['event_end_date'] );
						list($history) = $db->fetch($query);
						if ($history < 1) {

							$query = sprintf("update ".GD_MEMBER." set emoney = emoney + %d where m_no = %d", $info_cfg['event_emoney'], $sess['m_no']);
							$db->query($query);

							$query = sprintf("insert into ".GD_LOG_EMONEY." set m_no = %d, ordno = '', emoney = %d, memo = 'ȸ������ ���� �̺�Ʈ', regdt = '%s'", $sess['m_no'], $info_cfg['event_emoney'], $now);
							$db->query($query);

							msg('"ȸ���������� �̺�Ʈ"\n\n������ '.number_format($info_cfg['event_emoney']).'���� ���޵Ǿ����ϴ�.', $sitelink->link_mobile('mem/myinfo.php'), 'parent');
							break(1);	// break case "modMember"
						}
					}
				}
			}

			msg('ȸ�������� �����Ǿ����ϴ�.', $sitelink->link_mobile('mem/myinfo.php'), 'parent');
		} else {
			msg('ȸ������ ������ ���еǾ����ϴ�. �ٽ� �õ��� �ֽʽÿ�.', -1);
		}

		break;
}
?>