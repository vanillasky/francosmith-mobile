<? /*********************************************************
 * ���ϸ�     :  list.php
 * ���α׷��� :	��ǰ����Ʈ ������
 * �ۼ���     :  dn
 * ������     :  2012.05.31
 **********************************************************/

include dirname(__FILE__) . "/../_header.php";
@include dirname(__FILE__). "/../../shop/conf/config.soldout.php";

if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
	$_GET = validation::xssCleanArray($_GET, array(
		validation::DEFAULT_KEY	=> 'text'
	));
}

try {

	// ������ ����
	$page_title = 'ī�װ�';

	// ī�װ� ȣ�� (1�� �з��� ȣ���ϰ� 2�� �з����ʹ� Ajax�� ȣ��)
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

	// ���ø� ���� �Ҵ� �� ȭ�� ���
	$tpl->assign(array(
		'page_title' => $page_title,
		'loop' => $res_arr['child_res'],
	));
	$tpl->print_('tpl');

} catch (Clib_Exception $e) {
	Clib_Application::response()->jsAlert($e)->historyBack();
}
