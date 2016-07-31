<?
/*********************************************************
* ���ϸ�     :  mAjaxAction.php
* ���α׷��� :	����ϼ� Ajax ��� ������
* �ۼ���     :  dn
* ������     :  2012.07.12
* ����ȣ����:  http://������/m2/mAjaxAction.php?mode=get_category&now_cate=001
**********************************************************/
include dirname(__FILE__).'/../lib/library.php';
@include $shopRootDir . "/lib/page.class.php";
@include $shopRootDir . "/lib/json.class.php";
@include $shopRootDir."/lib/goods_qna.lib.php";

$mode = $_POST['mode'];
if(!$mode) $mode = $_GET['mode'];

$json = new Services_JSON(16);
if($_GET['debug']) {
	$mode = 'get_ipaddress';
	$_POST['kw'] = '����';
}

unset($_POST['mode']);

switch($mode) {
	case 'get_category' :
		$now_cate = $_POST['now_cate'];
		if (!$now_cate) $now_cate = $_GET['now_cate'];
		$res_arr = Array();

		if ($cfgMobileShop['vtype_category'] == '0') {
			if($now_cate) {
				$now_cate_len = strlen($now_cate);
				$child_cate_len = $now_cate_len + 3;
				$tmp_cate_path = currPosition($now_cate, 1);
				$cate_path = explode(" > ", $tmp_cate_path);

				$child_query = $db->_query_print('SELECT catnm, category, level, level_auth, auth_step FROM '.GD_CATEGORY.' WHERE hidden=0 AND category LIKE [s] AND category != [s] AND LENGTH(category)=[i] ORDER BY sort', $now_cate.'%', $now_cate, $child_cate_len);
				/* ī�װ� ���� ���� ��� �߰� 2013-06-27 dn START */
				$tmp_child_res = $db->_select($child_query);
				foreach($tmp_child_res as $row_child_res) {

					$member_auth = false;
					//ī�װ� ���� ����
					if($row_child_res['level']){
						switch($row_child_res['level_auth']){
							case '1':
								if( (!$sess['level'] ? 0 : $sess['level']) >= $row_child_res['level'] ) $member_auth = true;
								break;
							default: $member_auth = true; break;
						}
					}
					else $member_auth = true;

					if( $member_auth ){
						$child_res[] = $row_child_res;
					}

				}
				/* ī�װ� ���� ���� ��� �߰� 2013-06-27 dn END */

			}
			else {
				$child_query = $db->_query_print('SELECT catnm, category, level, level_auth, auth_step FROM '.GD_CATEGORY.' WHERE hidden=0 AND LENGTH(category)=[i] ORDER BY sort', 3);
				/* ī�װ� ���� ���� ��� �߰� 2013-06-27 dn START */
				$tmp_child_res = $db->_select($child_query);
				foreach($tmp_child_res as $row_child_res) {

					$member_auth = false;
					//ī�װ� ���� ����
					if($row_child_res['level']){
						switch($row_child_res['level_auth']){
							case '1':
								if( (!$sess['level'] ? 0 : $sess['level']) >= $row_child_res['level'] ) $member_auth = true;
								break;
							default: $member_auth = true; break;
						}
					}
					else $member_auth = true;

					if( $member_auth ){
						$child_res[] = $row_child_res;
					}

				}
				/* ī�װ� ���� ���� ��� �߰� 2013-06-27 dn END */

				$cate_path = array();
			}

			foreach ($child_res as $cate_key => $cate_item) {
				$sub_category_length = strlen($cate_item['category'])+3;
				$sub_category_count_query = $db->_query_print('SELECT count(*) as cnt FROM '.GD_CATEGORY.' WHERE hidden=0 AND category LIKE [s] AND category != [s] AND LENGTH(category)=[i] ORDER BY sort', $cate_item['category'].'%', $cate_item['category'], $sub_category_length);
				$result = $db->_select($sub_category_count_query);
				$child_res[$cate_key]['sub_count'] = $result[0]['cnt'];
			}
		} else {
			if($now_cate) {
				$now_cate_len = strlen($now_cate);
				$child_cate_len = $now_cate_len + 3;
				$tmp_cate_path = currPosition($now_cate, 1);
				$cate_path = explode(" > ", $tmp_cate_path);

				$child_query = $db->_query_print('SELECT catnm, category, level, level_auth, auth_step FROM '.GD_CATEGORY.' WHERE hidden_mobile=0 AND category LIKE [s] AND category != [s] AND LENGTH(category)=[i] ORDER BY sort', $now_cate.'%', $now_cate, $child_cate_len);
				/* ī�װ� ���� ���� ��� �߰� 2013-06-27 dn START */
				$tmp_child_res = $db->_select($child_query);
				foreach($tmp_child_res as $row_child_res) {

					$member_auth = false;
					//ī�װ� ���� ����
					if($row_child_res['level']){
						switch($row_child_res['level_auth']){
							case '1':
								if( (!$sess['level'] ? 0 : $sess['level']) >= $row_child_res['level'] ) $member_auth = true;
								break;
							default: $member_auth = true; break;
						}
					}
					else $member_auth = true;

					if( $member_auth ){
						$child_res[] = $row_child_res;
					}

				}
				/* ī�װ� ���� ���� ��� �߰� 2013-06-27 dn END */
			}
			else {
				$child_query = $db->_query_print('SELECT catnm, category, level, level_auth, auth_step FROM '.GD_CATEGORY.' WHERE hidden_mobile=0 AND LENGTH(category)=[i] ORDER BY sort', 3);
				/* ī�װ� ���� ���� ��� �߰� 2013-06-27 dn START */
				$tmp_child_res = $db->_select($child_query);
				foreach($tmp_child_res as $row_child_res) {

					$member_auth = false;
					//ī�װ� ���� ����
					if($row_child_res['level']){
						switch($row_child_res['level_auth']){
							case '1':
								if( (!$sess['level'] ? 0 : $sess['level']) >= $row_child_res['level'] ) $member_auth = true;
								break;
							default: $member_auth = true; break;
						}
					}
					else $member_auth = true;

					if( $member_auth ){
						$child_res[] = $row_child_res;
					}

				}
				/* ī�װ� ���� ���� ��� �߰� 2013-06-27 dn END */

				$cate_path = array();

			}
			foreach ($child_res as $cate_key => $cate_item) {
				$sub_category_length = strlen($cate_item['category'])+3;
				$sub_category_count_query = $db->_query_print('SELECT count(*) as cnt FROM '.GD_CATEGORY.' WHERE hidden_mobile=0 AND category LIKE [s] AND category != [s] AND LENGTH(category)=[i] ORDER BY sort', $cate_item['category'].'%', $cate_item['category'], $sub_category_length);
				$result = $db->_select($sub_category_count_query);
				$child_res[$cate_key]['sub_count'] = $result[0]['cnt'];
			}
		}
		$res_arr['cate_path'] = $cate_path;
		$res_arr['child_res'] = $child_res;
		echo $json->encode($res_arr);
		break;

	case 'get_goods' :

		@include dirname(__FILE__). "/../../shop/conf/config.soldout.php";

		### �����Ҵ�
		$category = $_POST['category'];
		$kw = iconv('utf-8', 'euc-kr', $_POST['kw']);
		$item_cnt = $_POST['item_cnt'];
		$view_type = $_POST['view_type'];
		$sort_type = $_POST['sort_type'];

		if($view_type == 'gallery') {
			$number = 9;
		}
		else {
			$number = 10;
		}

		if(!$item_cnt) {
			$item_cnt = 0;
			$page = 1;
		}
		else {
			$page = ceil($item_cnt / $number) + 1;
		}

		$goodsHelper   = Clib_Application::getHelperClass('front_goods_mobile');

		if(!$kw) {
			$categoryModel = $goodsHelper->getCategoryModel($category);
		}

		switch($sort_type) {
			case 'regdt' :
				$order_by = 'goods.regdt desc';
				break;
			case 'low_price' :
				$order_by = 'goods.goods_price asc';
				break;
			case 'high_price' :
				$order_by = 'goods.goods_price desc';
				break;
			case 'sort' :
			default:
				if($categoryModel instanceof Clib_Model_Category_Category) {
					$order_by = $categoryModel->getSortColumnName();
				}
				else {
					$order_by = 'goods.regdt desc';
				}
				break;
		}

		// �Ķ���� ����
		if($kw) {
			$params = array(
				'page' => $page,
				'page_num' => $number,
				'keyword' => $kw,
				'sort' => $order_by,
				'item_cnt' => $item_cnt,
			);

			// GROUP BY ó���� ���ؼ� ������ ��ü�� ������
			$params['resetRelationShip'] = array(
				'categories' => array(
					'modelName' => 'goods_link',
					'isCollection' => true,
					'foreignColumn' => 'goodsno',
					'deleteCascade' => true,
					'withoutGroup' => false,
				),
			);
		}
		else {
			$params = array(
				'page' => $page,
				'page_num' => $number,
				'keyword' => $kw,
				'sort' => $order_by,
				'category' => $category,
				'item_cnt' => $item_cnt,
			);
		}

		// ��ǰ ���
		$goodsCollection = $goodsHelper->getGoodsCollection($params);
		
		$ret_goods['goods_data'] = $goodsHelper->getGoodsCollectionArray($goodsCollection, $categoryModel, true);

		echo $json->encode($ret_goods);
		break;

	case 'get_ipaddress' :

		$ret_arr = array();
		$ret_arr['e_ndate'] = date('Y-m-d H:i:s');
		$ret_arr['e_ip'] = $_SERVER['REMOTE_ADDR'];
		$ret_arr['e_device'] = $_SERVER['HTTP_USER_AGENT'];
		//$ret_arr['e_url'] = $_SERVER['REQUEST_URI'];
		$ret_arr['script_name'] = $_SERVER['SCRIPT_NAME'];

		echo $json->encode($ret_arr);
		break;

	case 'set_visitdata' :

		$ins_arr = array();
		$ins_arr = $_POST;

		if(!$ins_arr['top_mvisit_idx'] && !$ins_arr['up_mvisit_idx']) {

			$visit_date = date('Y-m-d');
			$revisit_yn_query = $db->_query_print('SELECT mvisit_idx FROM '.GD_MOBILE_VISIT.' WHERE visit_ip=[s] AND visit_device=[s] AND DATE_FORMAT(visit_date, [s])=[s]', $ins_arr['visit_ip'], $ins_arr['visit_device'], '%Y-%m-%d', $visit_date);

			$res_revisit_yn = $db->_select($revisit_yn_query);

			if($res_revisit_yn[0]['mvisit_idx']) {
				$ins_arr['revisit_yn'] = 'Y';
			}
		}

		if($ins_arr['up_mvisit_idx'] && $ins_arr['view_second']) {
			$view_query = $db->_query_print('UPDATE '.GD_MOBILE_VISIT.' SET view_second=[i] WHERE mvisit_idx=[i]', $ins_arr['view_second'], $ins_arr['up_mvisit_idx']);
			$db->query($view_query);
			unset($ins_arr['view_second']);
		}

		$set_query = $db->_query_print('INSERT INTO '.GD_MOBILE_VISIT.' SET [cv]', $ins_arr);

		$res_set = $db->query($set_query);
		$ret['mvisit_idx'] = $db->_last_insert_id();

		if(!$ins_arr['top_mvisit_idx'] && !$ins_arr['up_mvisit_idx']) {
			$upd_arr['top_mvisit_idx'] = $ret['mvisit_idx'];
			$upd_arr['up_mvisit_idx'] = $ret['mvisit_idx'];

			$upd_query = $db->_query_print('UPDATE '.GD_MOBILE_VISIT.' SET [cv] WHERE mvisit_idx=[i]', $upd_arr, $ret['mvisit_idx']);

			$db->query($upd_query);

			unset($upd_arr);
		}

		unset($ins_arr, $res_set, $set_query);

		echo $json->encode($ret);

		break;

	case 'set_analysisdata' :

		$ins_arr = array();
		$ins_arr = $_POST;

		$set_query = $db->_query_print('INSERT INTO '.GD_MOBILE_ANALYSIS.' SET [cv]', $ins_arr);
		$res_query = $db->query($set_query);


		break;

	case 'id_check' :
		$id = $_POST['id'];

		$chk_query = $db->_query_print('SELECT count(m_no) as cnt_mem FROM '.GD_MEMBER.' WHERE m_id=[s]', $id);
		$res_chk = $db->_select($chk_query);

		$res_chk = $res_chk[0];

		$ret = array();
		if($res_chk['cnt_mem'] > 0) {
			$ret['code'] = 'n';
			$ret['msg'] = '������ ���̵� �̹� ��ϵǾ� �ֽ��ϴ�';
		}
		else {
			$ret['code'] = 'y';
			$ret['msg'] = '��ϰ����� ���̵� �Դϴ�';
		}

		if (preg_match('/^[a-zA-Z0-9_-]{6,16}$/',$id) < 1)
		{
			$ret['code'] = 'n';
			$ret['msg'] = '���̵𿡴� ����, ����, -, _ ���ڸ� ����� �� �ֽ��ϴ�.';
		}

		echo $json->encode($ret);

		break;

	case 'get_option' :
		$goodsno = $_POST['goodsno'];
		$responseData = array();
		$optionList = array();
		$optionCombination = array();

		if (isset($_POST['id'])) {
			$cart = new Cart;
			$item = $cart->item[$_POST['id']];
			$item['addopt_sno'] = array();
			$item['addopt_value'] = array();
			if (is_array($item['addopt'])) foreach($item['addopt'] as $addopt) {
				$item['addopt_sno'][] = $addopt['sno'];
				$item['addopt_value'][] = $addopt['opt'];
			}
		}
		else {
			$item = array();
		}

		$goods = $db->fetch('SELECT optnm, usestock, opttype, addoptnm, min_ea, max_ea, sales_unit FROM '.GD_GOODS.' WHERE goodsno='.$goodsno, 1);
		$responseData['name'] = explode('|', $goods['optnm']);
		$responseData['stockable'] = ($goods['usestock'] == 'o');
		$responseData['type'] = $goods['opttype'];
		$responseData['addoptnm'] = array();
		$responseData['addoptreq'] = array();

		$responseData['min_ea'] = $goods['min_ea'];
		$responseData['max_ea'] = $goods['max_ea'];
		$responseData['sales_unit'] = $goods['sales_unit'];

		foreach (explode('|', $goods['addoptnm']) as $addoptnm) {
			$_addoptnm = explode('^', $addoptnm);
			$responseData['addoptnm'][] = $_addoptnm[0];
			$responseData['_addoptreq'][] = ($_addoptnm[1] == 'o');
			$responseData['_addopttype'][] = $_addoptnm[2];
		}
		if (strlen($responseData['name'][0]) < 1) $responseData['name'][0] = '����1';
		if (strlen($responseData['name'][1]) < 1) $responseData['name'][1] = '����2';

		// �ɼǱ���
		$lookupOption = $db->query('SELECT opt1, opt2, price, stock FROM '.GD_GOODS_OPTION.' WHERE go_is_deleted <> \'1\' and go_is_display = \'1\' and goodsno='.$goodsno);
		while ($option = $db->fetch($lookupOption, 1)) {
			// �ɼ�1 ����
			if ($option['opt1']) {
				if (isset($optionList[0]) === false) $optionList[0] = array();
				if (in_array($option['opt1'], $optionList[0]) === false) $optionList[0][] = $option['opt1'];
			}
			// �ɼ�2 ����
			if ($option['opt2']) {
				if (isset($optionList[1]) === false) $optionList[1] = array();
				if (in_array($option['opt2'], $optionList[1]) === false) $optionList[1][] = $option['opt2'];
			}
			if ($responseData['type'] == 'double') {
				$responseData['list'] = $optionList;
			}
			$optionCombination[$option['opt1'].'/'.$option['opt2']] = $option;
		}
		if (count($optionCombination) == 1) $optionCombination = null;
		if (count($optionList[0]) < 1) unset($responseData['name'][0]);
		if (count($optionList[1]) < 1) unset($responseData['name'][1]);
		$responseData['combination'] = $optionCombination;

		// �߰��ɼ� ����
		$responseData['addopt'] = array();
		$lookupAddOption = $db->query('SELECT sno, step, opt, addprice, type FROM '.GD_GOODS_ADD.' WHERE goodsno='.$goodsno. ' ORDER BY type, step, sno');
		$_offset = 0;
		while ($addoption = $db->fetch($lookupAddOption, 1)) {

			if ($addoption['type'] == 'I') {
				$_offset = (int) array_search('I', $responseData['_addopttype']);

				if (($key = array_search($addoption['sno'], $item['addopt_sno'])) !== false) {
					$addoption['value'] = $item['addopt_value'][$key];
				}

				$responseData['addopt_inputable'][$responseData['addoptnm'][$_offset + $addoption['step']]] = $addoption;
				$responseData['addopt_inputable_req'] = array_slice($responseData['_addoptreq'], $_offset);
			}
			else {
				$responseData['addopt'][$responseData['addoptnm'][$addoption['step']]][] = $addoption;
				$responseData['addoptreq'] = $_offset > 0 ? array_slice($responseData['_addoptreq'], 0, $_offset) : $responseData['_addoptreq'];
			}

		}

		unset($responseData['_addoptreq']);
		unset($responseData['_addopttype']);

		echo $json->encode($responseData);
		break;

	case 'get_cart_item':
		$orderitem_mode = 'cart';
		$cart = new Cart;
		$responseData = array(
			'quantity' => count($cart->item),
		);
		echo $json->encode($responseData);
		break;

	case 'get_faq' :
		@include dirname(__FILE__). "/../conf/config.php";
		# �����ڵ� ����
		$summary_search = array();
		$summary_search[] = "/__shopname__/is";			# ���θ��̸�
		$summary_search[] = "/__shopdomain__/is";		# ���θ��ּ�
		$summary_search[] = "/__shopcpaddr__/is";		# ������ּ�
		$summary_search[] = "/__shopcoprnum__/is";		# ����ڵ�Ϲ�ȣ
		$summary_search[] = "/__shopcpmallceo__/is";	# ���θ� ��ǥ
		$summary_search[] = "/__shopcpmanager__/is";	# ��������������
		$summary_search[] = "/__shoptel__/is";			# ���θ� ��ȭ
		$summary_search[] = "/__shopfax__/is";			# ���θ� �ѽ�
		$summary_search[] = "/__shopmail__/is";			# ���θ� �̸���

		$summary_replace = array();
		$summary_replace[] = $cfg["shopName"];			# ���θ��̸�
		$summary_replace[] = $cfg["shopUrl"];			# ���θ��ּ�
		$summary_replace[] = $cfg["address"];			# ������ּ�
		$summary_replace[] = $cfg["compSerial"];		# ����ڵ�Ϲ�ȣ
		$summary_replace[] = $cfg["ceoName"];			# ���θ� ��ǥ
		$summary_replace[] = $cfg["adminName"];			# ��������������
		$summary_replace[] = $cfg["compPhone"];			# ���θ� ��ȭ
		$summary_replace[] = $cfg["compFax"];			# ���θ� �ѽ�
		$summary_replace[] = $cfg["adminEmail"];		# ���θ� �̸���

		### �����Ҵ�
		$itemcd = $_POST['itemcd'];
		$item_cnt = $_POST['item_cnt'];
		$number = 10;
		if(!$item_cnt) {
			$item_cnt = 0;
			$page = 1;
		}
		else {
			$page = ceil($item_cnt / $number) + 1;
		}

		### FAQ ����Ʈ
		$db_table = "".GD_FAQ."";
		$arr_where[] = "itemcd='$itemcd'";

		$where = implode(' AND ', $arr_where);

		$faq_query = $db->_query_print('SELECT sno, itemcd, question, descant, answer FROM '.$db_table.' WHERE '.$where.' ORDER BY sort');

		$res_faq = $db->_select_page($number, $page, $faq_query);

		if(!empty($res_faq['record']) && is_array($res_faq['record'])) {
			foreach($res_faq['record'] as $row_record) {
				if ( blocktag_exists( $row_record[descant] ) == false ){
					$row_record[descant] = nl2br($row_record[descant]);
				}

				$row_record[descant] = preg_replace( $summary_search, $summary_replace, $row_record[descant] );

				if ( blocktag_exists( $row_record[answer] ) == false ){
					$row_record[answer] = nl2br($row_record[answer]);
				}

				$row_record[answer] = preg_replace( $summary_search, $summary_replace, $row_record[answer] );

				$ret_faq['faq_data'][] = $row_record;
			}
		} else {
			$ret_faq['faq_data'] = "";
		}


		echo $json->encode($ret_faq);
		break;

	case 'get_view_goods_data' :

		$item_cnt = $_POST['item_cnt'];

		$serialize_goods_data = $_COOKIE['todayGoodsMobile'];
		$goods_arr = unserialize(stripslashes($serialize_goods_data));

		if(!empty($goods_arr) && is_array($goods_arr)) {
			$idx = 0;
			foreach($goods_arr as $goods_row) {

				if($idx > $item_cnt + 8) break;

				if($idx > $item_cnt-1) {
					$goods_row['img_html'] = goodsimgMobile($goods_row['img'],100);
					if ($goods_row['strprice']) {
						$goods_row['price'] = $goods_row['strprice'];
					}
					else {
						$goods_row['price'] = number_format($goods_row['price']).'��';
					}
					$goods_data[] = $goods_row;
				}

				$idx ++;
			}
		}

		echo $json->encode($goods_data);

		break;

	case 'get_review' :

		$number = 10;
		$item_cnt = $_POST['item_cnt'];

		if ($_POST['all']) {
			$review_where[] = "a.sno = a.parent";
			$review_where[] = "a.notice != '1'";
		}
		else if ($_POST['goodsno']) {
			$review_where[] = "a.goodsno = '$_POST[goodsno]'";
			$review_where[] = "a.sno = a.parent";
		}
		else {
			$review_where[] = "a.m_no = '$sess[m_no]'";
			$review_where[] = "a.sno = a.parent";
		}

		if(!$item_cnt) {
			$item_cnt = 0;
			$page = 1;
		}
		else {
			$page = ceil($item_cnt / $number) + 1;
		}

		$pg_review = new Page($page,$number);
		$pg_review->field = "a.sno, a.goodsno, a.subject, a.contents, a.point, a.regdt, a.name, b.m_no, b.m_id, a.attach, a.parent";
		$db_table = "".GD_GOODS_REVIEW." a left join ".GD_MEMBER." b on a.m_no=b.m_no";

		$pg_review->setQuery($db_table,$review_where,$sort="a.sno desc, a.regdt desc");
		$pg_review->exec();

		$res = $db->query($pg_review->query);

		$review_cnt = 0;
		while ($review_data=$db->fetch($res)){

			if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
				$review_data = validation::xssCleanArray($review_data, array(
				    validation::DEFAULT_KEY => 'text',
				    'contents' => array('html', 'ent_noquotes'),
					'subject' => array('html', 'ent_noquotes'),
				));
			}

			$review_data['idx'] = $pg_review->idx--;
			$review_data[contents] = nl2br(htmlspecialchars($review_data[contents]));
			$review_data[point] = sprintf( "%0d", $review_data[point]);

			$query = "select b.goodsnm,b.img_s,c.price
			from
				".GD_GOODS." b
				left join ".GD_GOODS_OPTION." c on b.goodsno=c.goodsno and link and go_is_deleted <> '1' and go_is_display = '1'
			where
				b.goodsno = '" . $review_data[goodsno] . "'";
			list( $review_data[goodsnm], $review_data[img_s], $review_data[price] ) = $db->fetch($query);

			$review_data['img_html'] = goodsimgMobile($review_data[img_s],100);
			
			$reply_query = "SELECT subject, contents, regdt, sno, m_no FROM ".GD_GOODS_REVIEW." WHERE parent='".$review_data[sno]."' AND sno != parent";

			$reply_res = $db->_select($reply_query);

			$review_data['reply'] =$reply_res;

			$review_data['reply_cnt'] = count($reply_res);


			if ($review_data[attach] == 1) {
				$review_data[image] = '<img src="../../shop/data/review/'.'RV'.sprintf("%010s", $review_data[sno]).'">';
			}
			else $review_data[image] = '';

			$review_data['review_name'] = '';

			/* encoding ����Ͽ� ���� �ؾ� �� */
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
					$review_data['point_star'] .= '<span class="active">��</span>';
				}
				else {
					$review_data['point_star'] .= '��';
				}
			}

			$review_data[authdelete] = 'Y'; # ���� �����ʱⰪ

			if ( empty($cfg['reviewWriteAuth']) || isset($sess) || !empty($review_data[m_no]) ){ // ȸ������ or ȸ�� or �ۼ���==ȸ��
				$review_data[authdelete] = ( isset($sess) && $sess[m_no] == $review_data[m_no] ? 'Y' : 'N' );
			}
			//***
			if(!empty($review_data['reply'])){
				for($i=0; $i<$review_data['reply_cnt']; $i++){
					$review_data['reply'][$i][authdelete] = 'Y';
					if ( empty($cfg['reviewWriteAuth']) || isset($sess) || !empty($review_data['reply'][$i][m_no]) ){
						$review_data['reply'][$i][authdelete] = ( isset($sess) && $sess[m_no] == $review_data['reply'][$i][m_no] ? 'Y' : 'N' );
					}
				}
			}

			$review_data[authdelete] = ( $review_data[reply_cnt] > 0 ? 'N' : $review_data[authdelete] ); # ��� �ִ� ��� ���� �Ұ�

			$review_loop[] = $review_data;

		}

		echo $json->encode($review_loop);
		break;

	case 'get_qna' :

		$number = 10;
		$item_cnt = $_POST['item_cnt'];

		if($_POST['isAll'] == 'Y'){
			$qna_where[] = "a.sno = a.parent";
			$qna_where[] = "a.notice != '1'";
		}
		else if($_POST['goodsno']) {
			$qna_where[] = "a.goodsno = '$_POST[goodsno]'";
			$qna_where[] = "a.sno = a.parent";
		}
		else {
			$qna_where[] = "a.m_no = '$sess[m_no]'";
			$qna_where[] = "a.sno = a.parent";
		}

		if(!$item_cnt) {
			$item_cnt = 0;
			$page = 1;
		}
		else {
			$page = ceil($item_cnt / $number) + 1;
		}

		$pg_qna = new Page($page, $number);
		$pg_qna -> field = "b.m_no, b.m_id,b.name as m_name,a.*";

		$where[]="notice!='1'";
		$pg_qna->setQuery($db_table=GD_GOODS_QNA." a left join ".GD_MEMBER." b on a.m_no=b.m_no",$qna_where,$sort="parent desc, ( case when parent=a.sno then 0 else 1 end ) asc, regdt desc");
		$pg_qna->exec();


		$res = $db->query($pg_qna->query);
		while ($qna_data=$db->fetch($res)){

			if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
				$qna_data = validation::xssCleanArray($qna_data, array(
				    validation::DEFAULT_KEY => 'text',
				    'contents' => array('html', 'ent_noquotes'),
					'subject' => array('html', 'ent_noquotes'),
				));
			}

			$qna_data['idx'] = $pg_qna->idx--;
			### ���� üũ
			list($qna_data['parent_m_no'],$qna_data['secret'],$qna_data['type']) = goods_qna_answer($qna_data['sno'],$qna_data['parent'],$qna_data['secret']);
			
			$reply_query = "SELECT subject, contents, regdt, sno, m_no FROM ".GD_GOODS_QNA." WHERE parent='".$qna_data[sno]."' AND sno != parent";
			$reply_res = $db->_select($reply_query);

			$qna_data['reply'] =$reply_res;
			$qna_data['reply_cnt'] = count($reply_res);

			### ����üũ
			if(!$cfg['qnaSecret']) $qna_data['secret'] = 0;
			list($qna_data['authmodify'],$qna_data['authdelete'],$qna_data['authview']) = goods_qna_chkAuth($qna_data);

			### ��� ���� üũ//***
			if(!empty($qna_data['reply'])){
				for($i=0; $i<$qna_data['reply_cnt']; $i++){
					list($qna_data['reply'][$i]['authmodify'],$qna_data['reply'][$i]['authdelete'],$qna_data['reply'][$i]['authview']) = goods_qna_chkAuth($qna_data['reply'][$i]);
				}
			}

			$query = "select b.goodsnm,b.img_s,c.price
			from
				".GD_GOODS." b
				left join ".GD_GOODS_OPTION." c on b.goodsno=c.goodsno and link and go_is_deleted <> '1' and go_is_display = '1'
			where
				b.goodsno = '" . $qna_data[goodsno] . "'";
			list( $qna_data[goodsnm], $qna_data[img_s], $qna_data[price] ) = $db->fetch($query);

			$qna_data['img_html'] = goodsimgMobile($qna_data[img_s],100);

			### ��б� ������
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

		echo $json->encode($qna_loop);
		break;

	case 'get_order_list_data' :

		$db_table = "".GD_ORDER."";

		$where[] = "m_no = '$sess[m_no]'";

		$number = 10;
		$item_cnt = $_POST['item_cnt'];

		if(!$item_cnt) {
			$item_cnt = 0;
			$page = 1;
		}
		else {
			$page = ceil($item_cnt / $number) + 1;
		}

		$pg = new Page($page, $number);
		$pg->setQuery($db_table,$where,"ordno desc");
		$pg->exec();


		$res = $db->query($pg->query);
		$idx = 0;
		while ($data=$db->fetch($res)){
			$idx ++;
			$data[str_step] = (!$data[step2]) ? $r_step[$data[step]] : $r_step2[$data[step2]];
			$data[str_settlekind] = $r_settlekind[$data[settlekind]];
			if($data[prn_settleprice]) $data[settleprice] = $data[prn_settleprice];
			$data[idx] = $idx;
			$data[str_settleprice] = number_format($data[settleprice]);
			$select_count_item = '(select count(*) from '.GD_ORDER_ITEM.' as s_oi where s_oi.ordno=[s]) as count_item';

			$goodsnm_query = $db->_query_print('SELECT '.$select_count_item.', oi.goodsnm FROM '.GD_ORDER_ITEM.' oi WHERE oi.ordno=[s]', $data['ordno'], $data['ordno']);

			$res_goodsnm = $db->_select($goodsnm_query);
			$row_goodsnm = $res_goodsnm[0];

			$data['goodsnm'] = $row_goodsnm['goodsnm'];

			if(!$data['goodsnm']) $data['goodsnm'] = '';

			if($row_goodsnm['count_item'] > 1) $data['goodsnm'] .= ' �� '.($row_goodsnm['count_item'] - 1).'��';
			$loop[] = $data;

			unset($row_goodsnm, $res_goodsnm, $goosnm_query);
		}

		echo $json->encode($loop);
		break;

	case 'get_log_emoney' :

		$number = 10;
		$item_cnt = $_POST['item_cnt'];

		if(!$item_cnt) {
			$item_cnt = 0;
			$page = 1;
		}
		else {
			$page = ceil($item_cnt / $number) + 1;
		}

		$pg = new Page($page, $number);
		$db_table = "".GD_LOG_EMONEY."";
		$pg->field = "*, date_format( regdt, '%Y.%m.%d' ) as regdts"; # �ʵ� ����
		$where[] = "m_no='$sess[m_no]' AND emoney != 0";
		$pg->setQuery($db_table,$where,$orderby="regdt desc");
		$pg->exec();

		$res = $db->query($pg->query);
		while ($data=$db->fetch($res)){
			$data['idx'] = $pg->idx--;
			$loop[] = $data;
		}

		echo $json->encode($loop);
		break;

	case 'get_member_qna' :

		$number = 10;
		$item_cnt = $_POST['item_cnt'];

		if(!$item_cnt) {
			$item_cnt = 0;
			$page = 1;
		}
		else {
			$page = ceil($item_cnt / $number) + 1;
		}


		$pg_member_qna = new Page($page,$number);
		$pg_member_qna->field = "distinct a.sno, a.parent, a.itemcd, a.subject, a.contents, a.ordno, a.regdt as regdt, b.m_no, b.m_id, b.name, a.notice";
		$db_table = "".GD_MEMBER_QNA." a left join ".GD_MEMBER." b on a.m_no=b.m_no";

		$member_qna_where[] = "a.m_no = '$sess[m_no]'";
		$member_qna_where[] = "a.sno = a.parent or notice='1' or a.sno in (select parent from ".GD_MEMBER_QNA." where m_no='$sess[m_no]')";

		$pg_member_qna->setQuery($db_table,$member_qna_where,$sort="a.notice desc, a.sno desc, a.regdt desc");
		$pg_member_qna->exec();
		$itemcds = codeitem( 'question' ); # ��������

		$res_member_qna = $db->query($pg_member_qna->query);
		while ($data_member_qna=$db->fetch($res_member_qna)){

			$data_member_qna['idx'] = $pg_member_qna->idx--;

			$data_member_qna[itemcd] = $itemcds[ $data_member_qna[itemcd] ];
			$data_member_qna[contents] = nl2br($data_member_qna[contents]); 

			if($data_member_qna['notice'] == 1) {	//notice �ʵ尪�� 1�̸�
				$data_member_qna['itemcd'] = "��������";	//���������� ������������ ����
			}

			$reply_query = "SELECT subject, contents, regdt FROM ".GD_MEMBER_QNA." WHERE parent='".$data_member_qna[sno]."' AND sno != parent";
			$reply_res = $db->_select($reply_query);

			$data_member_qna['reply'] =$reply_res;
			$data_member_qna['reply_cnt'] = count($reply_res);

			$member_qna_loop[] = $data_member_qna;
		}

		echo $json->encode($member_qna_loop);
		break;
	case 'get_board' :
		$number = 10;
		$item_cnt = $_POST['item_cnt'];
		if(!$item_cnt) {
			$item_cnt = 0;
			$page = 1;
		}
		else {
			$page = ceil($item_cnt / $number) + 1;
		}

		include_once "../..".$cfg['rootDir']."/conf/bd_".$_POST['id'].".php";

		### bd class
		$bd = new mobile_board($page,$number);

		$bd->db		= &$db;

		$bd->cfg	= &$cfg;
		if ( file_exists( dirname(__FILE__) . '/../..'.$cfg['rootDir'].'/data/skin/' . $cfg['tplSkin'] . '/admin.gif') ) $bd->adminicon = 'admin.gif';

		$bd->id			= $_POST['id'];
		$bd->subSpeech	= $_POST['subSpeech'];
		$bd->search		= $_POST['search'];
		$bd->sess		= $sess;
		$bd->ici_admin	= $ici_admin;
		$bd->date		= $_POST['date'];

		$bd->assign(array(
					bdSearchMode		=> $bdSearchMode,
					bdUseSubSpeech		=> $bdUseSubSpeech,
					bdSubSpeech			=> $bdSubSpeech,
					bdSubSpeechTitle	=> $bdSubSpeechTitle,
					bdLvlR				=> $bdLvlR,
					bdLvlW				=> $bdLvlW,
					bdStrlen			=> $bdStrlen,
					bdNew				=> $bdNew,
					bdHot				=> $bdHot,
					));

		$loop = $bd->getList();
		echo $json->encode($loop);

		break;

	case 'get_goods_html' :
		@include dirname(__FILE__). "/../../shop/conf/config.soldout.php";
		@include dirname(__FILE__). "/../../shop/conf/config.display.php";
		include $shopRootDir . "/Template_/Template_.class.php";
		include_once $shopRootDir . "/lib/tplSkinMobileView.php";
		if(!$cfgMobileShop['tplSkinMobile']) $cfgMobileShop['tplSkinMobile'] = 'default';

		$tpl = new Template_;
		$tpl->template_dir	= $shopRootDir."/data/skin_mobileV2/".$cfgMobileShop['tplSkinMobile'];
		$tpl->compile_dir	= $shopRootDir."/Template_/_compiles/skin_mobileV2/".$cfgMobileShop['tplSkinMobile'];
		$tpl->prefilter		= "adjustPath|include_file|capture_print";
		
		if ($_POST['kw']) { // �ڹٽ�ũ��Ʈ���� UTF-8�� �ѱ��� �Ѿ�� �ݵ�� EUC-KR�� ��ȯ�ؾ� ��
			$_POST['kw'] = iconv('UTF-8', 'EUC-KR', $_POST['kw']);
		}

		// ��ǰ�з� ������ ��ȯ ���ο� ���� ó��
		$whereArr	= getCategoryLinkQuery('CMGL0.category', $_POST['category']);

		// ī�װ� �� ��ǰ���� for paging
		$query = " SELECT ";
		$query.= " COUNT(".$whereArr['distinct']." CMGG0.goodsno) AS __CNT__ ";
		$query.= " FROM ".GD_GOODS." AS CMGG0 ";
		$query.= " INNER JOIN ".GD_GOODS_LINK." AS CMGL0 ON CMGG0.goodsno = CMGL0.goodsno ";
		$query.= " WHERE  (CMGL0.hidden = '0') ";
		$query.= " and ".$whereArr['where'];
		$query.= " and (CMGG0.open = '1') ";

		// ǰ�� ��ǰ ����
		if ($cfg_soldout['exclude_category']) {
			$query.= "and !( CMGG0.runout = 1 OR (CMGG0.usestock = 'o' AND CMGG0.usestock IS NOT NULL AND CMGG0.totstock < 1) ) ";
		}

		//DB Cache ��� 141030
		$dbCache = Core::loader('dbcache')->setLocation('goodslist');

		if (!$out = $dbCache->getCache($query)) {
			$totalCount = $db->fetch($query); // ��ü ���ڵ�
			if ($totalCount && $dbCache) $dbCache->setCache($query, $totalCount);
		} else {
			$totalCount = $out;
		}

		$goodsDisplay = Core::loader('Mobile2GoodsDisplay');
		$goods_data = $goodsDisplay->getMobileCategoryDisplayGoods($_POST);

		if ($_POST['kw']) {
			$totalCount['__CNT__'] = $goods_data['total'];
			$pg = $goods_data['pg'];
		} else {
			// ����¡ ó��
			$offset[0] = $params['page'];
			$offset[1] = $params['page_num'];
			$total_count = $totalCount['__CNT__'];
			if ($total_count % $offset[1]) {
				$totalpage = (int)($total_count / $offset[1]) + 1;
			}
			else {
				$totalpage = $total_count / $offset[1];
			}

			// ����¡
			$pg = new Page($offset[0], $offset[1]);
			$pg->recode['total'] = $total_count;
			$pg->page['total'] = $totalpage;
			$pg->idx = $pg->recode['total'] - $pg->recode['start'];
			$pg->setNavi($tpl2 = '');
			$pg->query = $query;
		}

		if ($_POST['view_type'] == 'gallery') {
			$key_file = 'tpl_02';
		} else {
			$key_file = 'tpl_01';
		}

		$tpl->define( array(
			'tpl'	=> 'goods/list/' . $key_file . '.htm',
		) );
		$tpl->assign(array(
			'loop' => $goods_data['goods_data'],
			'goods_src' => $goods_data['goods_src'],
		));

		$result = array(
			'html' => $tpl->fetch('tpl'),
			'total' => $totalCount['__CNT__'],
			'page' => $pg->page['now'],//�۵��ȵ�
			'is_last_page' => ($pg->page['total'] == $pg->page['now']),
		);
		
		echo $json->encode($result);
		break;

	case 'get_brand_html' :
		@include dirname(__FILE__). "/../../shop/conf/config.soldout.php";
		include $shopRootDir . "/Template_/Template_.class.php";
		include_once $shopRootDir . "/lib/tplSkinMobileView.php";
		if(!$cfgMobileShop['tplSkinMobile']) $cfgMobileShop['tplSkinMobile'] = 'default';

		$tpl = new Template_;
		$tpl->template_dir	= $shopRootDir."/data/skin_mobileV2/".$cfgMobileShop['tplSkinMobile'];
		$tpl->compile_dir	= $shopRootDir."/Template_/_compiles/skin_mobileV2/".$cfgMobileShop['tplSkinMobile'];
		$tpl->prefilter		= "adjustPath|include_file|capture_print";

		$goodsDisplay = Core::loader('Mobile2GoodsDisplay');
		$goods_data = $goodsDisplay->getMobileBrandDisplayGoods($_POST);

		if ($_POST['view_type'] == 'gallery') {
			$key_file = 'tpl_02';
		} else {
			$key_file = 'tpl_01';
		}

		$tpl->define( array(
			'tpl'	=> 'goods/list/' . $key_file . '.htm',
		) );
		$tpl->assign(array(
			'loop' => $goods_data['goods_data'],
			'goods_src' => $goods_data['goods_src'],
		));

		$result = array(
			'html' => $tpl->fetch('tpl'),
			'total' => $goods_data['pg']->recode['total'],
			'page' => $goods_data['pg']->page['now'],//�۵��ȵ�
			'is_last_page' => ($goods_data['pg']->page['total'] == $goods_data['pg']->page['now']),
		);

		echo $json->encode($result);
		break;
}
?>

