<?
/*********************************************************
* ���ϸ�     :  mGetDesignData.php
* ���α׷��� :	����� ������ ������ ��������
* �ۼ���     :  dn
* ������     :  2012.05.14
**********************************************************/
@include '../lib/library.php';
include $shopRootDir . "/lib/json.class.php";

if (is_file($shopRootDir . "/conf/config.soldout.php"))
	include $shopRootDir . "/conf/config.soldout.php";

$json = new Services_JSON(16);

$goodsDiscountModel = Clib_Application::getModelClass('goods_discount');

$req_arr = $list_arr = $arr = array();
/**
 *  �Ķ���� POST/GET ȣȯ ó��
 */
if($_GET['debug']) {
	$_POST['mdesign_no'] = 13;
	$_POST['display_type'] = 1;
}
if(is_array($_POST) && !empty($_POST)) {
	$req_arr = $_POST;
}
else {
	$req_arr = $_GET;
}
$design_query = $db->_query_print('SELECT tpl, tpl_opt FROM '.GD_MOBILE_EVENT.' WHERE mevent_no=[s]', $req_arr['mevent_no']);
$res_design = $db->_select($design_query);

//����
$orderby = "order by md.sort ASC";

// ǰ�� ��ǰ ����
if ($cfg_soldout['exclude_event']) {
	$where = " AND !( g.runout = 1 OR (g.usestock = 'o' AND g.usestock IS NOT NULL AND g.totstock < 1) ) ";
}
// ���ܽ�Ű�� �ʴ´ٸ�, �� �ڷ� �������� ����
else if ($cfg_soldout['back_event']) {
	$orderby = "order by `soldout` ASC, md.sort";
	$_add_field = ",IF (g.runout = 1 , 1, IF (g.usestock = 'o' AND g.totstock = 0, 1, 0)) as `soldout`";
}

switch ( $res_design[0]['tpl']) {
	case "tpl_05":
		$tab_info = $json->decode($res_design[0]['tpl_opt']);

		if(is_array($tab_info['tab_name']) && !empty($tab_info['tab_name'])) {
			foreach ($tab_info['tab_name'] as $key => $val) {
				$tmp_query = "
					SELECT
						md.goodsno, g.goodsnm, g.img_mobile, g.img_l, go.price, g.use_goods_discount, g.speach_description_useyn, g.speach_description, g.runout, g.usestock, g.totstock $_add_field
					FROM
						".GD_MOBILE_DISPLAY." md
						LEFT JOIN ".GD_GOODS." g ON md.goodsno = g.goodsno
						LEFT JOIN ".GD_GOODS_OPTION." go ON md.goodsno = go.goodsno AND link and go_is_deleted <> '1' and go_is_display = '1'
					WHERE
						md.mevent_no=[s] AND md.tab_no=[s]
						$where
					$orderby
					";

				$display_query = $db->_query_print($tmp_query, $req_arr['mevent_no'], $key);
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
						$tmp_arr['goodsnm'] = $row_display['goodsnm'];
						$img_l_arr = explode("|", $row_display['img_l']);
						if (preg_match("/^http/i", $img_l_arr[0])) {
							$tmp_arr['goods_img'] = $img_l_arr[0];
						} else {
							$tmp_arr['goods_img'] = $img_path.$img_l_arr[0];
						}
						if($row_display['img_mobile']) {
							if (preg_match("/^http/i", $row_display['img_mobile'])) {
								$tmp_arr['goods_img'] = $row_display['img_mobile'];
							} else {
								$tmp_arr['goods_img'] = $img_path.$row_display['img_mobile'];
							}
						}
						if ($row_display['speach_description_useyn'] === 'y' && strlen($row_display['speach_description']) > 0) {
							$tmp_arr['tts_url'] = Core::loader('TextToSpeach')->getURL($row_display['speach_description']);
						}
						else {
							$tmp_arr['tts_url'] = '';
						}

						// ��ǰ����	
						if($row_display['use_goods_discount']){
							$tmp_arr['special_discount'] = $goodsDiscountModel->getDiscountUnit($row_display, Clib_Application::session()->getMemberLevel());
						}

						// ǰ����ǰ
						if ( $row_display['runout'] > 0 || $row_display['usestock'] && $row_display['totstock'] < 1) {
							if($cfg_soldout['mobile_display'] === 'overlay')
								$tmp_arr['css_selector'] = 'class="el-goods-soldout-image"';
						}

						// �Ｎ�������� ��ȿ�� �˻� (pc or mobile)
						list($tmp_arr['coupon'], $tmp_arr['coupon_emoney']) = getCouponInfoMobile($row_display['goodsno'], $row_display['price']);

						// �������ο���			
						if($tmp_arr['coupon'] > 0 || $tmp_arr['coupon_emoney'] > 0){
							$tmp_arr['coupon_discount'] = true;
						}

						$tmp_arr['goods_price'] = number_format($row_display['price']).' ��';
						$tmp_res[] = $tmp_arr;
					}
				}
				$res_display[] = $tmp_res;
			}
		}
		break;
	default:
		$tmp_query = "
			SELECT
				md.goodsno, g.goodsnm, g.img_mobile, g.img_l, go.price, g.use_goods_discount, g.speach_description_useyn, g.speach_description, g.runout, g.usestock, g.totstock $_add_field
			FROM
				".GD_MOBILE_DISPLAY." md
				LEFT JOIN ".GD_GOODS." g ON md.goodsno = g.goodsno
				LEFT JOIN ".GD_GOODS_OPTION." go ON md.goodsno = go.goodsno AND link and go_is_deleted <> '1' and go_is_display = '1'
			WHERE
				md.mevent_no=[s]
				$where
			$orderby
			";

		$display_query = $db->_query_print($tmp_query, $req_arr['mevent_no']);
		$tmp_display = $db->_select($display_query);
		$img_path = '';
		if($cfg['rootDir']) {
			$img_path = $cfg['rootDir'].'/data/goods/';
		}
		else {
			$img_path = '/shop/data/goods/';
		}

		$res_display = array();
		//debug($tmp_display);
		if(is_array($tmp_display) && !empty($tmp_display)) {
			foreach($tmp_display as $row_display) {
				$tmp_arr = array();

				$tmp_arr['goodsno'] = $row_display['goodsno'];
				$tmp_arr['goodsnm'] = $row_display['goodsnm'];
				$img_l_arr = explode("|", $row_display['img_l']);
				if (preg_match("/^http/i", $img_l_arr[0])) {
					$tmp_arr['goods_img'] = $img_l_arr[0];
				} else {
					$tmp_arr['goods_img'] = $img_path.$img_l_arr[0];
				}
				if($row_display['img_mobile']) {
					if (preg_match("/^http/i", $row_display['img_mobile'])) {
						$tmp_arr['goods_img'] = $row_display['img_mobile'];
					} else {
						$tmp_arr['goods_img'] = $img_path.$row_display['img_mobile'];
					}
				}
				if ($row_display['speach_description_useyn'] === 'y' && strlen($row_display['speach_description']) > 0) {
					$tmp_arr['tts_url'] = Core::loader('TextToSpeach')->getURL($row_display['speach_description']);
				}
				else {
					$tmp_arr['tts_url'] = '';
				}

				// ��ǰ����	
				if($row_display['use_goods_discount']){
					$tmp_arr['special_discount'] = $goodsDiscountModel->getDiscountUnit($row_display, Clib_Application::session()->getMemberLevel());
				}

				// ǰ����ǰ
				if ( $row_display['runout'] > 0 || $row_display['usestock'] && $row_display['totstock'] < 1) {
					if($cfg_soldout['mobile_display'] === 'overlay')
						$tmp_arr['css_selector'] = 'class="el-goods-soldout-image"';
				}

				// �Ｎ�������� ��ȿ�� �˻� (pc or mobile)
				list($tmp_arr['coupon'], $tmp_arr['coupon_emoney']) = getCouponInfoMobile($row_display['goodsno'], $row_display['price']);

				// �������ο���			
				if($tmp_arr['coupon'] > 0 || $tmp_arr['coupon_emoney'] > 0){
					$tmp_arr['coupon_discount'] = true;
				}

				$tmp_arr['goods_price'] = number_format($row_display['price']).' ��';
				$res_display[] = $tmp_arr;
			}
		}
		break;

}
//debug($res_display);
echo $json->encode($res_display);

unset($req_arr, $list_arr, $arr);

exit;
?>