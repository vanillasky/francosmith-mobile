<?
/*********************************************************
* 파일명     :  mGetDesignData.php
* 프로그램명 :	모바일 디자인 데이터 가져오기
* 작성자     :  dn
* 생성일     :  2012.05.14
**********************************************************/
@include '../lib/library.php';
@include $shopRootDir . "/lib/json.class.php";

if (is_file($shopRootDir . "/conf/config.soldout.php"))
	include $shopRootDir . "/conf/config.soldout.php";

$json = new Services_JSON(16);
$goodsDisplay = Core::loader('Mobile2GoodsDisplay');
$mainAutoSort = Core::loader('mainAutoSort');

$goodsDiscountModel = Clib_Application::getModelClass('goods_discount');

/**
 *  파라미터 POST/GET 호환 처리
 */
if(is_array($_POST) && !empty($_POST)) {
	$req_arr = $_POST;
}
else {
	$req_arr = $_GET;
}
/**
 * 테스트용
 */
if($_GET['debug']) {
	$_POST['mdesign_no'] = 5;
	$_POST['display_type'] = 3;
}

//정렬
$_add_table = $orderby2 = "";
if (!$req_arr['sort_type'] || $req_arr['sort_type'] == 1) {
	if($req_arr['display_type'] == '2') {
		$_add_field = ", md.sort as md_sort, IF (length(md.category) = 3 AND c.sort_type = 'MANUAL' , gl.sort1, IF (length(md.category) = 6 AND c.sort_type = 'MANUAL' , gl.sort2, IF (length(md.category) = 9 AND c.sort_type = 'MANUAL' , gl.sort3 , IF (length(md.category) = 12 AND c.sort_type = 'MANUAL' , gl.sort4 , gl.sort)))) as `dis_sort`";
		$_add_table = "LEFT JOIN ".GD_CATEGORY." c ON md.category = c.category";
		$orderby = "ORDER BY md.sort, dis_sort";
		$orderby2 = "ORDER BY gd_mobile.md_sort, gd_mobile.dis_sort";
	}
	else
		$orderby = "order by md.sort ASC";
} else {
	$sortNum = $mainAutoSort->use_table.".sort".$req_arr['sort_type']."_".$req_arr['select_date'];
	$orderby = "ORDER BY ".$sortNum;
}

// 품절 상품 제외
if ($cfg_soldout['exclude_main']) {
	if (!$req_arr['sort_type'] || $req_arr['sort_type'] == 1) {
		$where = " AND !( g.runout = 1 OR (g.usestock = 'o' AND g.usestock IS NOT NULL AND g.totstock < 1) ) ";
	} else {
		$where = " AND !( gd_goods.runout = 1 OR (gd_goods.usestock = 'o' AND gd_goods.usestock IS NOT NULL AND gd_goods.totstock < 1) ) ";
	}
}
// 제외시키지 않는 다면, 맨 뒤로 보낼지를 결정
else if ($cfg_soldout['back_main']) {
	if (!$req_arr['sort_type'] || $req_arr['sort_type'] == 1) {
		$orderby = "order by `soldout` ASC, md.sort";
		$_add_field = ",IF (g.runout = 1 , 1, IF (g.usestock = 'o' AND g.totstock = 0, 1, 0)) as `soldout`";
	} else {
		$orderby = "order by `soldout` ASC, ".$sortNum;
		$_add_field = ",IF (gd_goods.runout = 1 , 1, IF (gd_goods.usestock = 'o' AND gd_goods.totstock = 0, 1, 0)) as `soldout`";
	}
}

//$req_arr['mdesign_no'] = intval($req_arr['mdesign_no']);
//$req_arr['display_type'] = intval($req_arr['display_type']);

switch ($req_arr['display_type']) {
	case '1' :
		if (!$req_arr['sort_type'] || $req_arr['sort_type'] == 1) {
			$tmp_query = "
				SELECT
					md.goodsno, g.goodsnm, g.img_mobile, g.img_l, g.img_m, g.strprice, go.price, go.consumer, g.runout, g.usestock, g.totstock, g.use_only_adult, g.speach_description_useyn, g.speach_description, g.use_goods_discount $_add_field
				FROM
					".GD_MOBILE_DISPLAY." md
					LEFT JOIN ".GD_GOODS." g ON md.goodsno = g.goodsno
					LEFT JOIN ".GD_GOODS_OPTION." go ON md.goodsno = go.goodsno AND link and go_is_deleted <> '1' and go_is_display = '1'
				WHERE
					md.mdesign_no=[s] AND md.display_type=[s]
					and g.open_mobile
					$where
				$orderby
				";
		} else {
			list($add_table, $add_where, $add_order) = $mainAutoSort->getSortTerms($req_arr['mobile_categoods'], $req_arr['price'], $req_arr['stock_type'], $req_arr['stock_amount'], $req_arr['regdt'], $sortNum);

			$tmp_query = "
				SELECT
					".$mainAutoSort->use_table.".goodsno, gd_goods.goodsnm, gd_goods.img_mobile, gd_goods.img_l, gd_goods.img_m, gd_goods.strprice, gd_goods_option.price, gd_goods_option.consumer, gd_goods.runout, gd_goods.usestock, gd_goods.totstock, gd_goods.use_only_adult, gd_goods.speach_description_useyn, gd_goods.speach_description, gd_goods.use_goods_discount $_add_field
				FROM
					".GD_AUTO_MAIN_DISPLAY."
					{$add_table}
				WHERE
					g.open_mobile
					$where
					{$add_where}
				GROUP BY ".$mainAutoSort->use_table.".goodsno $orderby
				LIMIT ".$mainAutoSort->sort_limit."
				";
		}

		$display_query = $db->_query_print($tmp_query, $req_arr['mdesign_no'], $req_arr['display_type']);
		$tmp_display = $db->_select($display_query);

		$img_path = '';

		if($cfg['rootDir']) {
			$img_path = $cfg['rootDir'].'/data/goods/';
		}
		else {
			$img_path = '/shop/data/goods/';
		}

		//DB Cache 사용 141030
		$dbCache = Core::loader('dbcache')->setLocation('mobile_display');

		if (!$res_display = $dbCache->getCache($display_query)) {
			$res_display = array();

			if(is_array($tmp_display) && !empty($tmp_display)) {
				foreach($tmp_display as $row_display) {
					$tmp_arr = array();

					$tmp_arr['goodsno'] = $row_display['goodsno'];
					$tmp_arr['goodsnm'] = strip_tags($row_display['goodsnm']);

					$img_l_arr = explode("|", $row_display['img_l']);
					if (preg_match("/^http/i", $img_l_arr[0])) {
						$tmp_arr['goods_img'] = $img_l_arr[0];
					} else {
						$tmp_arr['goods_img'] = $img_path.$img_l_arr[0];
					}
					if($row_display['img_m']) {
						$img_m_arr = explode("|", $row_display['img_m']);
						if (preg_match("/^http/i", $img_m_arr[0])) {
							$tmp_arr['goods_img'] = $img_m_arr[0];
						} else {
							$tmp_arr['goods_img'] = $img_path.$img_m_arr[0];
						}

					}
					if($row_display['use_only_adult'] == '1' && !Clib_Application::session()->canAccessAdult()){
						$tmp_arr['goods_img'] = 'http://' . $_SERVER['HTTP_HOST'] . $cfg['rootDir'] . "/data/skin/" . $cfg['tplSkin'] . '/img/common/19.gif';
					}
					if (strlen(trim($row_display['strprice'])) > 0) {
						$tmp_arr['goods_price'] = $row_display['strprice'];
					}
					else {
						$tmp_arr['goods_price'] = number_format($row_display['price']).' 원';
					}

					// 소비자가
					if ($row_display['consumer'] > 0) {
						$tmp_arr['consumer'] = $row_display['consumer'];
					}
					if ($row_display['speach_description_useyn'] === 'y' && strlen($row_display['speach_description']) > 0) {
						$tmp_arr['tts_url'] = Core::loader('TextToSpeach')->getURL($row_display['speach_description']);
					}
					else {
						$tmp_arr['tts_url'] = '';
					}

					// 상품할인
					if($row_display['use_goods_discount']){
						$tmp_arr['special_discount'] = $goodsDiscountModel->getDiscountUnit($row_display, Clib_Application::session()->getMemberLevel());
					}

					// 품절상품
					if ( $row_display['runout'] > 0 || $row_display['usestock'] && $row_display['totstock'] < 1) {
						if($cfg_soldout['mobile_display'] === 'overlay')
							$tmp_arr['css_selector'] = 'class="el-goods-soldout-image"';
					}

					// 즉석할인쿠폰 유효성 검사 (pc or mobile)
					list($tmp_arr['coupon'], $tmp_arr['coupon_emoney']) = getCouponInfoMobile($row_display['goodsno'], $row_display['price']);

					// 쿠폰할인여부
					if($tmp_arr['coupon'] > 0 || $tmp_arr['coupon_emoney'] > 0){
						$tmp_arr['coupon_discount'] = true;
					}

					$res_display[] = $tmp_arr;
				}
				if ($dbCache) { $dbCache->setCache($display_query, $res_display); }
			}
		}

		break;

	case '2' :
		$tmp_query = "
			SELECT * FROM (
			SELECT
				md.category, g.goodsno, g.goodsnm, g.img_i, g.img_s, g.img_m, g.img_l, g.use_mobile_img, g.img_w, g.img_pc_w, g.strprice, go.price, go.consumer, g.runout, g.usestock, g.totstock, g.use_only_adult, g.speach_description_useyn, g.speach_description, g.use_goods_discount $_add_field
			FROM
				".GD_MOBILE_DISPLAY." md
				LEFT JOIN ".GD_GOODS_LINK." gl ON gl.category = md.category
				LEFT JOIN ".GD_GOODS." g ON gl.goodsno = g.goodsno
				LEFT JOIN ".GD_GOODS_OPTION." go ON g.goodsno = go.goodsno AND link and go_is_deleted <> '1' and go_is_display = '1'
				$_add_table
			WHERE
				md.mdesign_no=[s] AND md.display_type=[s]
				and g.open_mobile
				$where
			$orderby
		) gd_mobile
		GROUP BY gd_mobile.goodsno
		$orderby2
		";

		$display_query = $db->_query_print($tmp_query, $req_arr['mdesign_no'], $req_arr['display_type']);

		$tmp_display = $db->_select($display_query);

		$img_path = '';

		// 매거진의 경우,  이미지
		if($cfg['rootDir']) {
			$img_path = $cfg['rootDir'].'/data/goods/';
		}
		else {
			$img_path = '/shop/data/goods/';
		}

		$res_display = array();

		if(is_array($tmp_display) && !empty($tmp_display)) {
			foreach($tmp_display as $row_display) {
				$tmp_arr = array();

				$tmp_arr['goodsno'] = $row_display['goodsno'];
				$tmp_arr['goodsnm'] = strip_tags($row_display['goodsnm']);

				$img_l_arr = explode("|", $row_display['img_l']);
				if (preg_match("/^http/i", $img_l_arr[0])) {
					$tmp_arr['goods_img'] = $img_l_arr[0];
				} else {
					$tmp_arr['goods_img'] = $img_path.$img_l_arr[0];
				}
				if($row_display['img_m']) {
					$img_m_arr = explode("|", $row_display['img_m']);
					if (preg_match("/^http/i", $img_m_arr[0])) {
						$tmp_arr['goods_img'] = $img_m_arr[0];
					} else {
						$tmp_arr['goods_img'] = $img_path.$img_m_arr[0];
					}
				}
				if($row_display['use_only_adult'] == '1' && !Clib_Application::session()->canAccessAdult()){
					$tmp_arr['goods_img'] = 'http://' . $_SERVER['HTTP_HOST'] . $cfg['rootDir'] . "/data/skin/" . $cfg['tplSkin'] . '/img/common/19.gif';
				}
				if (strlen(trim($row_display['strprice'])) > 0) {
					$tmp_arr['goods_price'] = $row_display['strprice'];
				}
				else {
					$tmp_arr['goods_price'] = number_format($row_display['price']).' 원';
				}

				// 소비자가
				if ($row_display['consumer'] > 0) {
					$tmp_arr['consumer'] = $row_display['consumer'];
				}

				if ($row_display['speach_description_useyn'] === 'y' && strlen($row_display['speach_description']) > 0) {
					$tmp_arr['tts_url'] = Core::loader('TextToSpeach')->getURL($row_display['speach_description']);
				}
				else {
					$tmp_arr['tts_url'] = '';
				}

				// 상품할인
				if($row_display['use_goods_discount']){
					$tmp_arr['special_discount'] = $goodsDiscountModel->getDiscountUnit($row_display, Clib_Application::session()->getMemberLevel());
				}

				// 품절상품
				if ( $row_display['runout'] > 0 || $row_display['usestock'] && $row_display['totstock'] < 1) {
					if($cfg_soldout['mobile_display'] === 'overlay')
						$tmp_arr['css_selector'] = 'class="el-goods-soldout-image"';
				}

				// 즉석할인쿠폰 유효성 검사 (pc or mobile)
				list($tmp_arr['coupon'], $tmp_arr['coupon_emoney']) = getCouponInfoMobile($row_display['goodsno'], $row_display['price']);

				// 쿠폰할인여부
				if($tmp_arr['coupon'] > 0 || $tmp_arr['coupon_emoney'] > 0){
					$tmp_arr['coupon_discount'] = true;
				}

				$res_display[] = $tmp_arr;
			}
		}
		break;
	case '3' :
		$tmp_query = '
			SELECT
				md.category, c.catnm, md.temp2
			FROM
				'.GD_MOBILE_DISPLAY.' md
				LEFT JOIN '.GD_CATEGORY.' c ON md.category = c.category
			WHERE
				md.mdesign_no=[s] AND md.display_type=[s]
			ORDER BY
				md.sort ASC
			';

			$display_query = $db->_query_print($tmp_query, $req_arr['mdesign_no'], $req_arr['display_type']);

			$tmp_display = $db->_select($display_query);

			$img_path = '';

			if($cfg['rootDir']) {
				$img_path = $cfg['rootDir'].'/data/m/upload_img/';
			}
			else {
				$img_path = '/shop/data/m/upload_img/';
			}

			$res_display = array();

			if(is_array($tmp_display) && !empty($tmp_display)) {
				foreach($tmp_display as $row_display) {
					$tmp_arr = array();

					$tmp_arr['goodsno'] = $row_display['category'];
					$tmp_arr['goodsnm'] = $row_display['catnm'];
					$tmp_arr['goods_img'] = $img_path.$row_display['temp2'];
					$tmp_arr['goods_price'] = '';

					if(is_file('../../'.$tmp_arr['goods_img']) && $tmp_arr['goodsnm']) {
						$res_display[] = $tmp_arr;
					}
				}
			}

		break;

	case '5' :
		$tab_query = $db->_query_print('SELECT tpl_opt FROM '.GD_MOBILE_DESIGN.' WHERE mdesign_no=[s]', $req_arr['mdesign_no']);
		$tab_res = $db->_select($tab_query);
		$tab_res = $tab_res[0];

		$json = new Services_JSON(16);
		$tab_info = $json->decode($tab_res['tpl_opt']);

		$res_display = array();

		if(is_array($tab_info['tab_name']) && !empty($tab_info['tab_name'])) {
			foreach ($tab_info['tab_name'] as $key => $val) {

				if (!$req_arr['sort_type'] || $req_arr['sort_type'] == 1) {
					$tmp_query = "
						SELECT
							md.goodsno, g.goodsnm, g.img_mobile, g.img_l, g.img_m, g.strprice, go.price, go.consumer, g.runout, g.usestock, g.totstock, g.speach_description_useyn, g.speach_description, g.use_goods_discount $_add_field
						FROM
							".GD_MOBILE_DISPLAY." md
							LEFT JOIN ".GD_GOODS." g ON md.goodsno = g.goodsno
							LEFT JOIN ".GD_GOODS_OPTION." go ON md.goodsno = go.goodsno AND link and go_is_deleted <> '1' and go_is_display = '1'
						WHERE
							md.mdesign_no=[s] AND md.display_type=[s] AND md.tab_no=[s]
							and g.open_mobile
							$where
						$orderby
						";
				} else {
					list($add_table, $add_where, $add_order) = $mainAutoSort->getSortTerms($req_arr['mobile_categoods'], $req_arr['price'], $req_arr['stock_type'], $req_arr['stock_amount'], $req_arr['regdt'], $sortNum);

					$tmp_query = "
						SELECT
							".$mainAutoSort->use_table.".goodsno, gd_goods.goodsnm, gd_goods.img_mobile, gd_goods.img_l, gd_goods.img_m, gd_goods.strprice, gd_goods_option.price, gd_goods_option.consumer, gd_goods.runout, gd_goods.usestock, gd_goods.totstock, gd_goods.speach_description_useyn, gd_goods.speach_description, gd_goods.use_goods_discount $_add_field
						FROM
							".$mainAutoSort->use_table."
							{$add_table}
						WHERE
							g.open_mobile
							$where
							{$add_where}
						GROUP BY ".$mainAutoSort->use_table.".goodsno $orderby
						LIMIT ".$mainAutoSort->sort_limit."
						";
				}

				$display_query = $db->_query_print($tmp_query, $req_arr['mdesign_no'], $req_arr['display_type'], $key);
				$tmp_display = $db->_select($display_query);

				$img_path = '';

				if($cfg['rootDir']) {
					$img_path = $cfg['rootDir'].'/data/goods/';
				}
				else {
					$img_path = '/shop/data/goods/';
				}

				$tmp_res = array();
				if(is_array($tmp_display) && !empty($tmp_display)) {
					foreach($tmp_display as $row_display) {
						$tmp_arr = array();

						$tmp_arr['goodsno'] = $row_display['goodsno'];
						$tmp_arr['goodsnm'] = strip_tags($row_display['goodsnm']);
						$img_l_arr = explode("|", $row_display['img_l']);
						if (preg_match("/^http/i", $img_l_arr[0])) {
							$tmp_arr['goods_img'] = $img_l_arr[0];
						} else {
							$tmp_arr['goods_img'] = $img_path.$img_l_arr[0];
						}
						if($row_display['img_m']) {
							$img_m_arr = explode("|", $row_display['img_m']);
							if (preg_match("/^http/i", $img_m_arr[0])) {
								$tmp_arr['goods_img'] = $img_m_arr[0];
							} else {
								$tmp_arr['goods_img'] = $img_path.$img_m_arr[0];
							}
						}
						if (strlen(trim($row_display['strprice'])) > 0) {
							$tmp_arr['goods_price'] = $row_display['strprice'];
						}
						else {
							$tmp_arr['goods_price'] = number_format($row_display['price']).' 원';
						}
		
						// 소비자가
						if ($row_display['consumer'] > 0) {
							$tmp_arr['consumer'] = $row_display['consumer'];
						}
						if ($row_display['speach_description_useyn'] === 'y' && strlen($row_display['speach_description']) > 0) {
							$tmp_arr['tts_url'] = Core::loader('TextToSpeach')->getURL($row_display['speach_description']);
						}
						else {
							$tmp_arr['tts_url'] = '';
						}

						// 상품할인
						if($row_display['use_goods_discount']){
							$tmp_arr['special_discount'] = $goodsDiscountModel->getDiscountUnit($row_display, Clib_Application::session()->getMemberLevel());
						}

						// 품절상품
						if ( $row_display['runout'] > 0 || $row_display['usestock'] && $row_display['totstock'] < 1) {
							if($cfg_soldout['mobile_display'] === 'overlay')
								$tmp_arr['css_selector'] = 'class="el-goods-soldout-image"';
						}

						// 즉석할인쿠폰 유효성 검사 (pc or mobile)
						list($tmp_arr['coupon'], $tmp_arr['coupon_emoney']) = getCouponInfoMobile($row_display['goodsno'], $row_display['price']);

						// 쿠폰할인여부
						if($tmp_arr['coupon'] > 0 || $tmp_arr['coupon_emoney'] > 0){
							$tmp_arr['coupon_discount'] = true;
						}

						$tmp_res[] = $tmp_arr;
					}
				}
				$res_display[] = $tmp_res;
			}
		}

		break;

	case '7' :
		$banner_query = $db->_query_print('SELECT tpl_opt FROM '.GD_MOBILE_DESIGN.' WHERE mdesign_no=[s]', $req_arr['mdesign_no']);
		$banner_res = $db->_select($banner_query);
		$banner_res = $banner_res[0];

		$json = new Services_JSON(16);
		$banner_info = $json->decode($banner_res['tpl_opt']);

		$res_display = array();

		if(is_array($banner_info['banner_img']) && !empty($banner_info['banner_img'])) {

			foreach ($banner_info['banner_img'] as $key => $val) {

				$tmp_query = '
					SELECT
						md.temp1
					FROM
						'.GD_MOBILE_DISPLAY.' md
					WHERE
						md.mdesign_no=[s] AND md.display_type=[s] AND md.banner_no=[s]
					ORDER BY
						md.sort ASC
					';

				$display_query = $db->_query_print($tmp_query, $req_arr['mdesign_no'], $req_arr['display_type'], $key);
				$tmp_display = $db->_select($display_query);
				$tmp_display = $tmp_display[0];

				$tmp_res = array();

				if($cfg['rootDir']) {
					$img_path = $cfg['rootDir'].'/data/m/upload_img/';
				}
				else {
					$img_path = '/shop/data/m/upload_img/';
				}

				$tmp_res['banner_img'] = $img_path.$val;

				if(strstr($tmp_display, 'http')) {
					$tmp_res['link_url'] = $tmp_display['temp1'];
				}
				else {
					$tmp_res['link_url'] = 'http://'.$tmp_display['temp1'];
				}

				if(is_file('../../'.$tmp_res['banner_img'])) {
					$res_display[] = $tmp_res;
				}

			}
		}
		break;

}

if ($goodsDisplay->isPCDisplay()) {
	$res_display = $goodsDisplay->getPCMainDisplayGoods($req_arr['mdesign_no']);
}

echo $json->encode($res_display);
?>