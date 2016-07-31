<?
/*********************************************************
* 파일명     :  mGetDesignData.php
* 프로그램명 :	모바일 디자인 데이터 가져오기
* 작성자     :  dn
* 생성일     :  2012.05.14
**********************************************************/

include '../lib/library.php';
@include $shopRootDir . "/lib/json.class.php";

# 1. 필요한 오브젝트 생성 
$json = new Services_JSON();
$goodsDisplay = Core::loader('Mobile2GoodsDisplay');
$req_arr = $list_arr = $arr = array();


# 2. 변수 POST/GET 호환 처리 
if(is_array($_POST) && !empty($_POST)) {
	$req_arr = $_POST;
}
else {
	$req_arr = $_GET;
}

# 3. 변수 Validation : 변수 검증 


# 3.1 예외 DEBUG 처리등 
if($_GET['debug']) {
	$req_arr['mdesign_no']  = 4;
	$req_arr['page_type']= 'main';
}

if ($goodsDisplay->displayTypeIsSet() === false) {
	if ($goodsDisplay->isInitStatus()) {
		$goodsDisplay->saveMainDisplayType('pc');
	}
	else {
		$goodsDisplay->saveMainDisplayType('mobile');
	}
}

# 4. 결과 오브젝트 생성

# 5. 데이터 조회 : DB 인터페이스 
$goodsDisplay->initializeMainDisplay();
$tmp_query = 'SELECT mdesign_no, page_type, chk, title, line_cnt, disp_cnt, banner_width, banner_height,'; 
$tmp_query.= ' display_type, tpl, tpl_opt, COALESCE(temp1,0) as temp1, COALESCE(temp2,0) as temp2, COALESCE(temp3,0) as temp3, COALESCE(text_temp1,0) as text_temp1'; 
$tmp_query.= ' FROM '.GD_MOBILE_DESIGN.' WHERE page_type=[s] AND mdesign_no=[s] AND chk=[s]'; 
$design_query = $db->_query_print($tmp_query, $req_arr['page_type'], $req_arr['mdesign_no'], 'on');
$res_design = $db->_select($design_query);
//debug($res_design);
if($res_design[0]['tpl'] == 'tpl_05' || $res_design[0]['tpl'] == 'tpl_07'){
	$res_design[0]['tpl_opt'] = $json->decode($res_design[0]['tpl_opt']);
}

if ($goodsDisplay->isPCDisplay()) {
	$goodsDisplay->makeDefaultMainDisplayDesign($req_arr['mdesign_no'], $res_design[0]);
}

//debug($res_design);
# 6. 데이터 변환
echo $json->encode($res_design[0]);

unset($req_arr, $list_arr, $arr);

exit;

?>