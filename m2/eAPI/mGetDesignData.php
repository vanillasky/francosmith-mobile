<?
/*********************************************************
* ���ϸ�     :  mGetDesignData.php
* ���α׷��� :	����� ������ ������ ��������
* �ۼ���     :  dn
* ������     :  2012.05.14
**********************************************************/

include '../lib/library.php';
@include $shopRootDir . "/lib/json.class.php";

# 1. �ʿ��� ������Ʈ ���� 
$json = new Services_JSON();
$goodsDisplay = Core::loader('Mobile2GoodsDisplay');
$req_arr = $list_arr = $arr = array();


# 2. ���� POST/GET ȣȯ ó�� 
if(is_array($_POST) && !empty($_POST)) {
	$req_arr = $_POST;
}
else {
	$req_arr = $_GET;
}

# 3. ���� Validation : ���� ���� 


# 3.1 ���� DEBUG ó���� 
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

# 4. ��� ������Ʈ ����

# 5. ������ ��ȸ : DB �������̽� 
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
# 6. ������ ��ȯ
echo $json->encode($res_design[0]);

unset($req_arr, $list_arr, $arr);

exit;

?>