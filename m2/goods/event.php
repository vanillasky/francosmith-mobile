<?
/*********************************************************
* 파일명     :  event.php
* 프로그램명 :	event 페이지
* 작성자     :  dn
* 생성일     :  2012.08.17
**********************************************************/	

$rtm[] = microtime();
include dirname(__FILE__) . "/../_header.php";
@include $shopRootDir . "/lib/page.class.php";
@include $shopRootDir . "/conf/config.pay.php";
if (is_file($shopRootDir . "/conf/config.soldout.php"))
	include $shopRootDir . "/conf/config.soldout.php";
$rtm[] = microtime();

$mevent_no = $_GET['mevent_no'];

if(!$mevent_no) {
	msg('잘못된 접근입니다', -1);
}

$select_query = $db->_query_print('SELECT tpl, event_title, start_date, event_body, end_date, line_cnt, disp_cnt, tpl_opt FROM '.GD_MOBILE_EVENT.' WHERE mevent_no=[i]', $mevent_no);
$res_select = $db->_select($select_query);
$_cfg = $res_select[0];

$now_date = date('Y-m-d H:i:s');
if($_cfg['start_date'] > $now_date || $_cfg['end_date'] < $now_date) {
	msg('이벤트 기간이 아닙니다', -1);
}

$goodsDisplay = Core::loader('Mobile2GoodsDisplay');
$goods_data = $goodsDisplay->getMobileEventDisplayGoods($mevent_no);

// 템플릿 기본설정 변수
$_cfg['mdesign_no'] = $mevent_no;
$_cfg['title'] = '';
$_cfg['page_goods_cnt'] = (int)$_cfg['line_cnt'] * (int)$_cfg['disp_cnt'];
$_cfg['item_width'] = floor(99/$_cfg['disp_cnt']);
$_cfg['event_body'] = str_replace("'", '', str_replace("\\", "", $_cfg['event_body']));

switch ($_cfg[tpl]) {
	case 'tpl_03':// 상품스크롤형
	case 'tpl_04':// 이미지스크롤형
		$_cfg['goods_cnt'] = sizeof($goods_data);
		$_cfg['item_name_width'] = floor($_SESSION['screen']['width']/$_cfg['disp_cnt']) - 26;
		$_cfg['page_cnt'] = floor($_cfg['goods_cnt']/$_cfg['page_goods_cnt']); //템플릿 호출시 설정해야 함
		if (empty($_cfg['remain_cnt'])) {
			$_cfg['remain_cnt'] = floor($_cfg['goods_cnt']%$_cfg['page_goods_cnt']);
			if($_cfg['remain_cnt'] > 0) {
				$_cfg['page_cnt'] = $_cfg['page_cnt'] + 1;
			}
		}
		
		// page_goods_cnt의 갯수에 따라 배열에 분할 할당
		foreach($goods_data as $idx => $data) {
			$key = floor($idx/$_cfg['page_goods_cnt']);
			$_cfg['goods_data'][$key][] = $data;
		}
		break;

	case 'tpl_05':// 탭형
		if (class_exists('Services_JSON') === false) { 
			@include $shopRootDir . "/lib/json.class.php"; 
		}
		$json = new Services_JSON(16);
		$_cfg['tpl_opt'] = $json->decode($_cfg['tpl_opt']);// 템플릿옵션(JSON)을 배열로 변환 

		if (empty($_cfg['tab_cnt'])) $_cfg['tab_cnt'] = $_cfg['tpl_opt']['tab_num'];
		if (empty($_cfg['tab_width'])) $_cfg['tab_width'] = floor(98/$_cfg['tab_cnt']);

		for($i=0; $i<$_cfg['tab_cnt']; $i++) {
			foreach($goods_data[$i] as $idx => $data) {
				$key = floor($idx/$_cfg['page_goods_cnt']);
				$_cfg['goods_data'][$i][$key][] = $data;
			}
			// 각 영역별 디스플레이 설정에 따른 페이지당 상품수 계산 
			if (empty($_cfg['goods_data_opt'][$i]['page_cnt'])) $_cfg['goods_data_opt'][$i]['page_cnt'] = sizeof($_cfg['goods_data'][$i]);
		}

		break;
}
$dpCfg = $_cfg;

$tpl->assign(array(
			tpl => $_cfg['tpl'],
			page_title => $_cfg['event_title'],
			event_body => $_cfg['event_body'],
			mevent_no	=> $mevent_no,
));
$tpl->print_('tpl');
