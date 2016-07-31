<?
/*********************************************************
* 파일명     :  index.php
* 프로그램명 :	메인페이지
* 작성자     :  dn
* 생성일     :  2012.04.14
**********************************************************/	
include dirname(__FILE__) . "/_header.php";
@include $shopRootDir . "/conf/config.mobileShop.main.php";
if (is_file($shopRootDir . "/conf/config.soldout.php"))
	include $shopRootDir . "/conf/config.soldout.php";

$_view_search_form = true;
$mainpage = true;

## 모바일샵 미설정일때는 PC화면으로 돌려준다 
if(!$cfgMobileShop['useMobileShop']){
	header("location:".$cfg['rootDir']."/main/intro.php");
}

## 모바일샵 일반인트로 사용일 경우 도메인으로 들어 온 것인지 메인으로 들어온 것인지 체크
if ( $cfg['introUseYNMobile'] == 'Y' && (int)$cfg['custom_landingpageMobile'] < 2 ) {
    if(preg_match('/^http(s)?:\/\/'.$_SERVER[SERVER_NAME].'\/m2$/',$_SERVER[HTTP_REFERER]) || strpos($_SERVER[HTTP_REFERER],"http://".$_SERVER[SERVER_NAME]) !==0 ){ // 인트로 사용
        header("location:intro/intro.php" . ($_SERVER[QUERY_STRING] ? "?{$_SERVER[QUERY_STRING]}" : ""));
    }
}

## 2015-07 @qnibus 메인진열상품 Ajax->PHP템플릿 방식으로 변경
## 메인 상품 리스트 화면 설정 및 상품 데이터
@include $shopRootDir . "/lib/json.class.php";
$json = new Services_JSON(16);
$goodsDisplay = Core::loader('Mobile2GoodsDisplay');

if ($goodsDisplay->displayTypeIsSet() === false) {
	if ($goodsDisplay->isInitStatus()) {
		$goodsDisplay->saveMainDisplayType('pc');
	}
	else {
		$goodsDisplay->saveMainDisplayType('mobile');
	}
}

$cfg_step = $goodsDisplay->initializeMainDisplay();
$cfg_step_keys = array_keys($cfg_step);

for($i = 0, $imax = count($cfg_step_keys); $i < $imax; $i++) {
	$_cfg = $cfg_step[ $cfg_step_keys[$i] ];	
	$goods_data = $goodsDisplay->getMobileMainDisplayGoods($_cfg);
	$_cfg['tpl_opt'] = $json->decode($_cfg['tpl_opt']);// 템플릿옵션(JSON)을 배열로 변환
	$_cfg['idx'] = $cfg_step_keys[$i];// 인덱스 값 저장

	// 템플릿이 PC 기준으로 설정되어 있는 경우 디자인 설정 초기화
	if ($goodsDisplay->isPCDIsplay()) {
		$goodsDisplay->makeDefaultMainDisplayDesign($_cfg['mdesign_no'], $_cfg);
	}

	// 템플릿 변수 기본설정
	if ($_cfg['chk'] == 'on') {
		if (empty($_cfg['title'])) $_cfg['title'] = ' ';
		if (empty($_cfg['tpl'])) $_cfg['tpl'] = 'tpl_03';
		if (empty($_cfg['img'])) $_cfg['img'] = 'img_s';
		if (empty($_cfg['page_goods_cnt'])) $_cfg['page_goods_cnt'] = (int)$_cfg['line_cnt'] * (int)$_cfg['disp_cnt'];
		if (empty($_cfg['item_width'])) $_cfg['item_width'] = floor(99/$_cfg['disp_cnt']);
	}

	// 템플릿별 상품데이터 가공
	switch ($_cfg[tpl]) {
		case 'tpl_01':
			break;

		case 'tpl_02':
			break;

		case 'tpl_03':// 상품스크롤형
		case 'tpl_04':// 이미지스크롤형
			if (empty($_cfg['page_cnt'])) $_cfg['page_cnt'] = floor(sizeof($goods_data)/$_cfg['page_goods_cnt']); //템플릿 호출시 설정해야 함
			if (empty($_cfg['remain_cnt'])) {
				$_cfg['remain_cnt'] = floor(sizeof($goods_data)%$_cfg['page_goods_cnt']);
				if($_cfg['remain_cnt'] > 0) {
					$_cfg['page_cnt'] = $_cfg['page_cnt'] + 1;
				}
			}
			if (empty($_cfg['goods_cnt'])) $_cfg['goods_cnt'] = sizeof($goods_data);

			// page_goods_cnt의 갯수에 따라 배열에 분할 할당
			foreach($goods_data as $idx => $data) {
				$key = floor($idx/$_cfg['page_goods_cnt']);
				$_cfg['goods_data'][$key][] = $data;
			}
			break;

		case 'tpl_05':// 탭형
			if (empty($_cfg['tab_cnt'])) $_cfg['tab_cnt'] = $_cfg['tpl_opt']['tab_num'];
			if (empty($_cfg['tab_width'])) $_cfg['tab_width'] = floor(98/$_cfg['tab_cnt']);
			for($j=0; $j<$_cfg['tab_cnt']; $j++) {
				foreach($goods_data[$j] as $idx => $data) {
					$key = floor($idx/$_cfg['page_goods_cnt']);
					$_cfg['goods_data'][$j][$key][] = $data;
				}
				// 각 영역별 디스플레이 설정에 따른 페이지당 상품수 계산 
				if (empty($_cfg['goods_data_opt'][$j]['page_cnt'])) $_cfg['goods_data_opt'][$j]['page_cnt'] = sizeof($_cfg['goods_data'][$j]);
			}
			break;

		case 'tpl_06':// 매거진 스크롤형
			if (empty($_cfg['goods_data'])) $_cfg['goods_data'] = $goods_data;
			if (empty($_cfg['page_cnt'])) $_cfg['page_cnt'] = sizeof($goods_data);
			if (empty($_cfg['goods_cnt'])) $_cfg['goods_cnt'] = sizeof($goods_data);
			if (empty($_cfg['img_width'])) $_cfg['img_width'] = $_SESSION['screen']['width'];
			if (empty($_cfg['img_height'])) $_cfg['img_height'] = $_cfg['banner_height'];
			break;

		case 'tpl_07':
			if (empty($_cfg['event_data'])) $_cfg['event_data'] = $goods_data;
			if (empty($_cfg['banner_cnt'])) $_cfg['banner_cnt'] = $_cfg['tpl_opt']['banner_num'];
			if (empty($_cfg['img_width'])) $_cfg['img_width'] = $_cfg['banner_width'];
			if (empty($_cfg['img_height'])) $_cfg['img_height'] = $_cfg['banner_height'];
			if ($_cfg['banner_height'] > 0) {
				$_cfg['img_width'] = $_cfg['banner_width'];
				$_cfg['img_height'] = $_cfg['banner_height'];
			}
			break;
	}

	$cfg_step[ $cfg_step_keys[$i] ] = $_cfg;
}

/* 모바일샵 팝업창 관리 시작 */
$today = date("Y-m-d H:i:s");
$hour = date("H");

$query = "	SELECT * FROM ".GD_MOBILEV2_POPUP." 
			WHERE open=1 
			and (	open_type=0 
					or 
					(open_type=1 and ('$today' between concat(start_date, ' ',if(length(start_time) = 1,concat('0',start_time),start_time),':00:00') 
					                      and concat(end_date, ' ',if(length(end_time) = 1,concat('0',end_time),end_time),':59:59'))
					)
				)
			limit 0,1
";

$popup_query = $db->_query_print($query);
$res_popup = $db->_select($popup_query);
$popup_data = $res_popup[0];


if($popup_data['popup_type'] == '0') {
	$src = "../m/upload_img/".$popup_data['popup_img'];
	
	$size	= getimagesize($src);

	if($size[0] > 320)  $width='320';
	else				$width=$size[0];
	
	$popup_data['popup_img'] = goodsimgMobile($src,$width,'',1);
}
/* 모바일샵 팝업창 관리 종료 */

$tpl->assign('popup_data',$popup_data);
$tpl->print_('tpl');

?>