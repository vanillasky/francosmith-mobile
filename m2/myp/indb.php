<?
@include dirname(__FILE__) . "/../lib/library.php";
@include $shopRootDir . "/lib/upload.lib.php";

$mode=$_POST['mode'];

### 후기 업로드 이미지 갯수 설정
if($cfg['reviewFileNum']){
	$reviewFileNum = $cfg['reviewFileNum'];
} else {
	$reviewFileNum = 1;
}

function spamFail() {
	global $_POST, $_SERVER;

	$str = "";
	$str .= "<script language='javascript'>";
	$str .= "alert('자동등록방지문자가 일치하지 않습니다. 다시 입력하여 주십시요.');";
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

if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
	$_POST = validation::xssCleanArray($_POST, array(
	    validation::DEFAULT_KEY => 'text',
	    'contents' => array('html', 'ent_quotes'),
		'subject'=>  array('html', 'ent_quotes'),
	    'password' => 'disable',
	    'mode' => 'disable',
	    'captcha_key' => 'disable',
	));
}

switch($mode) {
	case "add_review":
		# Anti-Spam 검증

		$switch = ($cfg['reviewSpamBoard']&1 ? '123' : '000') . ($cfg['reviewSpamBoard']&2 ? '4' : '0');
		$rst = antiSpam($switch, "goods/review_register.php", "post");
		if (substr($rst[code],0,1) == '4') spamFail();
		if ($rst[code] <> '0000') msg("무단 링크를 금지합니다.",-1);


		$query = "
		insert into ".GD_GOODS_REVIEW." set
			goodsno		= '$_POST[goodsno]',
			subject		= '$_POST[subject]',
			contents	= '$_POST[contents]',
			point		= '$_POST[point]',
			m_no		= '$sess[m_no]',
			name		= '$_POST[name]',
			password	= md5('$_POST[password]'),
			regdt		= now(),
			ip			= '$_SERVER[REMOTE_ADDR]',
			is_mobile	= 'y'
		";
		$db->query($query);
		$sno=$db->lastID();
	
		// 이미지 업로드
		$attach = 0;
		if ($_FILES['attach']['error'] === UPLOAD_ERR_OK) {	// UPLOAD_ERR_OK = 0
			if (is_uploaded_file($_FILES['attach'][tmp_name])){
				$data_path = $_SERVER['DOCUMENT_ROOT'].'/shop/data/review/';
				$filename = 'RV'.sprintf("%010s", $sno);
				$filename_tmp = $filename.'_tmp';
				$upload = new upload_file($_FILES['attach'],$data_path.$filename_tmp,'image');
				if (!$upload -> upload()){
					msg("이미지 파일만 업로드가 가능합니다",-1);
					exit;
				} else {
					$img_size = getimagesize( $data_path.$filename_tmp);
					if ($img_size[0] > 700) {
						thumbnail($data_path.$filename_tmp,$data_path.$filename,700);
					} else {
						copy($data_path.$filename_tmp,$data_path.$filename);
					}
					@unlink($data_path.$filename_tmp);
					$attach = 1;
				}
			}
		} else {
			$file_array = reverse_file_array($_FILES[file]);
			$data_path = $_SERVER['DOCUMENT_ROOT'].'/shop/data/review/';
			for ($i=0;$i<$reviewFileNum;$i++){
				if ($i == 0){
					$filename = 'RV'.sprintf("%010s", $sno);
				} else {
					$filename = 'RV'.sprintf("%010s", $sno).'_'.$i;
				}
				$filename_tmp = $filename.'_tmp';
				if ($_POST[del_file][$i]=="on"){
					@unlink($data_path.$filename);
				}
				if (is_uploaded_file($file_array[$i][tmp_name])){
					if ($filename){
						@unlink($data_path.$filename);
					}
					$upload = new upload_file($file_array[$i],$data_path.$filename_tmp,'image');
					if (!$upload -> upload()){
						msg("이미지 파일만 업로드가 가능합니다",-1);
						exit;
					} else {
						if ($cfg['reviewFileSize'] > 0){
							$reviewFileSize_byte = $cfg['reviewFileSize'] * 1024;
							if (filesize($data_path.$filename_tmp) > $reviewFileSize_byte){
								$db->query("delete from ".GD_GOODS_REVIEW." where sno='$sno'");
								for ($rm=0;$rm<=$i;$rm++){
									if ($rm == 0){
										$del_name = 'RV'.sprintf("%010s", $sno);
									} else {
										$del_name = 'RV'.sprintf("%010s", $sno).'_'.$rm;
									}
									@unlink($data_path.$del_name);
								}
								msg("최대 업로드 사이즈는 ".$cfg['reviewFileSize']."kb 입니다",-1);
								exit;
							}
						}
						$img_size = getimagesize($data_path.$filename_tmp);
						if ($img_size[0] > $cfg['reviewLimitPixel']) {
							thumbnail($data_path.$filename_tmp,$data_path.$filename,$cfg['reviewLimitPixel'],1000,1);
						} else {
							copy($data_path.$filename_tmp,$data_path.$filename);
						}
						@unlink($data_path.$filename_tmp);
						$attach = 1;
					}
				}
			}
		}

		$db->query("update ".GD_GOODS_REVIEW." set parent=sno, attach='$attach' where sno='$sno'");

		if ($_POST['goodsno']) {
			msg('정상적으로 등록되었습니다', '../goods/view.php?goodsno='.$_POST['goodsno'].'&view_area=review');
		}
		else {
			msg('정상적으로 등록되었습니다', '../goods/review.php');
		}

	case 'mod_review' :

		# Anti-Spam 검증

		$switch = ($cfg['reviewSpamBoard']&1 ? '123' : '000') . ($cfg['reviewSpamBoard']&2 ? '4' : '0');
		$rst = antiSpam($switch, "myp/review_register.php", "post");
		if (substr($rst[code],0,1) == '4') spamFail();
		if ($rst[code] <> '0000') msg("무단 링크를 금지합니다.",-1);


		### 접근체크
		list( $password ) = $db->fetch("select password from ".GD_GOODS_REVIEW." where sno = '$_POST[sno]'");
		if ( !isset($sess) && $password != md5($_POST[password]) ) msg($msg='비밀번호를 잘못 입력 하셨습니다.',$code=-1); // 회원전용 & 로그인전

		$attach_query = ", attach = '0'";

		$query = "
		update ".GD_GOODS_REVIEW." set
			subject		= '$_POST[subject]',
			contents	= '$_POST[contents]',
			point		= '$_POST[point]',
			name		= '$_POST[name]'
			$attach_query
		where sno = '$_POST[sno]'
		";
		$db->query($query);
		msg("정상적으로 수정되었습니다", '../goods/view.php?goodsno='.$_POST['goodsno'].'&view_area=review');
		break;

	case 'del_goodsview' :
		$goodsno = $_POST['goodsno'];

		$tmp_goods_idx = $_COOKIE['todayGoodsMobileIdx'];

		$arr_tmp_goods_idx = explode(',', $tmp_goods_idx);

		if(!empty($arr_tmp_goods_idx) && is_array($arr_tmp_goods_idx)) {
			foreach($arr_tmp_goods_idx as $goods_idx) {
				if($goods_idx != $goodsno) {
					$arr_goods_idx[] = $goods_idx;
				}
			}
		}

		$serialize_goods_data = $_COOKIE['todayGoodsMobile'];
		//unset($_COOKIE['todayGoodsMobileIdx'], $_COOKIE['todayGoodsMobile']);

		$goods_arr = unserialize(stripslashes($serialize_goods_data));

		if(!empty($goods_arr) && is_array($goods_arr)) {
			foreach($goods_arr as $goods_row) {
				if($goods_row['goodsno'] != $goodsno) {
					$goods_data[] = $goods_row;

				}
			}
		}

		$date = 1;


		setcookie('todayGoodsMobileIdx',implode(",",$arr_goods_idx),time()+3600*24*$date,'/');
		setcookie('todayGoodsMobile',serialize($goods_data),time()+3600*24*$date,'/');

		msg("삭제 되었습니다", "viewgoods.php", "parent");

		break;

	case 'add_member_qna' :


		$query = "
		insert into ".GD_MEMBER_QNA." set
			itemcd		= '$_POST[itemcd]',
			subject		= '$_POST[subject]',
			contents	= '$_POST[contents]',
			m_no		= '$sess[m_no]',
			email		= '$_POST[email]',
			mobile		= '$mobile',
			mailling	= '$mailling',
			sms			= '$sms',
			ordno		= '$_POST[ordno]',
			regdt		= now(),
			ip			= '$_SERVER[REMOTE_ADDR]'
		";
		$db->query($query);

		$db->query("update ".GD_MEMBER_QNA." set parent=sno where sno='" . $db->lastID() . "'");

		msg('정상적으로 등록되었습니다', $sitelink->link_mobile("myp/qna.php","regular"));

		break;
		case 'recoverCoupon' :
			if($_POST['ordno']) {
				restore_coupon($_POST['ordno']);
				go($_SERVER[HTTP_REFERER]);
			}
		break;
}
?>