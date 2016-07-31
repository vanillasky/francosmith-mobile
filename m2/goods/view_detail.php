<?php
	include dirname(__FILE__) . "/../_header.php";
	@include $shopRootDir . "/conf/config.pay.php";
	@include $shopRootDir . "/conf/sns.cfg.php";
	@include $shopRootDir . "/conf/coupon.php";

	### �����Ҵ�
	$goodsno = $_GET['goodsno'];

	### ī�װ� ��ȸ ���� üũ
	$res = $db->query("select a.category,b.level,b.level_auth,b.auth_step from ".GD_GOODS_LINK." a left join ".GD_CATEGORY." b on a.category=b.category where a.goodsno='$goodsno'");
	$tmp_chk=false;
	while($tmp = $db->fetch($res)){
		if(strncmp($tmp['category'],$_GET['category'],strlen($_GET['category']))==0) $tmp_chk=true;
		if($tmp['level']){//���Ѽ�������
			if($tmp['level'] <= $sess['level']) continue;
			switch($tmp['level_auth']){//����üũ
				case '1'://��μ���
				case '2'://ī�װ���
				case '3'://��ǰ����Ʈ
					msg('�̿� ������ �����ϴ�.\\nȸ������� ���ų� ȸ�������� �ʿ��մϴ�.',getReferer());
					break;
			}
		}
	}
	if(!$tmp_chk) $_GET['category']='';

	if(!$set['emoney']['cut'])$set['emoney']['cut']=0;
	$set['emoney']['base'] = pow(10,$set['emoney']['cut']);

	// ��ǰ�з� ������ ��ȯ ���ο� ���� ó��
	$whereArr	= getCategoryLinkQuery('b.category', $db->_escape($_GET['category']));
	if ($_GET[category]) $qrTmp = "and ".$whereArr['where'];

	### ȸ������ ��������
	if ($sess){
		$query = "
		select * from
			".GD_MEMBER." a
			left join ".GD_MEMBER_GRP." b on a.level=b.level
		where
			m_no='$sess[m_no]'
		";
		$member = $db->fetch($query,1);
	}else{
		### �⺻ ������
		@include "../conf/fieldset.php";
		$member = $db -> fetch("select * from ".GD_MEMBER_GRP." where level='".$joinset[grp]."' limit 1");
	}

	### ȸ������ ���ܻ�ǰ üũ
	$mdc_exc = chk_memberdc_exc($member,$goodsno);

	### ��ǰ ����Ÿ
	if ($ici_admin === false) $where = "c.hidden_mobile=0 and";
	$query = "
	select a.*,".$whereArr['max'].", c.level, c.level_auth, c.auth_step from
		".GD_GOODS." a
		left join ".GD_GOODS_LINK." b on a.goodsno=b.goodsno $qrTmp
		left join ".GD_CATEGORY." c on b.category=c.category
	where $where
		a.goodsno='$goodsno'
	limit 1
	";
	$data = $db->fetch($query,1);

	$cauth_step = explode(':', $data['auth_step']);
	$data['auth_step'] = array();
	$data['auth_step'][0] = (in_array('1', $cauth_step) ? 'Y' : 'N' ) ;
	$data['auth_step'][1] = (in_array('2', $cauth_step) ? 'Y' : 'N' ) ;
	$data['auth_step'][2] = (in_array('3', $cauth_step) ? 'Y' : 'N' ) ;

	$tpl->assign(array('clevel'	=> $data['level'],
						'slevel'=> $sess['level'],
						'level_auth'=> $data['level_auth']));

	### ��ǰ ���� ���� üũ
	if (!$data[open_mobile]) msg("�ش��ǰ�� ������ ���� ��ǰ�� �ƴմϴ�",-1);

	list ($point) = $db->fetch("select round(avg(point)) from ".GD_GOODS_REVIEW." where goodsno='$goodsno' and sno=parent");
	$data[chk_point] = $point;
	$data[point] = ($point) ? $point : 5;

	list( $data[brand] ) = $db->fetch("select brandnm from ".GD_GOODS_BRAND." where sno='$data[brandno]'");

	if (!$_GET[category]) $_GET[category] = $data[category];

	/* ��ǰ�� ī�װ� ȯ�� ���� ���� �������� */
	@include "../conf/category/{$_GET['category']}.php";

	$category = $_GET[category];

	### �ʼ��ɼ� ���Ÿ�� (��ü��[single]/�и���[double])
	$typeOption = $data[opttype];

	### �߰����� ����
	$data[ex_title] = explode("|",$data[ex_title]);
	foreach ($data[ex_title] as $k=>$v) $data[ex][$v] = $data["ex".($k+1)];
	$data[ex] = array_notnull($data[ex]);

	### ������
	$data[icon] = setIcon($data[icon],$data[regdt]);

	### �̹��� �迭
	$data[r_img] = explode("|",$data[img_m]);
	$data[l_img] = explode("|",$data[img_l]);
	$data[t_img] = array_map("toThumb",$data[r_img]);

	### �ʼ��ɼ� (���� ����)
	$optnm = explode("|",$data[optnm]);
	$query = "select * from ".GD_GOODS_OPTION." where goodsno='$goodsno' and go_is_deleted <> '1' and go_is_display = '1' order by sno asc";
	$res = $db->query($query);
	$idx=0; while ($tmp=$db->fetch($res,1)){
		$tmp = array_map("htmlspecialchars",$tmp);
		if ($tmp[stock] && !$isSelected){
			$isSelected = 1;
			$tmp[selected] = "selected";
			$preSelIndex = $idx++;
		}

		### �ɼǺ� ȸ�� ���ΰ� �� ���� ���ΰ� ���
		$realprice = $tmp[realprice] = $tmp[memberdc] = $tmp[coupon] = $tmp[coupon_emoney] = $tmp[couponprice] = 0;
		if(!$mdc_exc) $tmp[memberdc] = getDcprice($tmp[price],$member[dc]."%");
		$tmp[realprice] = $tmp[price] - $tmp[memberdc];
		// ����Ͽ����� �Ϲ� ���� + ��������� ��� ��� ���� getCouponInfoMobile �� �ش� �޼ҵ� �̴�
		//$tmp_coupon = getCouponInfo($data[goodsno],$tmp['price'],'v');
		$tmp_coupon = getCouponInfoMobile($data[goodsno],$tmp['price'],'v');

		if($cfgCoupon[use_yn] == '1'){
			if($tmp_coupon)foreach($tmp_coupon as $v){
				$tp = $v[price];
				if(substr($v[price],-1) == '%') $tp = getDcprice($tmp[price],$v[price]);
				if(!$v[ability] && $tmp[coupon] < $tp) $tmp[coupon] = $tp;
				else if($v[ability] && $tmp[coupon_emoney] < $tp) $tmp[coupon_emoney] = $tp;
			}
		}
		if($tmp[coupon] && $tmp[memberdc] && $cfgCoupon[range] != '2') $realprice = $tmp[realprice];
		else $realprice = $tmp[price];
		$tmp[couponprice] = $realprice - $tmp[coupon];
		if($tmp[coupon] && $tmp[memberdc] && $cfgCoupon[range] == '2') $tmp[realprice] = $tmp[memberdc] = 0;
		if($tmp[coupon] && $tmp[memberdc] && $cfgCoupon[range] == '1') $tmp[couponprice] = $tmp[coupon] = 0;
		if (!$optkey){
			$optkey = $tmp[opt1];
			$data[a_coupon] = $tmp_coupon;
		}

		if(!$data['use_emoney']){
			if($set['emoney']['useyn'] == 'n') $tmp['reserve'] = 0;
			else {
				if( !$set['emoney']['chk_goods_emoney'] ){
					$tmp['reserve']	= 0;
					if( $set['emoney']['goods_emoney'] ) $tmp['reserve'] = getDcprice($tmp['price'],$set['emoney']['goods_emoney'].'%');
				}else{
					$tmp['reserve']	= $set['emoney']['goods_emoney'];
				}
			}
		}

		$opt[$tmp[opt1]][] = $tmp;
		$data[stock] += $tmp[stock];
	}

	### ����� ���� �ڵ� ǰ�� ó��
	if ($data[usestock] && $data[stock]==0) $data[runout] = 1;

	$data[coupon] = $data[coupon_emoney] = 0;
	$data[price]	= &$opt[$optkey][0][price];
	$data[consumer]	= &$opt[$optkey][0][consumer];
	$data[reserve]	= &$opt[$optkey][0][reserve];
	$data[coupon]	= &$opt[$optkey][0][coupon];
	$data[couponprice]	= &$opt[$optkey][0][couponprice];
	$data[coupon_emoney]	= &$opt[$optkey][0][coupon_emoney];
	$data[memberdc]	= &$opt[$optkey][0][memberdc];
	$data[realprice]	= &$opt[$optkey][0][realprice];

	$data[optnm]	= str_replace("|","/",$data[optnm]);
	if ($opt[$optkey][0][opt1] == null && $opt[$optkey][0][opt2] == null) unset($opt);
	if (!$optnm[1]) $typeOption = "single";

	## �����ŷ�����ȸ ��ǰ �ʼ� ���� �߰�
	if ($data['extra_info']) {

		$extra_info = gd_json_decode(stripslashes($data['extra_info']));
		$keys = array_keys($extra_info);

		for ($i=min($keys),$m=max($keys);$i<=$m;$i++) {

			$next_key = $i + 1 <= $m ? $i + 1 : null;

			if (!isset($extra_info[$i])) continue;

			if ($i % 2 == 1 && !isset($extra_info[$next_key])) {
				$colspan = 3;
			}
			else {
				$colspan = 1;
			}

			$extra_info[$i]['nkey'] = $next_key;
			$extra_info[$i]['colspan'] = $colspan;
			$extra_info[$i]['title'] = htmlspecialchars($extra_info[$i]['title']);
			$extra_info[$i]['desc'] = htmlspecialchars($extra_info[$i]['desc']);
		}

	}
	else {
		$extra_info = array();
	}
	$data['extra_info'] = $extra_info;

	### �߰��ɼ�
	$r_addoptnm = explode("|",$data[addoptnm]);
	for ($i=0;$i<count($r_addoptnm);$i++) list ($addoptnm[],$addoptreq[]) = explode("^",$r_addoptnm[$i]);
	$query = "select * from ".GD_GOODS_ADD." where goodsno='$goodsno' order by sno asc";
	$res = $db->query($query);
	while ($tmp=$db->fetch($res,1)) $addopt[$addoptnm[$tmp[step]]][] = $tmp;

	### SNS Start
	//if ($snsCfg['useBtn'] == 'y') {
		require_once "../../shop/lib/sns.class.php";

		$sns = new SNS();
		$sns->mobileSkin = true;
		$goodsurl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?goodsno='.$_GET['goodsno'];
		$goodsurl = preg_replace('/\/m2\/goods\/view/', '/shop/goods/goods_view', $goodsurl);
		$args = array('shopnm'=>$cfg['shopName'], 'goodsnm'=>$data['goodsnm'], 'goodsurl'=>$goodsurl, 'img'=>$data['img_s']);
		$snsRes = $sns->get_post_btn_mobile($args);
		$customHeader .= $snsRes['meta']; // ���̽��Ͽ� ���� meta tag
		$data['snsBtn'] = $snsRes['btn'];

		if($snsCfg['use_kakao'] == 'y') {
			$data['msg_kakao1'] = $sns->msg_kakao1;
			$data['msg_kakao2'] = $sns->msg_kakao2;
			$data['msg_kakao3'] = $sns->msg_kakao3;
		}
	//}
	### SNS End (*������������� īī����ũ�� ����ϸ�, ���̿��� ������ ������� ����)

	// ���̹� üũ�ƿ�
	$naverCheckout = &load_class('naverCheckoutMobile', 'naverCheckoutMobile');
	if($naverCheckout->isAvailable() && $naverCheckout->checkGoods($goodsno, $data['goodsnm']))
	{
		if((int)$data['runout']) $checkoutEnable = false;
		else if($data['usestock']==='o' && (int)$data['totstock']===0) $checkoutEnable = false;
		else $checkoutEnable = true;
		$tpl->assign('naverCheckout', $naverCheckout->getButtonTag('GOODS_VIEW', $checkoutEnable));
	}

	// ���̹� ���ϸ���
	$naverNcash = Core::loader('naverNcash');
	if ($naverNcash->canUseMobile() === false) $naverNcash->useyn = "N";
	if ($naverNcash->useyn == 'Y' && $naverNcash->baseAccumRate) {
		$exceptionYN = $naverNcash->exception_goods(array(array('goodsno' => $goodsno)));
		if($exceptionYN == 'Y') {
			$N_ex = '���� �� ��� ���� ��ǰ';
		}
		$tpl->assign('NaverMileageAccum', include dirname(__FILE__).'/../../shop/proc/naver_mileage/goods_accum_rate_type_2.php');
	}

	if($cfgMobileShop['vtype_goods_view_skin'] == 1 || $cfgMobileShop['vtype_goods_view_skin'] == '') {

		$key_file = preg_replace( "'^.*$mobileRootDir/'si", "", $_SERVER['SCRIPT_NAME'] );
		$key_file = preg_replace( "'\.php$'si", "2.htm", $key_file );

		if(is_file($tpl->template_dir.'/'.$key_file)) {
			$tpl->define( array(
				'tpl'			=> $key_file,
			) );
		}
	}

	if ($cfgMobileShop['goods_view_quick_menu_useyn'] !== 'n') {
		if (file_exists(dirname(__FILE__).'/../../'.$cfg['rootDir'].'/data/skin_mobileV2/'.$cfgMobileShop['tplSkinMobile'].'/proc/quick_menu.htm')) {
			list($cntGoodsQna) = $db->fetch('SELECT COUNT(*) FROM gd_goods_qna WHERE goodsno='.$goodsno);
			list($cntGoodsReview) = $db->fetch('SELECT COUNT(*) FROM gd_goods_review WHERE goodsno='.$goodsno);
			$tpl->assign('QuickMenuEnabled', true);
			$tpl->assign('cnt_goods_qna', (int)$cntGoodsQna);
			$tpl->assign('cnt_goods_review', (int)$cntGoodsReview);
			$tpl->define('QuickMenuScript', 'proc/quick_menu.htm');
		}
	}

	//������
	if(is_file($shopRootDir . '/lib/payco.class.php')){
		$Payco = Core::loader('payco')->getButtonHtmlCode('CHECKOUT', true, 'goodsView');
		if($Payco) $tpl->assign('Payco', $Payco);
	}

	### ���ø� ���
	$tpl->assign($data);
	$tpl->assign('customHeader', $customHeader);
	$goodsBuyable = getGoodsBuyable($goodsno); 
	$tpl->assign('goodsBuyable', $goodsBuyable);
	$tpl->print_('tpl');
?>
