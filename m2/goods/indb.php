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

switch($mode) {
	case "add_qna":
		@include $shopRootDir ."/conf/config.php";
		# Anti-Spam 검증
		$switch = ($cfg['qnaSpamBoard']&1 ? '123' : '000') . ($cfg['qnaSpamBoard']&2 ? '4' : '0');
		$rst = antiSpam($switch, "goods/goods_qna_register.php", "post");
		if (substr($rst[code],0,1) == '4') spamFail();
		if ($rst[code] <> '0000') msg("무단 링크를 금지합니다.",-1);

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
			//무료보안서버
			$redirectUrl = $sitelink->link_mobile("goods/view.php?goodsno=".$_POST['goodsno']."&view_area=qna","regular");
		}
		else{
			//무료보안서버
			$redirectUrl = $sitelink->link_mobile("goods/goods_qna_list.php?isAll=".$_POST['isAll'],"regular");
		}
		msg('정상적으로 등록되었습니다', $redirectUrl);
		exit;
	break;

	case "del_review":

		$sno = ($_POST[sno] ? $_POST[sno] : $_GET[sno] );
		$insert_password = md5($_POST[password]);

		//sno 데이터 타입 검증
		if(!filter_var($sno, FILTER_VALIDATE_INT)){
			msg("잘못된 접근입니다.",-1);
			exit;
		}

		//회원 본인이 작성한 글인지 확인
		list( $m_no ) = $db->fetch("select m_no from ".GD_GOODS_REVIEW." where sno = '$sno'");
		if(isset($sess) && $sess[level] < 80){
			if($sess[m_no] != $m_no){
				msg("권한이 없습니다.",-1);
				exit;
			}
		}
		//비회원이 회원이 작성한 글을 삭제하려 할 경우
		else if(!isset($sess) && $m_no > 0){
			msg("권한이 없습니다.",-1);
			exit;
		}
		//비회원글 삭제 비밀번호 확인
		else if(!isset($sess) && $m_no < 1){
			list( $password ) = $db->fetch("select password from ".GD_GOODS_REVIEW." where sno = '$sno'");
			if($insert_password != $password){
				msg("비밀번호가 틀렸습니다.",-1);
				exit;
			}
		}
		//댓글이 있는 글을 삭제하려할 경우
		list( $chk_parent ) = $db->fetch("select count(*) from ".GD_GOODS_REVIEW." where parent = '$sno'");
		if($chk_parent > 1){
			msg("권한이 없습니다.",-1);
			exit;
		}
		daum_goods_review($sno);	// 다음 상품평 DB 저장
		$query = "delete from ".GD_GOODS_REVIEW." where sno = '$sno'";
		$db->query($query);

		// 이미지 업로드
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

		//DB Cache 초기화 141030
		$dbCache = Core::loader('dbcache');
		$dbCache->clearCache('goodsview_review');

		// 페이지캐시 초기화
		$templateCache = Core::loader('TemplateCache');
		$templateCache->clearCacheByClass('goods_review');

		confirm_reload("정상적으로 삭제되었습니다.");

		break;

	case "del_qna":

		$sno = ($_POST[sno] ? $_POST[sno] : $_GET[sno] );
		$insert_password = md5($_POST[password]);

		//sno 데이터 타입 검증
		if(!filter_var($sno, FILTER_VALIDATE_INT)){
			msg("잘못된 접근입니다.",-1);
			exit;
		}

		list( $m_no,$password,$parent ) = $db->fetch("select m_no,password,parent from ".GD_GOODS_QNA." where sno = '$sno'");

		//회원 본인이 작성한 글인지 확인
		if ( isset($sess) && $sess[level] < 80){
			if($sess[m_no] != $m_no){
				msg("권한이 없습니다.",-1);
				exit;
			}
		}
		//비회원이 회원이 작성한 글을 삭제하려할 경우
		else if(!isset($sess) && $m_no > 0){
			msg("권한이 없습니다.",-1);
			exit;
		}
		//비회원글 삭제 비밀번호 확인
		else if(!isset($sess) && $m_no < 1){
			if($insert_password != $password){
				msg("비밀번호가 틀렸습니다.",-1);
				exit;
			}
		}

		$query = "delete from ".GD_GOODS_QNA." where sno = '$sno'";
		if($parent == $sno){
			list($cnt) = $db->fetch("select count(*) from ".GD_GOODS_QNA." where parent='$parent'");
			if($cnt > 1) $query = "update ".GD_GOODS_QNA." set subject='삭제된 게시물 입니다.',contents='삭제된 게시물 입니다.' where sno='".$sno."'";
		}
		$db->query($query);

		//DB Cache 초기화 141030
		$dbCache = Core::loader('dbcache');
		$dbCache->clearCache('goodsview_qna');

		// 페이지캐시 초기화
		$templateCache = Core::loader('TemplateCache');
		$templateCache->clearCacheByClass('goods_qna');

		confirm_reload("정상적으로 삭제되었습니다.");
		break;

}

?>