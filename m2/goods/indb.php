<?

@include dirname(__FILE__) . "/../lib/library.php";

function confirm_reload($str,$url='')
{
	if($url){
		echo "
		<script>
		alert('$str');
		if (opener){ opener.location.reload(); window.close(); }
		else location.href='$url';
		</script>
		";
	}else{
		echo "
		<script>
		alert('$str');
		if (opener){ opener.location.reload(); window.close(); }
		else parent.location.reload();
		</script>
		";
	}
	exit;
}

$mode = ($_POST[mode] ? $_POST[mode] : $_GET[mode] );

if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
	$_POST = validation::xssCleanArray($_POST, array(
		validation::DEFAULT_KEY => 'text',
		'contents' => array('html', 'ent_quotes'),
		'subject' => array('html', 'ent_quotes'),
		'password' => 'disable'
	));
}

function spamFail() {
	global $_POST, $_SERVER;

	$str = "";
	$str .= "<script language='javascript'>";
	$str .= "alert('�ڵ���Ϲ������ڰ� ��ġ���� �ʽ��ϴ�. �ٽ� �Է��Ͽ� �ֽʽÿ�.');";
	$str .= "window.onload = function() { rtnForm.submit(); }";
	$str .= "</script>";
	$str .= "<div style='display:none;'>";
	$str .= "<form name='rtnForm' method='post' action='".$_SERVER['HTTP_REFERER']."'>";
	$str .= "<input type='text' name='email' value='".$_POST['email']."' />";
	$str .= "<input type='text' name='phone' value='".$_POST['phone']."' />";
	$str .= "<input type='text' name='secret' value='".$_POST['secret']."' />";
	$str .= "<input type='text' name='point' value='".$_POST['point']."' />";
	$str .= "<input type='text' name='subject' value='".$_POST['subject']."' />";
	$str .= "<input type='text' name='name' value='".$_POST['name']."' />";
	$str .= "<textarea name='contents'>".$_POST['contents']."</textarea>";
	$str .= "</form>";
	$str .= "</div>";
	exit($str);
}

switch($mode) {
	case "add_qna":
		@include $shopRootDir ."/conf/config.php";
		# Anti-Spam ����
		$switch = ($cfg['qnaSpamBoard']&1 ? '123' : '000') . ($cfg['qnaSpamBoard']&2 ? '4' : '0');
		$rst = antiSpam($switch, "goods/goods_qna_register.php", "post");
		if (substr($rst[code],0,1) == '4') spamFail();
		if ($rst[code] <> '0000') msg("���� ��ũ�� �����մϴ�.",-1);

		$query = "
		insert into ".GD_GOODS_QNA." set
			goodsno		= '$_POST[goodsno]',
			subject		= '$_POST[subject]',
			contents	= '$_POST[contents]',
			m_no		= '$sess[m_no]',
			name		= '$_POST[name]',
			password	= md5('$_POST[password]'),
			regdt		= now(),
			secret		= '".$_POST['secret']."',
			ip			= '$_SERVER[REMOTE_ADDR]',
			email		= '$_POST[email]',
			phone		= '$_POST[phone]',
			rcv_sms		= '$_POST[rcv_sms]',
			rcv_email	= '$_POST[rcv_email]'
		";
		$db->query($query);

		$db->query("update ".GD_GOODS_QNA." set parent=sno where sno='" . $db->lastID() . "'");
		
		if($_POST['goodsno']){
			//���Ẹ�ȼ���
			$redirectUrl = $sitelink->link_mobile("goods/view.php?goodsno=".$_POST['goodsno']."&view_area=qna","regular");
		}
		else{
			//���Ẹ�ȼ���
			$redirectUrl = $sitelink->link_mobile("goods/goods_qna_list.php?isAll=".$_POST['isAll'],"regular");
		}
		msg('���������� ��ϵǾ����ϴ�', $redirectUrl);
		exit;
	break;

	case "del_review":

		$sno = ($_POST[sno] ? $_POST[sno] : $_GET[sno] );
		$insert_password = md5($_POST[password]);

		//sno ������ Ÿ�� ����
		if(!filter_var($sno, FILTER_VALIDATE_INT)){
			msg("�߸��� �����Դϴ�.",-1);
			exit;
		}

		//ȸ�� ������ �ۼ��� ������ Ȯ��
		list( $m_no ) = $db->fetch("select m_no from ".GD_GOODS_REVIEW." where sno = '$sno'");
		if(isset($sess) && $sess[level] < 80){
			if($sess[m_no] != $m_no){
				msg("������ �����ϴ�.",-1);
				exit;
			}
		}
		//��ȸ���� ȸ���� �ۼ��� ���� �����Ϸ� �� ���
		else if(!isset($sess) && $m_no > 0){
			msg("������ �����ϴ�.",-1);
			exit;
		}
		//��ȸ���� ���� ��й�ȣ Ȯ��
		else if(!isset($sess) && $m_no < 1){
			list( $password ) = $db->fetch("select password from ".GD_GOODS_REVIEW." where sno = '$sno'");
			if($insert_password != $password){
				msg("��й�ȣ�� Ʋ�Ƚ��ϴ�.",-1);
				exit;
			}
		}
		//����� �ִ� ���� �����Ϸ��� ���
		list( $chk_parent ) = $db->fetch("select count(*) from ".GD_GOODS_REVIEW." where parent = '$sno'");
		if($chk_parent > 1){
			msg("������ �����ϴ�.",-1);
			exit;
		}
		daum_goods_review($sno);	// ���� ��ǰ�� DB ����
		$query = "delete from ".GD_GOODS_REVIEW." where sno = '$sno'";
		$db->query($query);

		// �̹��� ���ε�
		$data_path = "../data/review";
		for($i=0;$i<10;$i++){
			if($i == 0){
				$name = 'RV'.sprintf("%010s", $sno);
			} else {
				$name = 'RV'.sprintf("%010s", $sno).'_'.$i;
			}
			if (file_exists($data_path."/".$name)){
				@unlink($data_path."/".$name);
			}
		}

		//DB Cache �ʱ�ȭ 141030
		$dbCache = Core::loader('dbcache');
		$dbCache->clearCache('goodsview_review');

		// ������ĳ�� �ʱ�ȭ
		$templateCache = Core::loader('TemplateCache');
		$templateCache->clearCacheByClass('goods_review');

		confirm_reload("���������� �����Ǿ����ϴ�.");

		break;

	case "del_qna":

		$sno = ($_POST[sno] ? $_POST[sno] : $_GET[sno] );
		$insert_password = md5($_POST[password]);

		//sno ������ Ÿ�� ����
		if(!filter_var($sno, FILTER_VALIDATE_INT)){
			msg("�߸��� �����Դϴ�.",-1);
			exit;
		}

		list( $m_no,$password,$parent ) = $db->fetch("select m_no,password,parent from ".GD_GOODS_QNA." where sno = '$sno'");

		//ȸ�� ������ �ۼ��� ������ Ȯ��
		if ( isset($sess) && $sess[level] < 80){
			if($sess[m_no] != $m_no){
				msg("������ �����ϴ�.",-1);
				exit;
			}
		}
		//��ȸ���� ȸ���� �ۼ��� ���� �����Ϸ��� ���
		else if(!isset($sess) && $m_no > 0){
			msg("������ �����ϴ�.",-1);
			exit;
		}
		//��ȸ���� ���� ��й�ȣ Ȯ��
		else if(!isset($sess) && $m_no < 1){
			if($insert_password != $password){
				msg("��й�ȣ�� Ʋ�Ƚ��ϴ�.",-1);
				exit;
			}
		}

		$query = "delete from ".GD_GOODS_QNA." where sno = '$sno'";
		if($parent == $sno){
			list($cnt) = $db->fetch("select count(*) from ".GD_GOODS_QNA." where parent='$parent'");
			if($cnt > 1) $query = "update ".GD_GOODS_QNA." set subject='������ �Խù� �Դϴ�.',contents='������ �Խù� �Դϴ�.' where sno='".$sno."'";
		}
		$db->query($query);

		//DB Cache �ʱ�ȭ 141030
		$dbCache = Core::loader('dbcache');
		$dbCache->clearCache('goodsview_qna');

		// ������ĳ�� �ʱ�ȭ
		$templateCache = Core::loader('TemplateCache');
		$templateCache->clearCacheByClass('goods_qna');

		confirm_reload("���������� �����Ǿ����ϴ�.");
		break;

}

?>