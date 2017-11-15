<?php
	

	include dirname(__FILE__) . "/../_header.php";
	@include $shopRootDir . "/conf/config.pay.php";
	@include $shopRootDir . "/conf/sns.cfg.php";
	@include $shopRootDir . "/conf/coupon.php";
	@include $shopRootDir."/lib/goods_qna.lib.php";
	@include $shopRootDir . '/lib/Lib_Robot.php';
	@include $shopRootDir . '/conf/config.checkout_review.php';

	$jQueryUiCssPath =  '../lib/js/jquery.ui.1.8.5/jquery-ui.css';
	$jQueryUiPath =  '../lib/js/jquery.ui.1.8.5/jquery-ui.js';
	$jQueryHashtagJsPath = $cfg['rootDir'] . '/proc/hashtag/hashtagControl.js?actTime='.time();

### 관련 상품 설정 가져오기
if (is_file($shopRootDir . "/conf/config.related.goods.php")) include $shopRootDir . "/conf/config.related.goods.php";
else {
	// 기본 설정 값
	$cfg_related['horizontal'] =  5;
	$cfg_related['vertical'] =  1;
	$cfg_related['size'] =  $cfg[img_s];

	$cfg_related['dp_image'] = 1;	// 고정
	$cfg_related['dp_goodsnm'] =  1;
	$cfg_related['dp_price'] = 1;
	$cfg_related['dp_shortdesc'] = $cfg[img_s];

	$cfg_related['use_cart'] = 0;
	$cfg_related['cart_icon'] = 1;

	$cfg_related['exclude_soldout'] =  0;
	$cfg_related['link_type'] = 'self';
}

### 후기 업로드 이미지 갯수 설정
if($cfg['reviewFileNum']){
	$reviewFileNum = $cfg['reviewFileNum'];
} else {
	$reviewFileNum = 1;
}

try {

	$goodsHelper   = Clib_Application::getHelperClass('front_goods');

	$goodsModel    = $goodsHelper->getGoodsModel(Clib_Application::request()->get('goodsno'));
	if (!$goodsModel->hasLoaded()) {
		throw new Clib_Exception('상품정보가 없습니다.');
	}
	else if (!$goodsModel->getOpenMobile()) {
		throw new Clib_Exception('해당상품은 진열이 허용된 상품이 아닙니다.');
	}

	$categoryModel = $goodsHelper->getCategoryModel(Clib_Application::request()->get('category'), $goodsModel);

	// 성인 인증이 필요한 상품일 경우
	if ($goodsModel->getUseOnlyAdult() && !Clib_Application::session()->canAccessAdult()) {
		Clib_Application::response()->redirect(
			$goodsHelper->getGoodsViewUrlMobile($goodsModel)
		);
	}

	$goodsno = $goodsModel->getId();

	// 카테고리 정보 설정, 접근 권한 체크
	if (!$goodsHelper->canAccessLinkedCategory($goodsModel)) {
		throw new Clib_Exception('이용 권한이 없습니다.\\n회원등급이 낮거나 회원가입이 필요합니다.');
	}

	// 페이지뷰 카운팅
	if (Lib_Robot::isRobotAccess() === false) {
		$db->silent(true);
		$db->query("INSERT INTO ".GD_GOODS_PAGEVIEW." SET date = CURDATE(), goodsno = $goodsno, cnt = 1 ON DUPLICATE KEY UPDATE cnt = cnt + 1");
		$db->silent();
	}

	$data = $goodsHelper->getGoodsDataArray($goodsModel, $categoryModel);
	
	// 이미지 추가 (l_img 오버라이드 시켜서 모바일용 이미지 처리)
	if ($data['use_mobile_img'] === '1') {
		$data['l_img'] = explode('|', $data['img_y']);
	} else if ($data['use_mobile_img'] === '0') {
		$data['l_img'] = explode('|', $data[$data['img_pc_y']]);
	}

	//kakaoTalk Link 3.5
	if($data['kakaoTalkLinkScript']){
		$customHeader .= $data['kakaoTalkLinkScript'];
	}

	// 상품 후기 가져오기
	$review_where[] = "a.goodsno = '$goodsno'";
	$review_where[] = "a.sno = a.parent";

	$pg = new Page(1,10);
	$pg->field = "a.sno, a.goodsno, a.subject, a.contents, a.point, a.regdt, a.name, b.m_no, b.m_id, a.attach, a.parent";
	$db_table = "".GD_GOODS_REVIEW." a left join ".GD_MEMBER." b on a.m_no=b.m_no";

	$pg->setQuery($db_table,$review_where,$sort="a.sno desc, a.regdt desc");
	$pg->exec();

	//DB Cache 사용 141030
	$dbCache = Core::loader('dbcache')->setLocation('mobile_goodsview_review');

	if (!$review_loop = $dbCache->getCache($pg->query)) {

		$res = $db->query($pg->query);

		$review_cnt = 0;
		while ($review_data=$db->fetch($res)){

			if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
				$review_data = validation::xssCleanArray($review_data, array(
					validation::DEFAULT_KEY => 'text',
					'contents' => array('html', 'ent_noquotes'),
				));
			}

			$review_data['idx'] = $pg->idx--;
			$review_data[contents] = nl2br(htmlspecialchars($review_data[contents]));
			$review_data[point] = sprintf( "%0d", $review_data[point]);

			$query = "select b.goodsnm,b.img_s,c.price
			from
				".GD_GOODS." b
				left join ".GD_GOODS_OPTION." c on b.goodsno=c.goodsno and link and go_is_deleted <> '1' and go_is_display = '1'
			where
				b.goodsno = '" . $data[goodsno] . "'";
			list( $review_data[goodsnm], $review_data[img_s], $review_data[price] ) = $db->fetch($query);

			$reply_query = "SELECT subject, contents, regdt, sno, m_no FROM ".GD_GOODS_REVIEW." WHERE parent='".$review_data[sno]."' AND sno != parent";

			$reply_res = $db->_select($reply_query);

			$review_data['reply'] =$reply_res;

			$review_data['reply_cnt'] = count($reply_res);


			if ($review_data[attach] == 1) {
				$data_path = "../../shop/data/review";
				for($ii=0;$ii<10;$ii++){
					if($ii == 0){
						$name = 'RV'.sprintf("%010s", $review_data[sno]);
					} else {
						$name = 'RV'.sprintf("%010s", $review_data[sno]).'_'.$ii;
					}
					if(file_exists($data_path.'/'.$name)){
						$review_data[image] .= "<img src='".$data_path."/".$name."'><br>";
					}
				}
			}
			else $review_data[image] = '';

			$review_data['review_name'] = '';

			/* encoding 고려하여 수정 해야 함 */
			if($review_data['name']) {
				$tmp_name = $review_data['name'];
			}
			else {
				$tmp_name = $review_data['m_id'];
			}

			if(!preg_match('/[0-9A-Za-z]/', substr($tmp_name, 0, 1))) {
				$division_num = 2;
			}
			else {
				$division_num = 1;
			}

			$review_data['review_name'] = substr($tmp_name, 0, $division_num).implode('', array_fill(0, intval((strlen($tmp_name) -1)/$division_num), '*'));


			for($i=0; $i<5; $i++) {

				if($i < $review_data['point']) {
					$review_data['point_star'] .= '<span class="active">★</span>';
				}
				else {
					$review_data['point_star'] .= '★';
				}
			}

			$review_data[authdelete] = 'Y'; # 삭제 권한초기값

			if ( empty($cfg['reviewWriteAuth']) || isset($sess) || !empty($review_data[m_no]) ){ // 회원전용 or 회원 or 작성자==회원
				$review_data[authdelete] = ( isset($sess) && $sess[m_no] == $review_data[m_no] ? 'Y' : 'N' );
			}

			if(!empty($review_data['reply'])){
				for($i=0; $i<$review_data['reply_cnt']; $i++){
					$review_data['reply'][$i][authdelete] = 'Y';
					if ( empty($cfg['reviewWriteAuth']) || isset($sess) || !empty($review_data['reply'][$i][m_no]) ){
						$review_data['reply'][$i][authdelete] = ( isset($sess) && $sess[m_no] == $review_data['reply'][$i][m_no] ? 'Y' : 'N' );
					}
				}
			}

			$review_data[authdelete] = ( $review_data[reply_cnt] > 0 ? 'N' : $review_data[authdelete] ); # 답글 있는 경우 삭제 불가

			$review_loop[] = $review_data;
		}
		if ($dbCache) { $dbCache->setCache($pg->query, $review_loop); }
	}

	$data['review_cnt'] = $pg->recode['total'];

	unset($pg);
	unset($where);
	unset($dbCache);


	//네이버페이 상품후기 가져오기
	if ($checkoutReviewCfg['use'] === 'y') {
	
		//우선노출설정
		$priority = "";
		if ($checkoutReviewCfg['priority'] === '1') $priority = "1";
	
		$checkout_where[] = "PR.ProductID  = '$goodsno'";
			
		$pg = new Page(1,10);
		$pg->field = "PR.PurchaseReviewId as sno, PR.PurchaseReviewScore, PR.Title, PR.CreateYmdt, PR.ProductName, PR.ProductID";
		$db_table = " ".GD_NAVERCHECKOUT_PURCHASEREVIEW." AS PR";
		$pg->setQuery($db_table,$checkout_where,$sort="PR.CreateYmdt desc");
		$pg->exec();
	
		//DB Cache 사용 141030
		$dbCache = Core::loader('dbcache')->setLocation('mobile_goodsview_checkout_review');
	
		if (!$checkout_loop = $dbCache->getCache($pg->query)) {
	
			$res = $db->query($pg->query);
	
			while ($review_data=$db->fetch($res)) {
	
				if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
					$review_data = validation::xssCleanArray($review_data, array(
							validation::DEFAULT_KEY => 'text',
							'Title' => array('html', 'ent_noquotes'),
					));
				}
	
				$review_data['idx'] = $pg->idx--;
				$review_data['Title'] = nl2br(htmlspecialchars($review_data[Title]));
	
				$query = "select b.goodsnm,b.img_s,c.price
				from
					".GD_GOODS." b
					left join ".GD_GOODS_OPTION." c on b.goodsno=c.goodsno and link and go_is_deleted <> '1' and go_is_display = '1'
				where
					b.goodsno = '" . $data[goodsno] . "'";
				list( $review_data[goodsnm], $review_data[img_s], $review_data[price] ) = $db->fetch($query);
	
				//네이버페이 상품후기 평점
				if ($review_data[PurchaseReviewScore] == "0") {
					$review_data[PurchaseReviewScore] = "불만족";
				} else if ($review_data[PurchaseReviewScore] == "1") {
					$review_data[PurchaseReviewScore] = "보통";
				} else {
					$review_data[PurchaseReviewScore] = "만족";
				}
	
				$checkout_loop[] = $review_data;
			}
			if ($dbCache) { $dbCache->setCache($pg->query, $checkout_loop); }
		}
	
		$data['checkoutCnt'] = $pg->recode['total'];
	
		//글 갯수
		$reviewTotal = $data['review_cnt'] + $data['checkoutCnt'];
	
		unset($pg);
		unset($where);
		unset($dbCache);
	}
	
	// 상품 문의 가져오기
	$pg = new Page(1,10);
	$pg -> field = "b.m_no, b.m_id,b.name as m_name,a.*";
	
	$where[] = "a.goodsno = '$goodsno'";
	$where[] = "a.parent = a.sno";
	
	$where[]="notice!='1'";
	$pg->setQuery($db_table=GD_GOODS_QNA." a left join ".GD_MEMBER." b on a.m_no=b.m_no",$where,$sort="parent desc, ( case when parent=a.sno then 0 else 1 end ) asc, regdt desc");
	$pg->exec();
	
	//DB Cache 사용 141030
	$dbCache = Core::loader('dbcache')->setLocation('mobile_goodsview_qna');
	
	if (!$qna_loop = $dbCache->getCache($pg->query)) {
		$res = $db->query($pg->query);
		while ($qna_data=$db->fetch($res)){
			if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
				$qna_data = validation::xssCleanArray($qna_data, array(
						validation::DEFAULT_KEY => 'text',
						'contents' => array('html', 'ent_noquotes'),
				));
			}
	
			### 원글 체크
			list($qna_data['parent_m_no'],$qna_data['secret'],$qna_data['type']) = goods_qna_answer($qna_data['sno'],$qna_data['parent'],$qna_data['secret']);
			$qna_data['contents'] = nl2br(htmlspecialchars($qna_data['contents']));
	
			$reply_query = "SELECT subject, contents, regdt, sno, m_no FROM ".GD_GOODS_QNA." WHERE parent='".$qna_data[sno]."' AND sno != parent";
			$reply_res = $db->_select($reply_query);
	
			$qna_data['reply'] =$reply_res;
			$qna_data['reply_cnt'] = count($reply_res);
	
			### 권한체크
			if(!$cfg['qnaSecret']) $qna_data['secret'] = 0;
			list($qna_data['authmodify'],$qna_data['authdelete'],$qna_data['authview']) = goods_qna_chkAuth($qna_data);
	
			### 댓글 권한 체크
			if(!empty($qna_data['reply'])){
				for($i=0; $i<$qna_data['reply_cnt']; $i++){
					list($qna_data['reply'][$i]['authmodify'],$qna_data['reply'][$i]['authdelete'],$qna_data['reply'][$i]['authview']) = goods_qna_chkAuth($qna_data['reply'][$i]);
				}
			}
	
			### 비밀글 아이콘
			$qna_data['secretIcon'] = 0;
			if($qna_data['secret'] == '1') $qna_data['secretIcon'] = 1;
	
			if($qna_data['name']) {
				$tmp_name = $qna_data['name'];
			}
			else {
				$tmp_name = $qna_data['m_id'];
			}
	
			if(!preg_match('/[0-9A-Za-z]/', substr($tmp_name, 0, 1))) {
				$division_num = 2;
			}
			else {
				$division_num = 1;
			}
	
			$qna_data['qna_name'] = substr($tmp_name, 0, $division_num).implode('', array_fill(0, intval((strlen($tmp_name) -1)/$division_num), '*'));
	
	
			if ($ici_admin) $qna_data['accessable'] = true;
			else if ($qna_data['secret'] != '1') $qna_data['accessable'] = true;
			else if ($qna_data['m_no'] > 0 && $sess['m_no'] == $qna_data['m_no']) $qna_data['accessable'] = true;
			else $qna_data['accessable'] = false;
	
			$qna_loop[] = $qna_data;
		}
		if ($dbCache) { $dbCache->setCache($pg->query, $qna_loop); }
	}
	
	$data['qna_cnt'] = $pg->recode['total'];
	
	unset($pg);
	
	//view 스킨 지정
	$goods_view_skin_setting_version = false;
	switch((string)$cfgMobileShop['vtype_goods_view_skin']){
		case '0':
	
			break;
	
		case '1': default:
			$goods_view_skin_setting_version = true;
			$key_file = preg_replace( "'^.*$mobileRootDir/'si", "", $_SERVER['SCRIPT_NAME'] );
			$key_file = preg_replace( "'\.php$'si", "2.htm", $key_file );
			break;
	
		case '2':
			$goods_view_skin_setting_version = true;
			$key_file = preg_replace( "'^.*$mobileRootDir/'si", "", $_SERVER['SCRIPT_NAME'] );
			$key_file = preg_replace( "'\.php$'si", "3.htm", $key_file );
			break;
	}
	if($goods_view_skin_setting_version === true){
		if(is_file($tpl->template_dir.'/'.$key_file)) {
			$tpl->define( array(
					'tpl'			=> $key_file,
			) );
		}
	}
	
	if($_GET['view_area']) {
		$tpl->assign('view_area', $_GET['view_area']);
	}
	
	// 페이지뷰 카운팅
	if (Lib_Robot::isRobotAccess() === false) {
		$db->silent(true);
		$db->query("INSERT INTO ".GD_GOODS_PAGEVIEW." SET date = CURDATE(), goodsno = $goodsno, cnt = 1 ON DUPLICATE KEY UPDATE cnt = cnt + 1");
		$db->silent();
	}
	
	### 템플릿 출력
	$tpl->assign($data);
	$tpl->assign('returnUrl', $_SERVER[REQUEST_URI]);
	$tpl->assign('kw', Clib_Application::request()->get('kw'));
	$tpl->assign('category', $categoryModel->getId());
	$tpl->assign('referer', $_SERVER['HTTP_REFERER']);
	$tpl->assign('review_loop', $review_loop);
	$tpl->assign('qna_loop', $qna_loop);
	$tpl->assign('coupon_cnt', count($data[a_coupon]));
	$tpl->assign('customHeader', $customHeader);
	$tpl->assign('checkout_loop', $checkout_loop);
	$tpl->assign('priority', $priority);
	$tpl->assign('reviewTotal', $reviewTotal);
	
	$jQueryUiUse = false;
	if($data['hashtag']){
		$tpl->assign('jQueryHashtagJsPath', $jQueryHashtagJsPath);
		$jQueryUiUse = true;
	}
	
	$tpl->assign('jQueryUiUse', $jQueryUiUse);
	if($jQueryUiUse === true){
		$tpl->assign('jQueryUiPath', $jQueryUiPath);
		$tpl->assign('jQueryUiCssPath', $jQueryUiCssPath);
	}
	
	### 템플릿 출력
	Clib_Application::storage()->toGlobal();
	
	$tpl->assign(array(
			'clevel'	=> $categoryModel->getLevel(),
			'slevel'=> Clib_Application::session()->getMemberLevel(),
			'level_auth'=> $categoryModel->getLevelAuth()
	));
	
	$goodsBuyable = getGoodsBuyable($goodsno);
	$tpl->assign('goodsBuyable', $goodsBuyable);
	$tpl->print_('tpl');
	
	}
	catch (Clib_Exception $e) {
		Clib_Application::response()->jsAlert($e)->historyBack();
	}
	

?>
