<?
/*********************************************************
* 파일명     :  mGetDesignData.php
* 프로그램명 :	모바일 디자인 데이터 가져오기
* 작성자     :  dn
* 생성일     :  2012.05.14
**********************************************************/	
@include '../lib/library.php';
include $shopRootDir . "/lib/json.class.php";

$json = new Services_JSON(16);
$req_arr = $list_arr = $arr = array();

if($_GET['debug']) {
	$_POST['mevent_no'] = 3;
}

/** 
 *  파라미터 POST/GET 호환 처리 
 */
if(is_array($_POST) && !empty($_POST)) {
	$req_arr = $_POST;
}
else {
	$req_arr = $_GET;
}

$design_query = $db->_query_print('SELECT * FROM '.GD_MOBILE_EVENT.' WHERE mevent_no=[s]', $req_arr['mevent_no']);
$res_design = $db->_select($design_query);
if ($res_design) {
	$res_design[0]['event_body'] = stripslashes($res_design[0]['event_body']);
} 
if($res_design[0]['tpl'] == 'tpl_05'|| $res_design[0]['tpl'] == 'tpl_07'){
	$res_design[0]['tpl_opt'] = $json->decode($res_design[0]['tpl_opt']);
} 
$res_design[0]['mdesign_no'] = $req_arr['mevent_no'];
echo $json->encode($res_design[0]);

unset($req_arr, $list_arr, $arr);
exit;
?>